<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\FormFieldDefinition;
use App\Models\ExternalProjectSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Collection;

class ProjectController extends Controller
{
    /**
     * プロジェクトのカスタムフィールド定義を取得します。
     * プロジェクト固有の定義が存在する場合はそれを、なければグローバル定義を使用します。
     *
     * @param Project|null $project
     * @return array
     */
    private function getProjectCustomFieldDefinitions(Project $project = null): array
    {
        if ($project && !empty($project->form_definitions) && is_array($project->form_definitions)) {
            return collect($project->form_definitions)
                ->sortBy(function ($definition) {
                    return $definition['order'] ?? 0;
                })
                ->values()
                ->all();
        }

        $globalDefinitions = FormFieldDefinition::where('is_enabled', true)
            ->orderBy('order')
            ->orderBy('label')
            ->get();

        if ($globalDefinitions->isEmpty()) {
            return [];
        }

        return $globalDefinitions->map(function (FormFieldDefinition $def) {
            $optionsString = '';
            if (is_array($def->options)) {
                $optionsParts = [];
                foreach ($def->options as $value => $label) {
                    $optionsParts[] = $value . ':' . $label;
                }
                $optionsString = implode(',', $optionsParts);
            } elseif (is_string($def->options)) {
                $optionsString = $def->options;
            }
            return [
                'name' => $def->name,
                'label' => $def->label,
                'type' => $def->type,
                'options' => $optionsString,
                'placeholder' => $def->placeholder,
                'required' => $def->is_required,
                'order' => $def->order,
                'maxlength' => $def->max_length,
            ];
        })->sortBy('order')->values()->all();
    }

    /**
     * バリデーションルールとカスタム属性名を構築します。
     *
     * @param array $customFieldDefinitions
     * @param Request $request
     * @param boolean $isUpdate
     * @param Project|null $project
     * @return array
     */
    private function buildValidationRulesAndNames(array $customFieldDefinitions, Request $request, bool $isUpdate = false, ?Project $project = null): array
    {
        $rules = [];
        $customMessages = [];
        $attributeNames = [];

        // 専用カラムの基本バリデーション
        $dedicatedRules = [
            'title'         => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:255',
            'description'   => 'nullable|string',
            'start_date'    => 'nullable|date_format:Y-m-d',
            'end_date'      => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'color'         => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:7',
            'is_favorite'   => 'boolean',
            'delivery_flag' => 'nullable|string|max:1',
            'payment_flag'  => 'nullable|string|max:50',
            'payment'       => 'nullable|string',
            'status'        => 'nullable|string|max:50',
            'series_title'  => 'nullable|string|max:255',
            'client_name'   => 'nullable|string|max:255',
            'budget'        => ($isUpdate ? 'sometimes|' : '') . 'nullable|integer|min:0',
            'target_cost'   => [
                ($isUpdate ? 'sometimes' : ''),
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $project, $isUpdate) {
                    $budget = $request->input('budget');
                    // 更新時で、かつリクエストにbudgetが含まれていない場合、既存のプロジェクトのbudgetを使用
                    if (!$request->has('budget') && $isUpdate && $project) {
                        $budget = $project->budget;
                    }
                    // $value (目標コスト) と $budget (予算) が両方ともnullでない場合のみ比較
                    if ($value !== null && $budget !== null && (int)$value > (int)$budget) {
                        $fail('目標コストは予算以下の金額にしてください。');
                    }
                },
            ],
        ];
        $dedicatedAttributeNames = [
            'title' => '案件名',
            'series_title' => '作品名',
            'client_name' => '依頼主名',
            'description' => '備考',
            'start_date' => '開始日',
            'end_date' => '終了日（納期）',
            'color' => 'カラー',
            'is_favorite' => 'お気に入り',
            'delivery_flag' => '納品フラグ',
            'payment_flag' => '支払いフラグ',
            'payment' => '支払条件',
            'status' => '案件ステータス',
            'budget' => '予算',
            'target_cost' => '目標コスト',
        ];

        $rules = array_merge($rules, $dedicatedRules);
        $attributeNames = array_merge($attributeNames, $dedicatedAttributeNames);

        if (($request->filled('start_date') || $request->filled('end_date')) && isset($attributeNames['end_date']) && isset($attributeNames['start_date'])) {
            $customMessages['end_date.after_or_equal'] = $attributeNames['end_date'] . 'は、' . $attributeNames['start_date'] . '以降の日付にしてください。';
        }

        // 案件依頼フィールドの動的バリデーション
        foreach ($customFieldDefinitions as $field) {
            $fieldRules = [];
            $fieldName = $field['name'];
            $validationKey = 'attributes.' . $fieldName;
            $arrayValidationKey = $validationKey . '.*';

            if ($isUpdate && $field['type'] !== 'file_multiple') {
                $fieldRules[] = 'sometimes';
            }

            if ($field['required'] ?? false) {
                if ($field['type'] === 'file_multiple') {
                    $fieldRules[] = 'array';
                    if (!$isUpdate) {
                        $fieldRules[] = 'required';
                        $fieldRules[] = 'min:1';
                        $customMessages[$validationKey . '.required'] = $field['label'] . 'は必須です。';
                        $customMessages[$validationKey . '.min'] = $field['label'] . 'には少なくとも1つのファイルをアップロードしてください。';
                    } else {
                        $hasNewFiles = $request->hasFile($validationKey) && count((array)$request->file($validationKey)) > 0;
                        $existingFiles = $project->attributes[$fieldName] ?? [];
                        // delete_existing_files キーが存在しない場合のエラーを避ける
                        $filesToDeletePaths = $request->input("delete_existing_files.{$fieldName}", []);

                        $remainingExistingFiles = array_udiff((array)$existingFiles, (array)$filesToDeletePaths, function ($a, $b) {
                            $pathA = is_array($a) ? ($a['path'] ?? null) : $a;
                            $pathB = is_array($b) ? ($b['path'] ?? null) : $b;
                            return strcmp((string)$pathA, (string)$pathB);
                        });


                        if (!$hasNewFiles && empty($remainingExistingFiles)) {
                            $fieldRules[] = 'required';
                            $fieldRules[] = 'min:1';
                            $customMessages[$validationKey . '.required'] = $field['label'] . 'は必須です。';
                            $customMessages[$validationKey . '.min'] = $field['label'] . 'には少なくとも1つのファイルを指定または保持してください。';
                        }
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $fieldRules[] = 'accepted';
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
                if ($field['type'] === 'file_multiple') {
                    $fieldRules[] = 'array';
                }
            }

            switch ($field['type']) {
                case 'text':
                case 'textarea':
                case 'color':
                    $fieldRules[] = 'string';
                    if (isset($field['maxlength']) && is_numeric($field['maxlength']) && $field['maxlength'] > 0) {
                        $fieldRules[] = 'max:' . $field['maxlength'];
                    }
                    break;
                case 'date':
                    $fieldRules[] = 'date_format:Y-m-d';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'tel':
                    $fieldRules[] = 'string';
                    if (isset($field['maxlength'])) $fieldRules[] = 'max:' . $field['maxlength'];
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    if (isset($field['maxlength'])) $fieldRules[] = 'max:' . $field['maxlength'];
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    if (isset($field['maxlength'])) $fieldRules[] = 'max:' . $field['maxlength'];
                    break;
                case 'select':
                    if (in_array('required', $fieldRules) && !empty($field['options'])) {
                        $validOptions = [];
                        $optionsArray = explode(',', $field['options']);
                        foreach ($optionsArray as $option) {
                            $parts = explode(':', trim($option), 2);
                            $validOptions[] = trim($parts[0]);
                        }
                        if (!empty($validOptions)) {
                            $fieldRules[] = Rule::in($validOptions);
                        }
                    }
                    break;
                case 'checkbox':
                    break;
                case 'file_multiple':
                    if ($request->hasFile($validationKey) && is_array($request->file($validationKey))) {
                        $rules[$arrayValidationKey] = 'file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,txt|max:10240'; // 各10MB
                    }
                    $attributeNames[$arrayValidationKey] = $field['label'] . 'の各ファイル';
                    $rules[$validationKey] = implode('|', array_unique($fieldRules));
                    continue 2;
            }

            if (!empty($fieldRules)) {
                if ($isUpdate && ($fieldRules[0] ?? '') !== 'sometimes' && $field['type'] !== 'file_multiple' && $field['type'] !== 'checkbox') {
                    array_unshift($fieldRules, 'sometimes');
                }
                $rules[$validationKey] = implode('|', array_unique($fieldRules));
            }
            $attributeNames[$validationKey] = $field['label'];
        }
        return ['rules' => $rules, 'messages' => $customMessages, 'names' => $attributeNames];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::orderBy('title')->get();
        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Project::class);
        $customFormFields = $this->getProjectCustomFieldDefinitions(null);
        $formDisplayName = '新規衣装案件';

        $prefillStandardData = [];
        $prefillCustomAttributes = [];
        $externalSubmission = null;

        if ($request->has('external_request_id')) {
            $externalRequestId = $request->input('external_request_id');
            logger()->info('External Request ID received:', ['id' => $externalRequestId]);

            $externalSubmission = ExternalProjectSubmission::find($externalRequestId);

            if ($externalSubmission) {
                logger()->info('External Submission found:', $externalSubmission->toArray());
                if ($externalSubmission->status === 'new' || $externalSubmission->status === 'in_progress') {
                    $prefillStandardData['title'] = ($externalSubmission->submitter_name ?? '外部申請') . '様からの依頼案件（仮）';
                    $prefillStandardData['client_name'] = $externalSubmission->submitter_name;
                    $prefillStandardData['description'] = $externalSubmission->submitter_notes;
                    // budgetとtarget_costもプリフィル対象に含める（もし外部申請にあれば）
                    $prefillStandardData['budget'] = $externalSubmission->budget ?? null;
                    $prefillStandardData['target_cost'] = $externalSubmission->target_cost ?? null;


                    $prefillCustomAttributes = $externalSubmission->submitted_data ?? [];
                    logger()->info('Prefill Custom Attributes for view:', $prefillCustomAttributes);
                } else {
                    logger()->warning('External Submission status not eligible for prefill:', ['status' => $externalSubmission->status]);
                    $externalSubmission = null;
                }
            } else {
                logger()->warning('External Submission not found for ID:', ['id' => $externalRequestId]);
            }
        }

        return view('projects.create', compact('customFormFields', 'formDisplayName', 'prefillStandardData', 'prefillCustomAttributes', 'externalSubmission'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);
        $activeGlobalFieldDefinitions = $this->getProjectCustomFieldDefinitions(null);
        $validationConfig = $this->buildValidationRulesAndNames($activeGlobalFieldDefinitions, $request, false);

        $validatedData = Validator::make($request->all(), $validationConfig['rules'], $validationConfig['messages'], $validationConfig['names'])->validate();

        $dedicatedData = Arr::only($validatedData, [
            'title',
            'series_title',
            'client_name',
            'description',
            'start_date',
            'end_date',
            'color',
            'delivery_flag',
            'payment_flag',
            'payment',
            'status',
            'budget',
            'target_cost' // budget と target_cost を追加
        ]);
        if (empty($dedicatedData['title'])) {
            $dedicatedData['title'] = '名称未設定案件 - ' . now()->format('YmdHis');
        }
        $dedicatedData['is_favorite'] = $request->boolean('is_favorite');
        if (!empty($dedicatedData['start_date'])) {
            $dedicatedData['start_date'] = Carbon::parse($dedicatedData['start_date'])->format('Y-m-d');
        }
        if (!empty($dedicatedData['end_date'])) {
            $dedicatedData['end_date'] = Carbon::parse($dedicatedData['end_date'])->format('Y-m-d');
        }
        if (empty($dedicatedData['color'])) {
            $dedicatedData['color'] = '#0d6efd';
        }
        // budget と target_cost が空文字列の場合、nullに変換
        $dedicatedData['budget'] = $dedicatedData['budget'] === '' ? null : $dedicatedData['budget'];
        $dedicatedData['target_cost'] = $dedicatedData['target_cost'] === '' ? null : $dedicatedData['target_cost'];


        $project = new Project();
        $project->fill($dedicatedData);
        $project->form_definitions = $activeGlobalFieldDefinitions;
        $project->save(); // ProjectモデルのLogsActivityが発火 (createdイベント)

        $customAttributesValues = [];
        $submittedAttributes = $request->input('attributes', []);
        $uploadedFileLogDetails = [];

        foreach ($activeGlobalFieldDefinitions as $field) {
            $fieldName = $field['name'];
            if ($field['type'] === 'checkbox') {
                $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
            } elseif ($field['type'] === 'file_multiple') {
                $fileInputKey = 'attributes.' . $fieldName;
                if ($request->hasFile($fileInputKey)) {
                    $uploadedFiles = $request->file($fileInputKey);
                    $storedFilePaths = [];
                    foreach ($uploadedFiles as $file) {
                        $directory = "project_files/{$project->id}/{$fieldName}";
                        $path = $file->store($directory, 'public');
                        $fileMetaData = [
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                        ];
                        $storedFilePaths[] = $fileMetaData;
                        $uploadedFileLogDetails[] = [
                            'field_label' => $field['label'],
                            'field_name' => $fieldName,
                            'original_name' => $fileMetaData['original_name'],
                            'path' => $fileMetaData['path'],
                            'size' => $fileMetaData['size'],
                        ];
                    }
                    $customAttributesValues[$fieldName] = $storedFilePaths;
                } else {
                    $customAttributesValues[$fieldName] = [];
                }
            } elseif (isset($submittedAttributes[$fieldName])) {
                $customAttributesValues[$fieldName] = $submittedAttributes[$fieldName];
            } else {
                $customAttributesValues[$fieldName] = null;
            }
        }

        $project->attributes = $customAttributesValues;
        $project->save();


        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project)
                    ->withProperties($logDetail)
                    ->log("プロジェクト「{$project->title}」の案件依頼項目「{$logDetail['field_label']}」にファイル「{$logDetail['original_name']}」がアップロードされました。");
            }
        }


        if ($request->filled('external_submission_id_on_creation')) {
            $externalSubmissionId = $request->input('external_submission_id_on_creation');
            $externalSubmission = ExternalProjectSubmission::find($externalSubmissionId);

            if ($externalSubmission) {
                if ($externalSubmission->status === 'new' || $externalSubmission->status === 'in_progress') {
                    $externalSubmission->status = 'processed';
                    $externalSubmission->processed_by_user_id = auth()->id();
                    $externalSubmission->processed_at = now();
                    $externalSubmission->save();

                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($externalSubmission)
                        ->withProperties(['project_id' => $project->id])
                        ->log("外部申請 ID:{$externalSubmission->id} が案件化され、ステータスが 'processed' に更新されました。");
                } else {
                    logger()->info('External submission status was not "new" or "in_progress" during project creation finalization, no status change needed.', [
                        'external_submission_id' => $externalSubmissionId,
                        'current_status' => $externalSubmission->status,
                        'project_id' => $project->id
                    ]);
                }
            } else {
                logger()->error('External submission not found during project creation finalization, though an ID was provided.', [
                    'external_submission_id_on_creation' => $externalSubmissionId,
                    'project_id' => $project->id
                ]);
            }
        }

        return redirect()->route('projects.show', $project)->with('success', '衣装案件が作成されました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $appendTasksRecursively = function ($parentId, $tasksGroupedByParent, &$sortedTasksList) use (&$appendTasksRecursively) {
            $keyForGrouping = $parentId === null ? '' : $parentId;
            if (!$tasksGroupedByParent->has($keyForGrouping)) {
                return;
            }
            $childrenOfCurrentParent = $tasksGroupedByParent->get($keyForGrouping)
                ->sortBy(function ($task) {
                    return [$task->start_date === null ? PHP_INT_MAX : $task->start_date->getTimestamp(), $task->name];
                });
            foreach ($childrenOfCurrentParent as $task) {
                $sortedTasksList->push($task);
                $appendTasksRecursively($task->id, $tasksGroupedByParent, $sortedTasksList);
            }
        };

        if ($request->ajax()) {
            $hideCompleted = $request->boolean('hide_completed');
            $context = $request->input('context', 'project');
            $character = null;
            $tasksToList = collect();
            $tableId = 'project-tasks-table';
            $matchingTaskIds = collect();

            if ($context === 'character' && $request->has('character_id')) {
                // キャラクターの工程表を更新する場合 (変更なし)
                $character = $project->characters()->with(['tasks.children', 'tasks.parent'])->find($request->input('character_id'));
                if ($character) {
                    $allCharacterTasks = $character->tasks;
                    $tasksForHierarchy = $allCharacterTasks;
                    $matchingTaskIds = $allCharacterTasks->pluck('id');
                    if ($hideCompleted) {
                        $matchingTasks = $allCharacterTasks->where('status', '!=', 'completed');
                        $matchingTaskIds = $matchingTasks->pluck('id');
                        $requiredTaskIds = collect();
                        if ($matchingTaskIds->isNotEmpty()) {
                            $allTasksLookup = $allCharacterTasks->keyBy('id');
                            $tasksToProcess = $matchingTaskIds->toArray();
                            $processedIds = collect();
                            while (!empty($tasksToProcess)) {
                                $currentId = array_pop($tasksToProcess);
                                if ($processedIds->contains($currentId)) continue;
                                $requiredTaskIds->push($currentId);
                                $processedIds->push($currentId);
                                $task = $allTasksLookup->get($currentId);
                                if ($task && $task->parent_id) array_push($tasksToProcess, $task->parent_id);
                            }
                        }
                        $tasksForHierarchy = $allCharacterTasks->whereIn('id', $requiredTaskIds->unique());
                    }
                    $tasksGrouped = $tasksForHierarchy->groupBy('parent_id');
                    $sortedList = collect();
                    $appendTasksRecursively(null, $tasksGrouped, $sortedList);
                    $tasksToList = $hideCompleted ? $sortedList->filter(fn($task) => $matchingTaskIds->contains($task->id)) : $sortedList;
                    $tableId = 'character-tasks-table-' . $character->id;
                }
            } else {
                // ▼▼▼【ここから修正】案件全体の工程表を更新する場合のロジックを修正 ▼▼▼
                $allProjectTasks = $project->tasksWithoutCharacter()->with(['children', 'parent', 'project', 'character', 'files', 'assignees'])->get();
                $tasksForHierarchy = $allProjectTasks;
                $matchingTaskIds = $allProjectTasks->pluck('id');

                if ($hideCompleted) {
                    $matchingTasks = $allProjectTasks->where('status', '!=', 'completed');
                    $matchingTaskIds = $matchingTasks->pluck('id');
                    $requiredTaskIds = collect();
                    if ($matchingTaskIds->isNotEmpty()) {
                        $allTasksLookup = $allProjectTasks->keyBy('id');
                        $tasksToProcess = $matchingTaskIds->toArray();
                        $processedIds = collect();
                        while (!empty($tasksToProcess)) {
                            $currentId = array_pop($tasksToProcess);
                            if ($processedIds->contains($currentId)) continue;
                            $requiredTaskIds->push($currentId);
                            $processedIds->push($currentId);
                            $task = $allTasksLookup->get($currentId);
                            if ($task && $task->parent_id) array_push($tasksToProcess, $task->parent_id);
                        }
                    }
                    $tasksForHierarchy = $allProjectTasks->whereIn('id', $requiredTaskIds->unique());
                }

                $tasksGrouped = $tasksForHierarchy->groupBy('parent_id');
                $sortedList = collect();
                $appendTasksRecursively(null, $tasksGrouped, $sortedList);

                $tasksToList = $hideCompleted ? $sortedList->filter(fn($task) => $matchingTaskIds->contains($task->id)) : $sortedList;
                $tableId = 'project-tasks-table';
                // ▲▲▲【ここまで修正】▲▲▲
            }

            $assigneeOptions = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name'])->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()->all();
            $viewData = compact('tasksToList', 'tableId', 'project', 'assigneeOptions', 'hideCompleted', 'character');
            $html = view('projects.partials.projects-task-table', $viewData)->render();
            return response()->json(['html' => $html]);
        }

        // --- 通常のページ読み込み時のデータ準備 ---
        $project->load([
            'characters' => function ($query) {
                $query->with(['tasks.children', 'tasks.parent', 'tasks.project', 'tasks.character', 'tasks.files', 'tasks.assignees', 'measurements' => fn($query) => $query->orderBy('display_order'), 'materials' => fn($query) => $query->orderBy('display_order'), 'costs' => fn($query) => $query->orderBy('display_order')])->orderBy('name');
            },
            'tasks' => function ($query) {
                $query->with(['children', 'parent', 'project', 'character', 'files', 'assignees']);
            }
        ]);
        $tasksToList = $project->tasksWithoutCharacter()->orderByRaw('ISNULL(start_date), start_date ASC, name ASC')->get();
        foreach ($project->characters as $character) {
            $characterTasksCollection = $character->tasks;
            if (!($characterTasksCollection instanceof Collection)) $characterTasksCollection = collect($characterTasksCollection);
            $tasksGroupedForCharacter = $characterTasksCollection->groupBy('parent_id');
            $sortedCharacterTasks = collect();
            $appendTasksRecursively(null, $tasksGroupedForCharacter, $sortedCharacterTasks);
            $character->sorted_tasks = $sortedCharacterTasks;
        }
        $customFormFields = $this->getProjectCustomFieldDefinitions($project);
        $completionDataMasterFolderName = '_project_completion_data_';
        $masterFolder = $project->tasks()->where('name', $completionDataMasterFolderName)->where('is_folder', true)->firstOrCreate(['name' => $completionDataMasterFolderName, 'project_id' => $project->id], ['is_folder' => true, 'parent_id' => null, 'character_id' => null]);
        $completionDataFolders = Task::where('parent_id', $masterFolder->id)->where('is_folder', true)->with('files')->orderBy('name')->get();
        $availableInventoryItems = InventoryItem::where('quantity',  '>', 0)->orderBy('name')->get();
        $assigneeOptions = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name'])->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()->all();
        return view('projects.show', compact('project', 'customFormFields', 'availableInventoryItems', 'tasksToList', 'masterFolder', 'completionDataFolders', 'assigneeOptions'));
    }
    /**
     * 案件詳細ページで完成データ用のフォルダを作成します。
     */
    public function storeCompletionFolder(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'required|exists:tasks,id',
        ], [
            'name.required' => 'フォルダ名は必須です。',
            'parent_id.exists' => '親フォルダが見つかりません。',
        ]);

        // 親フォルダがこのプロジェクトのものであることを確認
        $parentFolder = Task::where('id', $validated['parent_id'])
            ->where('project_id', $project->id)
            ->where('is_folder', true)
            ->firstOrFail();

        Task::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'is_folder' => true,
            'parent_id' => $parentFolder->id,
            'character_id' => null, // 案件直下のファイルなのでキャラクターは紐付けない
        ]);

        return redirect()->route('projects.show', $project)->with('success', '完成データ用のフォルダが作成されました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        $customFormFields = $this->getProjectCustomFieldDefinitions($project);
        $formDisplayName = '衣装案件編集';
        return view('projects.edit', compact('project', 'customFormFields', 'formDisplayName'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $currentCustomFieldDefinitions = $this->getProjectCustomFieldDefinitions($project);
        $validationConfig = $this->buildValidationRulesAndNames($currentCustomFieldDefinitions, $request, true, $project);
        $validatedData = Validator::make($request->all(), $validationConfig['rules'], $validationConfig['messages'], $validationConfig['names'])->validate();

        $dedicatedDataToUpdate = Arr::only($validatedData, [
            'title',
            'series_title',
            'client_name',
            'description',
            'start_date',
            'end_date',
            'color',
            'delivery_flag',
            'payment_flag',
            'payment',
            'status',
            'budget',
            'target_cost' // budget と target_cost を追加
        ]);
        if ($request->has('is_favorite') || array_key_exists('is_favorite', $validatedData)) {
            $dedicatedDataToUpdate['is_favorite'] = $request->boolean('is_favorite');
        }
        if (isset($dedicatedDataToUpdate['start_date'])) {
            $dedicatedDataToUpdate['start_date'] = !empty($dedicatedDataToUpdate['start_date']) ? Carbon::parse($dedicatedDataToUpdate['start_date'])->format('Y-m-d') : null;
        }
        if (isset($dedicatedDataToUpdate['end_date'])) {
            $dedicatedDataToUpdate['end_date'] = !empty($dedicatedDataToUpdate['end_date']) ? Carbon::parse($dedicatedDataToUpdate['end_date'])->format('Y-m-d') : null;
        }
        // budget と target_cost が空文字列の場合、nullに変換
        if (array_key_exists('budget', $dedicatedDataToUpdate)) {
            $dedicatedDataToUpdate['budget'] = $dedicatedDataToUpdate['budget'] === '' ? null : $dedicatedDataToUpdate['budget'];
        }
        if (array_key_exists('target_cost', $dedicatedDataToUpdate)) {
            $dedicatedDataToUpdate['target_cost'] = $dedicatedDataToUpdate['target_cost'] === '' ? null : $dedicatedDataToUpdate['target_cost'];
        }


        $customAttributesValues = $project->attributes ?? [];
        $submittedAttributes = $request->input('attributes', []);
        $uploadedFileLogDetails = [];
        $deletedFileLogDetails = [];

        foreach ($currentCustomFieldDefinitions as $field) {
            $fieldName = $field['name'];
            $fieldLabel = $field['label'];

            if ($field['type'] === 'checkbox') {
                $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
            } elseif ($field['type'] === 'file_multiple') {
                $fileInputKey = 'attributes.' . $fieldName;
                $newlyUploadedFileMeta = [];

                if ($request->hasFile($fileInputKey)) {
                    $uploadedFiles = $request->file($fileInputKey);
                    foreach ($uploadedFiles as $file) {
                        $directory = "project_files/{$project->id}/{$fieldName}";
                        $path = $file->store($directory, 'public');
                        $fileMetaData = [
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                        ];
                        $newlyUploadedFileMeta[] = $fileMetaData;
                        $uploadedFileLogDetails[] = [
                            'field_label' => $fieldLabel,
                            'field_name' => $fieldName,
                            'original_name' => $fileMetaData['original_name'],
                            'path' => $fileMetaData['path'],
                            'size' => $fileMetaData['size'],
                        ];
                    }
                }

                $existingFilesMeta = $customAttributesValues[$fieldName] ?? [];
                $keptFilesMeta = [];
                $filesToDeletePathsForField = $request->input("attributes.{$fieldName}_delete", []); // 修正: form-fields.blade.php の name属性に合わせる

                if (is_array($existingFilesMeta)) {
                    foreach ($existingFilesMeta as $fileMeta) {
                        $existingFilePath = is_array($fileMeta) ? ($fileMeta['path'] ?? null) : $fileMeta;
                        $existingOriginalName = is_array($fileMeta) ? ($fileMeta['original_name'] ?? basename((string)$existingFilePath)) : basename((string)$existingFilePath);

                        if ($existingFilePath && in_array($existingFilePath, $filesToDeletePathsForField)) {
                            Storage::disk('public')->delete($existingFilePath);
                            $deletedFileLogDetails[] = [
                                'field_label' => $fieldLabel,
                                'field_name' => $fieldName,
                                'original_name' => $existingOriginalName,
                                'path' => $existingFilePath,
                            ];
                        } else {
                            $keptFilesMeta[] = $fileMeta;
                        }
                    }
                }
                $customAttributesValues[$fieldName] = array_merge($keptFilesMeta, $newlyUploadedFileMeta);
            } elseif (Arr::has($submittedAttributes, $fieldName)) {
                $customAttributesValues[$fieldName] = Arr::get($submittedAttributes, $fieldName);
            }
        }

        $project->fill($dedicatedDataToUpdate);
        $project->attributes = $customAttributesValues;
        $project->save();

        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project)
                    ->withProperties($logDetail)
                    ->log("プロジェクト「{$project->title}」の案件依頼項目「{$logDetail['field_label']}」にファイル「{$logDetail['original_name']}」がアップロードされました。");
            }
        }
        if (!empty($deletedFileLogDetails)) {
            foreach ($deletedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project)
                    ->withProperties($logDetail)
                    ->log("プロジェクト「{$project->title}」の案件依頼項目「{$logDetail['field_label']}」からファイル「{$logDetail['original_name']}」が削除されました。");
            }
        }

        return redirect()->route('projects.show', $project)->with('success', '衣装案件が更新されました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $deletedFileLogDetails = [];

        if (isset($project->attributes) && is_array($project->attributes) && is_array($project->form_definitions)) {
            foreach ($project->form_definitions as $field) {
                if (is_array($field) && isset($field['type']) && $field['type'] === 'file_multiple' && isset($field['name']) && !empty($project->attributes[$field['name']])) {
                    $filesData = $project->attributes[$field['name']];
                    if (is_array($filesData)) {
                        foreach ($filesData as $fileInfo) {
                            $filePath = null;
                            $originalName = '不明なファイル';
                            if (is_array($fileInfo) && isset($fileInfo['path'])) {
                                $filePath = $fileInfo['path'];
                                $originalName = $fileInfo['original_name'] ?? basename($filePath);
                            } elseif (is_string($fileInfo)) {
                                $filePath = $fileInfo;
                                $originalName = basename($filePath);
                            }

                            if ($filePath && Storage::disk('public')->exists($filePath)) {
                                Storage::disk('public')->delete($filePath);
                                $deletedFileLogDetails[] = [
                                    'field_label' => $field['label'] ?? $field['name'],
                                    'field_name' => $field['name'],
                                    'original_name' => $originalName,
                                    'path' => $filePath,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $project->tasks()->delete();
        foreach ($project->characters as $character) {
            $character->measurements()->delete();
            $character->materials()->delete();
            $character->costs()->delete();
            $character->tasks()->delete();
            $character->delete();
        }

        if (!empty($deletedFileLogDetails)) {
            foreach ($deletedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project)
                    ->withProperties(array_merge($logDetail, ['action' => 'file_deleted_during_project_deletion']))
                    ->log("プロジェクト「{$project->title}」削除に伴い、案件依頼項目「{$logDetail['field_label']}」からファイル「{$logDetail['original_name']}」が削除されました。");
            }
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', '衣装案件が削除されました。');
    }

    /**
     * Update the delivery flag for the specified project.
     */
    public function updateDeliveryFlag(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $validated = $request->validate(['delivery_flag' => ['required', Rule::in(['0', '1'])]]);
        $project->delivery_flag = $validated['delivery_flag'];
        $project->save();
        return response()->json([
            'success' => true,
            'message' => '納品フラグを更新しました。',
            'delivery_flag' => $project->delivery_flag,
            'new_status' => $project->status, // 関連するステータス変更があれば返す
        ]);
    }

    /**
     * Update the payment flag for the specified project.
     */
    public function updatePaymentFlag(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $allowedPaymentFlags = array_keys(\App\Models\Project::PAYMENT_FLAG_OPTIONS);
        // '' (空文字) も許可するために、Rule::in の前に 'nullable' を追加
        $validated = $request->validate(['payment_flag' => ['nullable', Rule::in(array_merge([''], $allowedPaymentFlags))]]);
        $project->payment_flag = $validated['payment_flag'];
        $project->save();
        return response()->json([
            'success' => true,
            'message' => '支払いフラグを更新しました。',
            'payment_flag' => $project->payment_flag,
            'new_status' => $project->status, // 関連するステータス変更があれば返す
        ]);
    }

    /**
     * Update the status for the specified project.
     */
    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $allowedProjectStatuses = array_keys(\App\Models\Project::PROJECT_STATUS_OPTIONS);
        // '' (空文字) も許可するために、Rule::in の前に 'nullable' を追加
        $validated = $request->validate(['status' => ['nullable', Rule::in(array_merge([''], $allowedProjectStatuses))]]);
        $project->status = $validated['status'];
        $project->save();
        return response()->json([
            'success' => true,
            'message' => 'プロジェクトステータスを更新しました。',
            'new_status' => $project->status,
        ]);
    }
}

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
use App\Models\WorkLog;
use App\Models\Cost;
use App\Models\ProjectCategory;

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
            ->where('category', 'project')
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
                'options' => $def->options,
                'placeholder' => $def->placeholder,
                'help_text' => $def->help_text,
                'is_required' => $def->is_required,
                'order' => $def->order,
                'maxlength' => $def->max_length,
                'min_selections' => $def->min_selections, // 追加
                'max_selections' => $def->max_selections, // 追加
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
            'tracking_info'   => 'nullable|array',
            'tracking_info.*.carrier' => 'nullable|string|in:' . implode(',', array_keys(config('shipping.carriers'))),
            'tracking_info.*.number' => 'nullable|string|max:255',
            'tracking_info.*.memo' => 'nullable|string|max:255',
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
                        $fail('予算は総売上以下の金額にしてください。');
                    }
                },
            ],
            'target_material_cost' => ($isUpdate ? 'sometimes|' : '') . 'nullable|integer|min:0',
            'target_labor_cost_rate' => ($isUpdate ? 'sometimes|' : '') . 'nullable|integer|min:0',
            'project_category_id' => 'nullable|exists:project_categories,id',
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
            'tracking_info.*.carrier' => '配送業者',
            'tracking_info.*.number' => '送り状番号',
            'tracking_info.*.memo' => '送り状メモ',
            'budget' => '総売上',
            'target_cost' => '予算',
            'target_material_cost' => '目標材料費',
            'target_labor_cost_rate' => '目標人件費 時給',
            'project_category_id' => '案件カテゴリ',
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
        $allProjects = Project::with('projectCategory')->orderBy('start_date')->orderBy('title')->get();

        // 「完了」または「キャンセル」のステータスを持つ案件をアーカイブ済みとする
        $archivedStatuses = ['completed', 'cancelled'];

        // partitionメソッドを使い、条件に一致するもの（アーカイブ済み）としないもの（進行中）に分割
        list($archivedProjects, $activeProjects) = $allProjects->partition(function ($project) use ($archivedStatuses) {
            return in_array($project->status, $archivedStatuses);
        });

        // カテゴリ情報を取得（display_order順）
        $categories = \App\Models\ProjectCategory::orderBy('display_order')->orderBy('name')->get();

        // カテゴリ別にグループ化（並び順考慮）
        $activeProjectsByCategory = $activeProjects->groupBy(function ($project) {
            return $project->projectCategory ? $project->projectCategory->name : 'uncategorized';
        })->sortBy(function ($projects, $categoryKey) use ($categories) {
            if ($categoryKey === 'uncategorized') {
                return 999999; // 未分類を最後に
            }
            $category = $categories->where('name', $categoryKey)->first();
            return $category ? $category->display_order : 999998;
        });

        $archivedProjectsByCategory = $archivedProjects->groupBy(function ($project) {
            return $project->projectCategory ? $project->projectCategory->name : 'uncategorized';
        })->sortBy(function ($projects, $categoryKey) use ($categories) {
            if ($categoryKey === 'uncategorized') {
                return 999999; // 未分類を最後に
            }
            $category = $categories->where('name', $categoryKey)->first();
            return $category ? $category->display_order : 999998;
        });

        return view('projects.index', compact('activeProjects', 'archivedProjects', 'activeProjectsByCategory', 'archivedProjectsByCategory', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Project::class);
        $formDisplayName = '新規案件';
        $prefillStandardData = [];
        $prefillCustomAttributes = [];
        $externalSubmission = null;
        $categories = ProjectCategory::all();

        if ($request->has('external_request_id')) {
            $externalRequestId = $request->input('external_request_id');
            logger()->info('External Request ID received:', ['id' => $externalRequestId]);

            $externalSubmission = ExternalProjectSubmission::find($externalRequestId);

            if ($externalSubmission) {
                logger()->info('External Submission found:', $externalSubmission->toArray());
                if ($externalSubmission->status === 'new' || $externalSubmission->status === 'in_progress') {
                    $prefillStandardData['title'] = ($externalSubmission->submitter_name ?? '外部申請') . '★';
                    $prefillStandardData['client_name'] = $externalSubmission->submitter_name;
                    $prefillStandardData['description'] = $externalSubmission->submitter_notes;
                    // budgetとtarget_costもプリフィル対象に含める（もし外部申請にあれば）
                    $prefillStandardData['budget'] = $externalSubmission->budget ?? null;
                    $prefillStandardData['target_cost'] = $externalSubmission->target_cost ?? null;

                    // フォームカテゴリから案件カテゴリを取得
                    if ($externalSubmission->formCategory && $externalSubmission->formCategory->project_category_id) {
                        $prefillStandardData['project_category_id'] = $externalSubmission->formCategory->project_category_id;
                    }

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

        // カスタムフィールドの取得 - 外部申請がある場合はそのフォームカテゴリに基づく
        if ($externalSubmission && $externalSubmission->formCategory) {
            logger()->info('Using form category custom fields:', [
                'form_category_id' => $externalSubmission->formCategory->id,
                'form_category_name' => $externalSubmission->formCategory->name
            ]);
            $customFormFields = $this->getCustomFieldsForFormCategory($externalSubmission->formCategory);
        } else {
            logger()->info('Using default project custom fields (no external submission or form category)');
            $customFormFields = $this->getProjectCustomFieldDefinitions(null);
        }

        return view('projects.create', compact('customFormFields', 'formDisplayName', 'prefillStandardData', 'prefillCustomAttributes', 'externalSubmission', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        // --- フォーム定義の取得（変更なし） ---
        $externalSubmission = null;
        if ($request->filled('external_submission_id_on_creation')) {
            $externalSubmission = ExternalProjectSubmission::find($request->input('external_submission_id_on_creation'));
        }
        $activeGlobalFieldDefinitions = ($externalSubmission && $externalSubmission->formCategory)
            ? $this->getCustomFieldsForFormCategory($externalSubmission->formCategory)
            : $this->getProjectCustomFieldDefinitions(null);

        // --- バリデーション（変更なし） ---
        $validationConfig = $this->buildValidationRulesAndNames($activeGlobalFieldDefinitions, $request, false);
        $validatedData = Validator::make($request->all(), $validationConfig['rules'], $validationConfig['messages'], $validationConfig['names'])->validate();

        // --- 専用カラムのデータ処理（変更なし） ---
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
            'tracking_info',
            'budget',
            'target_cost',
            'target_material_cost',
            'target_labor_cost_rate',
            'project_category_id',
        ]);
        if (empty($dedicatedData['title'])) {
            $dedicatedData['title'] = '名称未設定案件 - ' . now()->format('YmdHis');
        }
        $dedicatedData['is_favorite'] = $request->boolean('is_favorite');
        if (!empty($dedicatedData['start_date'])) $dedicatedData['start_date'] = Carbon::parse($dedicatedData['start_date'])->format('Y-m-d');
        if (!empty($dedicatedData['end_date'])) $dedicatedData['end_date'] = Carbon::parse($dedicatedData['end_date'])->format('Y-m-d');
        if (empty($dedicatedData['color'])) $dedicatedData['color'] = '#0d6efd';
        if (isset($dedicatedData['tracking_info'])) {
            $dedicatedData['tracking_info'] = array_values(array_filter($dedicatedData['tracking_info'], fn($item) => !empty($item['carrier']) && !empty($item['number'])));
        }
        foreach (['budget', 'target_cost', 'target_material_cost', 'target_labor_cost_rate', 'project_category_id'] as $key) {
            if (array_key_exists($key, $dedicatedData) && ($dedicatedData[$key] === '' || is_null($dedicatedData[$key]))) {
                $dedicatedData[$key] = null;
            }
        }

        // --- Projectモデルの作成と保存（変更なし） ---
        $project = new Project();
        $project->fill($dedicatedData);
        $project->form_definitions = $activeGlobalFieldDefinitions;
        $project->save();

        $customAttributesValues = [];
        $submittedAttributes = $request->input('attributes', []);
        $uploadedFileLogDetails = [];

        $prefilledFilesData = $request->input('attributes._prefilled_files', []);
        $prefilledFilesToDelete = $request->input('attributes._delete_prefilled_files', []);

        foreach ($activeGlobalFieldDefinitions as $field) {
            $fieldName = $field['name'];
            $fileInputKey = 'attributes.' . $fieldName;

            switch ($field['type']) {
                case 'checkbox':
                    $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'file':
                case 'file_multiple':
                    $finalFilesForField = [];

                    // 1. プリフィルされたファイルのコピー処理
                    if (isset($prefilledFilesData[$fieldName]) && is_array($prefilledFilesData[$fieldName])) {
                        foreach ($prefilledFilesData[$fieldName] as $fileToCopy) {
                            $sourcePath = $fileToCopy['path'];
                            if (in_array($sourcePath, $prefilledFilesToDelete)) {
                                continue;
                            }
                            if ($sourcePath && Storage::disk('public')->exists($sourcePath)) {
                                $destinationDirectory = "project_files/{$project->id}/{$fieldName}";
                                $newFileName = basename($sourcePath);
                                $destinationPath = $destinationDirectory . '/' . $newFileName;

                                Storage::disk('public')->copy($sourcePath, $destinationPath);

                                $fileMetaData = [
                                    'path' => $destinationPath,
                                    'original_name' => $fileToCopy['original_name'],
                                    'mime_type' => $fileToCopy['mime_type'],
                                    'size' => $fileToCopy['size'],
                                ];
                                $finalFilesForField[] = $fileMetaData;
                                $uploadedFileLogDetails[] = array_merge($fileMetaData, ['field_label' => $field['label'], 'field_name' => $fieldName]);
                            }
                        }
                    }

                    // 2. 新規アップロードされたファイルの処理
                    if ($request->hasFile($fileInputKey)) {
                        $uploadedFiles = is_array($request->file($fileInputKey)) ? $request->file($fileInputKey) : [$request->file($fileInputKey)];
                        foreach ($uploadedFiles as $file) {
                            $directory = "project_files/{$project->id}/{$fieldName}";
                            $path = $file->store($directory, 'public');
                            $fileMetaData = [
                                'path' => $path,
                                'original_name' => $file->getClientOriginalName(),
                                'mime_type' => $file->getClientMimeType(),
                                'size' => $file->getSize(),
                            ];
                            $finalFilesForField[] = $fileMetaData;
                            $uploadedFileLogDetails[] = array_merge($fileMetaData, ['field_label' => $field['label'], 'field_name' => $fieldName]);
                        }
                    }

                    // 3. 最終的な値をセット
                    if ($field['type'] === 'file') {
                        $customAttributesValues[$fieldName] = $finalFilesForField[0] ?? null;
                    } else {
                        $customAttributesValues[$fieldName] = $finalFilesForField;
                    }
                    break;

                default:
                    $customAttributesValues[$fieldName] = $submittedAttributes[$fieldName] ?? null;
                    break;
            }
        }

        $project->attributes = $customAttributesValues;
        $project->save();

        // --- ログ記録と外部申請ステータス更新（変更なし） ---
        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()->causedBy(auth()->user())->performedOn($project)->withProperties($logDetail)->log("プロジェクト「{$project->title}」の案件依頼項目「{$logDetail['field_label']}」にファイル「{$logDetail['original_name']}」がアップロードされました。");
            }
        }

        if ($externalSubmission && ($externalSubmission->status === 'new' || $externalSubmission->status === 'in_progress')) {
            $externalSubmission->status = 'processed';
            $externalSubmission->processed_by_user_id = auth()->id();
            $externalSubmission->processed_at = now();
            $externalSubmission->save();
            activity()->causedBy(auth()->user())->performedOn($externalSubmission)->withProperties(['project_id' => $project->id])->log("外部申請 ID:{$externalSubmission->id} が案件化され、ステータスが 'processed' に更新されました。");
        }

        return redirect()->route('projects.show', $project)->with('success', '案件が作成されました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        // プロジェクトカテゴリのリレーションを読み込み
        $project->load('projectCategory');

        $appendTasksRecursively = function ($parentId, $tasksGroupedByParent, &$sortedTasksList) use (&$appendTasksRecursively) {
            $keyForGrouping = $parentId === null ? '' : $parentId;
            if (!$tasksGroupedByParent->has($keyForGrouping)) {
                return;
            }
            $childrenOfCurrentParent = $tasksGroupedByParent->get($keyForGrouping)
                ->sortBy('id');
            foreach ($childrenOfCurrentParent as $task) {
                $sortedTasksList->push($task);
                $appendTasksRecursively($task->id, $tasksGroupedByParent, $sortedTasksList);
            }
        };

        if ($request->ajax()) {
            $hideCompleted = $request->boolean('hide_completed');
            $context = $request->input('context', 'project');
            $viewData = [
                'project' => $project,
                'hideCompleted' => $hideCompleted,
                'assigneeOptions' => User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name'])->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()->all(),
            ];
            $viewPath = '';

            if ($context === 'character' && $request->has('character_id')) {
                // 【キャラクターの工程表を更新する場合】
                $character = $project->characters()->with(['tasks.children', 'tasks.parent'])->find($request->input('character_id'));
                if ($character) {
                    // (フィルタリングロジックは変更なし)
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

                    // ★ 正しいテンプレートと変数を設定
                    $viewPath = 'projects.partials.character-tasks-table';
                    $viewData['character'] = $character;
                    $viewData['tasksToList'] = $tasksToList;
                    $viewData['tableId'] = 'character-tasks-table-' . $character->id;
                    $viewData['showCharacterColumn'] = false; // キャラクタービューではキャラクター列は不要
                }
            } else {
                // 【案件全体の工程表を更新する場合】
                // (フィルタリングロジックは変更なし)
                $allProjectTasks = $project->tasksWithoutCharacter()->with(['children', 'parent', 'project', 'character', 'files', 'assignees', 'workLogs'])->get();
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

                // ★ 正しいテンプレートと変数を設定
                $viewPath = 'projects.partials.projects-task-table';
                $viewData['tasksToList'] = $tasksToList;
                $viewData['tableId'] = 'project-tasks-table';
                $viewData['showCharacterColumn'] = false; // 案件全体ビューでもキャラクター列は不要
            }

            if (empty($viewPath)) {
                return response()->json(['html' => '<p class="text-red-500">データの取得に失敗しました。</p>']);
            }

            $html = view($viewPath, $viewData)->render();
            return response()->json(['html' => $html]);
        }

        // 1. 全キャラクターのコストレコードを一度に取得
        $all_costs = Cost::with('character')
            ->where('project_id', $project->id)
            ->get();

        // 2. 実績材料費の計算と内訳の取得
        // 'type'カラムが「材料費」のものをフィルタリングして合計
        $material_cost_breakdown = $all_costs->whereIn('type', ['材料費', 'その他'])->sortByDesc('amount');
        $actual_material_cost = $material_cost_breakdown->sum('amount');
        // 3. 実績人件費の計算と内訳の取得
        // 3-1. 作業費（コストテーブルから）
        $manual_labor_related_costs = $all_costs->whereIn('type', ['作業費', '交通費']);
        $actual_labor_cost_from_costs = $manual_labor_related_costs->sum('amount');

        // 3-2. 人件費（作業ログから）
        $actual_labor_cost_from_logs = 0;
        $labor_cost_breakdown = []; // 内訳を格納する配列
        // 完了だけでなく一時停止・進行中も含める
        $labor_statuses = ['completed', 'on_hold', 'cancelled', 'in_progress', 'rework'];
        $target_tasks = Task::where('project_id', $project->id)
            ->whereIn('status', $labor_statuses)
            ->with('workLogs.user.hourlyRates')
            ->get();

        foreach ($target_tasks as $task) {
            $actual_work_seconds_per_task = 0;
            $cost_per_task = 0;
            foreach ($task->workLogs as $log) {
                if ($log->user && $log->effective_duration > 0) {
                    $rate = $log->user->getHourlyRateForDate($log->start_time);
                    if ($rate > 0) {
                        $cost_per_this_log = ($log->effective_duration / 3600) * $rate;
                        $cost_per_task += $cost_per_this_log;
                    }
                }
                $actual_work_seconds_per_task += $log->effective_duration;
            }
            if ($actual_work_seconds_per_task > 0) {
                $labor_cost_breakdown[] = [
                    'task_name' => $task->name,
                    'character_name' => $task->character->name ?? '',
                    'estimated_duration_seconds' => ($task->duration ?? 0) * 60,
                    'actual_work_seconds' => $actual_work_seconds_per_task,
                ];
            }
            $actual_labor_cost_from_logs += $cost_per_task;
        }

        // 3-3. 実績人件費の合計 (作業費 + ログからの人件費)
        $actual_labor_cost = $actual_labor_cost_from_costs + $actual_labor_cost_from_logs;
        // 人件費の内訳には「作業費」も追加
        foreach ($manual_labor_related_costs->sortByDesc('amount') as $cost) {
            array_unshift($labor_cost_breakdown, [
                'task_name' => "{$cost->type}: {$cost->item_description} ({$cost->amount}円)",
                'character_name' => $cost->character->name ?? '案件全体',
                'estimated_duration_seconds' => 0,
                'actual_work_seconds' => 0,
            ]);
        }

        // 4. 目標人件費の計算 (変更なし)
        $target_labor_cost = 0;
        if ($project->target_labor_cost_rate > 0) {
            $total_duration_minutes = $project->tasks()
                ->where('is_folder', false)
                ->where('is_milestone', false)
                ->where('is_rework_task', false) // ★★★ 「直し」工程を目標工数から除外 ★★★
                ->sum('duration');
            $target_labor_cost = ($total_duration_minutes / 60) * $project->target_labor_cost_rate;
        }

        // 5. 目標合計コストの計算 (変更なし)
        $total_target_cost = ($project->target_material_cost ?? 0) + $target_labor_cost;


        // --- 通常のページ読み込み時のデータ準備 ---
        $project->load([
            'characters' => function ($query) {
                $query->with([
                    'tasks.children',
                    'tasks.parent',
                    'tasks.project',
                    'tasks.character',
                    'tasks.files',
                    'tasks.assignees',
                    'tasks.workLogs',
                    'measurements' => fn($q) => $q->orderBy('display_order'),
                    'materials' => fn($q) => $q->orderBy('display_order'),
                    'costs' => fn($q) => $q->orderBy('display_order')
                ])->orderBy('name');
            },
            'tasks' => function ($query) {
                $query->with(['children', 'parent', 'project', 'character', 'files', 'assignees', 'workLogs']);
            },
            // ★ 以下のリレーションを追加
            'requests' => function ($query) {
                $query->with(['requester', 'assignees', 'items.completedBy', 'category', 'project'])->latest();
            }
        ]);

        // 案件全体の目標人件費レートを取得
        $target_labor_cost_rate = $project->target_labor_cost_rate ?? 0;

        // 各キャラクターのコスト情報を計算して、キャラクターオブジェクトにプロパティとして追加
        foreach ($project->characters as $character) {
            // 1. 実績コストの計算
            // 1-1. 実績材料費 (Costテーブルから)
            $actual_material_cost_char = $character->costs()->whereIn('type', ['材料費', 'その他'])->sum('amount');

            // 1-2. 実績人件費 (Costテーブルから手動計上分 '作業費', '交通費' など)
            $actual_labor_cost_from_costs_char = $character->costs()->whereIn('type', ['作業費', '交通費'])->sum('amount');

            // 1-3. 実績人件費 (WorkLogから自動計算分)
            $actual_labor_cost_from_logs_char = 0;
            // 関連リレーションをEager Loadしておく（N+1問題対策）
            $character->loadMissing('tasks.workLogs.user.hourlyRates');
            $completed_tasks_char = $character->tasks->where('status', 'completed');

            foreach ($completed_tasks_char as $task) {
                foreach ($task->workLogs as $log) {
                    if ($log->user && $log->effective_duration > 0) {
                        // UserモデルのgetHourlyRateForDateメソッドで時給を取得
                        $rate = $log->user->getHourlyRateForDate($log->start_time);
                        if ($rate > 0) {
                            $actual_labor_cost_from_logs_char += ($log->effective_duration / 3600) * $rate;
                        }
                    }
                }
            }

            // 1-4. キャラクターごとの実績人件費合計と、実績総コスト
            $actual_labor_cost_char = $actual_labor_cost_from_costs_char + $actual_labor_cost_from_logs_char;
            $character->actual_total_cost = $actual_material_cost_char + $actual_labor_cost_char;

            // 2. 目標コストの計算
            // 2-1. 目標材料費 (現状、キャラクターごとに目標材料費を設定する項目がないため、関連する材料の目標コストを合計します)
            // Note: Materialモデルに target_cost カラムが存在しない場合は、この行を $target_material_cost_char = 0; に変更してください。
            $target_material_cost_char = 0;

            // 2-2. 目標人件費 (キャラクターに紐づくタスクの予定工数から計算)
            $total_duration_minutes_char = $character->tasks()->where('is_folder', false)->where('is_milestone', false)->sum('duration');
            $target_labor_cost_char = ($total_duration_minutes_char / 60) * $target_labor_cost_rate;

            // 2-3. キャラクターごとの目標総コスト
            $character->target_total_cost = $target_material_cost_char + $target_labor_cost_char;
        }

        $allProjectTasks = $project->tasksWithoutCharacter()->with(['children', 'parent', 'project', 'character', 'files', 'assignees'])->get();
        $tasksGroupedByParent = $allProjectTasks->groupBy('parent_id');
        $sortedTasksList = new Collection();
        // 親がいないトップレベルの工程から再帰的にリストを構築
        $appendTasksRecursively(null, $tasksGroupedByParent, $sortedTasksList);
        $tasksToList = $sortedTasksList;
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
        return view('projects.show', compact(
            'project',
            'customFormFields',
            'availableInventoryItems',
            'tasksToList',
            'masterFolder',
            'completionDataFolders',
            'assigneeOptions',
            'actual_material_cost',
            'actual_labor_cost',
            'target_labor_cost',
            'total_target_cost',
            'material_cost_breakdown',
            'labor_cost_breakdown'
        ));
    }
    /**
     * 案件詳細ページで完成データ用のフォルダを作成します。
     */
    public function storeCompletionFolder(Request $request, Project $project)
    {
        $this->authorize('canCreateFoldersForFileUpload',  Task::class);

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
        $formDisplayName = '案件編集';
        $categories = ProjectCategory::all();
        return view('projects.edit', compact('project', 'customFormFields', 'formDisplayName', 'categories'));
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
            'tracking_info',
            'budget',
            'target_cost',
            'target_material_cost',
            'target_labor_cost_rate',
            'project_category_id',
        ]);
        if ($request->has('is_favorite')) $dedicatedDataToUpdate['is_favorite'] = $request->boolean('is_favorite');
        if (isset($dedicatedDataToUpdate['start_date'])) $dedicatedDataToUpdate['start_date'] = !empty($dedicatedDataToUpdate['start_date']) ? Carbon::parse($dedicatedDataToUpdate['start_date'])->format('Y-m-d') : null;
        if (isset($dedicatedDataToUpdate['end_date'])) $dedicatedDataToUpdate['end_date'] = !empty($dedicatedDataToUpdate['end_date']) ? Carbon::parse($dedicatedDataToUpdate['end_date'])->format('Y-m-d') : null;
        foreach (['budget', 'target_cost', 'target_material_cost', 'target_labor_cost_rate', 'project_category_id'] as $key) {
            if (array_key_exists($key, $dedicatedDataToUpdate) && ($dedicatedDataToUpdate[$key] === '' || is_null($dedicatedDataToUpdate[$key]))) {
                $dedicatedDataToUpdate[$key] = null;
            }
        }
        if (array_key_exists('tracking_info', $dedicatedDataToUpdate)) {
            $dedicatedDataToUpdate['tracking_info'] = array_values(array_filter($dedicatedDataToUpdate['tracking_info'], fn($item) => !empty($item['carrier']) && !empty($item['number'])));
        }

        $customAttributesValues = $project->attributes ?? [];
        $submittedAttributes = $request->input('attributes', []);

        $filesToDeletePaths = $request->input('attributes._delete_files', []);

        $uploadedFileLogDetails = [];
        $deletedFileLogDetails = [];

        foreach ($currentCustomFieldDefinitions as $field) {
            $fieldName = $field['name'];
            $fileInputKey = 'attributes.' . $fieldName;

            // ファイルタイプ（単一・複数）の処理
            if (in_array($field['type'], ['file', 'file_multiple'])) {
                $existingFiles = $customAttributesValues[$fieldName] ?? null;
                // データを正規化して常に「ファイルの配列」として扱えるようにする
                $normalizedExistingFiles = [];
                if ($existingFiles) {
                    $normalizedExistingFiles = ($field['type'] === 'file') ? [$existingFiles] : $existingFiles;
                }

                // 1. ファイル削除処理
                $keptFiles = [];
                foreach ($normalizedExistingFiles as $fileInfo) {
                    $path = is_array($fileInfo) ? ($fileInfo['path'] ?? null) : null;
                    if ($path && in_array($path, $filesToDeletePaths)) {
                        Storage::disk('public')->delete($path);
                        $deletedFileLogDetails[] = ['field_label' => $field['label'], 'original_name' => $fileInfo['original_name'] ?? basename($path)];
                    } else {
                        $keptFiles[] = $fileInfo; // 削除対象でなければ保持
                    }
                }

                // 2. 新規ファイルアップロード処理
                $newlyUploadedFiles = [];
                if ($request->hasFile($fileInputKey)) {
                    $files = is_array($request->file($fileInputKey)) ? $request->file($fileInputKey) : [$request->file($fileInputKey)];
                    foreach ($files as $file) {
                        $directory = "project_files/{$project->id}/{$fieldName}";
                        $path = $file->store($directory, 'public');
                        $fileMetaData = ['path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getClientMimeType(), 'size' => $file->getSize()];
                        $newlyUploadedFiles[] = $fileMetaData;
                        $uploadedFileLogDetails[] = array_merge($fileMetaData, ['field_label' => $field['label']]);
                    }
                }

                // 3. 保持するファイルと新規ファイルをマージ
                $finalFiles = array_merge($keptFiles, $newlyUploadedFiles);

                // 4. フィールドタイプに応じて最終的な値を設定
                if ($field['type'] === 'file') {
                    $customAttributesValues[$fieldName] = $finalFiles[0] ?? null; // 単一ファイルは最初の要素、またはnull
                } else {
                    $customAttributesValues[$fieldName] = $finalFiles; // 複数ファイルは配列全体
                }
            } elseif ($field['type'] === 'checkbox') {
                if (Arr::has($submittedAttributes, $fieldName) || $request->has("attributes.{$fieldName}")) {
                    $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
                }
            }
            // ファイルとチェックボックス以外の通常のフィールド
            elseif (Arr::has($submittedAttributes, $fieldName)) {
                $customAttributesValues[$fieldName] = Arr::get($submittedAttributes, $fieldName);
            }
        }

        $project->fill($dedicatedDataToUpdate);
        $project->attributes = $customAttributesValues;
        $project->save();

        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()->causedBy(auth()->user())->performedOn($project)->withProperties($logDetail)->log("プロジェクト「{$project->title}」の案件依頼項目「{$logDetail['field_label']}」にファイル「{$logDetail['original_name']}」がアップロードされました。");
            }
        }
        if (!empty($deletedFileLogDetails)) {
            foreach ($deletedFileLogDetails as $logDetail) {
                activity()->causedBy(auth()->user())->performedOn($project)->withProperties($logDetail)->log("プロジェクト「{$project->title}」の案件依頼項目「{$logDetail['field_label']}」からファイル「{$logDetail['original_name']}」が削除されました。");
            }
        }

        return redirect()->route('projects.show', $project)->with('success', '案件が更新されました。');
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
            ->with('success', '案件が削除されました。');
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

    /**
     * Display a listing of the resource for API.
     */
    public function indexApi()
    {
        // ここでは一旦すべてのプロジェクトを返しますが、
        // 将来的にはログインユーザーに紐づくプロジェクトを返すようにします。
        $projects = Project::all();

        return response()->json($projects);
    }

    /**
     * フォームカテゴリに基づくカスタムフィールドを取得
     */
    private function getCustomFieldsForFormCategory($formCategory)
    {
        // フォームカテゴリ名と一致するFormFieldDefinitionを取得
        $globalDefinitions = FormFieldDefinition::where('is_enabled', true)
            ->where('category', $formCategory->name)
            ->orderBy('order')
            ->orderBy('label')
            ->get();

        logger()->info('Custom fields filtered by form category:', [
            'form_category' => $formCategory->name,
            'fields_count' => $globalDefinitions->count(),
            'fields' => $globalDefinitions->pluck('name', 'id')->toArray()
        ]);

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
                'options' => $def->options,
                'placeholder' => $def->placeholder,
                'help_text' => $def->help_text,
                'is_required' => $def->is_required, // is_required を使用
                'order' => $def->order,
                'maxlength' => $def->max_length,
                'min_selections' => $def->min_selections,
                'max_selections' => $def->max_selections,
            ];
        })->sortBy('order')->values()->all();
    }
}

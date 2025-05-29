<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\FormFieldDefinition;
use App\Models\ExternalProjectSubmission; // 追加
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage; // ファイル操作のために追加

class ProjectController extends Controller
{
    private function getProjectCustomFieldDefinitions(Project $project = null): array
    {
        // プロジェクトインスタンスが提供され、固有のform_definitionsを持っている場合はそれを使用
        if ($project && !empty($project->form_definitions) && is_array($project->form_definitions)) {
            return collect($project->form_definitions)
                ->sortBy(function ($definition) {
                    return $definition['order'] ?? 0;
                })
                ->values()
                ->all();
        }

        // 新規プロジェクトの場合、またはプロジェクトに固有の定義がない場合は、
        // データベースの form_field_definitions テーブルから有効なグローバル定義を取得
        $globalDefinitions = FormFieldDefinition::where('is_enabled', true)
            ->orderBy('order')
            ->orderBy('label')
            ->get();

        if ($globalDefinitions->isEmpty()) {
            return []; // DBにグローバル定義がない場合は空配列
        }

        // FormFieldDefinitionモデルのコレクションを、ビューパーシャルが期待する配列構造にマッピング
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

    private function buildValidationRulesAndNames(array $customFieldDefinitions, Request $request, bool $isUpdate = false, ?Project $project = null)
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
        ];

        $rules = array_merge($rules, $dedicatedRules);
        $attributeNames = array_merge($attributeNames, $dedicatedAttributeNames);

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $customMessages['end_date.after_or_equal'] = $attributeNames['end_date'] . 'は、' . $attributeNames['start_date'] . '以降の日付にしてください。';
        }

        // カスタムフィールドの動的バリデーション
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
                    $hasExistingFiles = $project && isset($project->attributes[$fieldName]) && !empty($project->attributes[$fieldName]);
                    if (!($isUpdate && $hasExistingFiles && !$request->hasFile($validationKey) && !$request->input($validationKey . "_delete"))) {
                        $fieldRules[] = 'required';
                    }
                    $fieldRules[] = 'array';
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
                    if (($field['required'] ?? false) && $request->input($validationKey) === '') {
                        $validOptions = [];
                        if (!empty($field['options'])) {
                            $pairs = explode(',', $field['options']);
                            foreach ($pairs as $pair) {
                                $parts = explode(':', trim($pair), 2);
                                $validOptions[] = trim($parts[0]);
                            }
                        }
                        if (!empty($validOptions)) {
                            $fieldRules[] = Rule::in($validOptions);
                        }
                    }
                    break;
                case 'checkbox':
                    break;
                case 'file_multiple':
                    if ($request->hasFile($validationKey)) {
                        $rules[$arrayValidationKey] = 'file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,txt|max:10240'; // 例: 各10MBまで
                    } elseif ($isUpdate && ($field['required'] ?? false) && !$request->hasFile($validationKey) && !($project && isset($project->attributes[$fieldName]) && !empty($project->attributes[$fieldName])) && !$request->input($validationKey . "_delete")) {
                        // 更新時、必須で、新しいファイルがなく既存ファイルもない（または全削除指定がない）場合はエラー
                        $rules[$validationKey] = 'required|array|min:1';
                        $customMessages[$validationKey . '.min'] = $field['label'] . 'は少なくとも1つのファイルを指定してください。';
                    }
                    $attributeNames[$arrayValidationKey] = $field['label'] . 'の各ファイル';
                    // fieldRules は file_multiple の配列自体に使用
                    $rules[$validationKey] = implode('|', $fieldRules);
                    continue 2; // switch を抜けて次の foreach へ
            }

            if (!empty($fieldRules)) {
                if ($isUpdate && ($fieldRules[0] ?? '') !== 'sometimes' && $field['type'] !== 'file_multiple') {
                    array_unshift($fieldRules, 'sometimes');
                }
                $rules[$validationKey] = implode('|', $fieldRules);
            }
            $attributeNames[$validationKey] = $field['label'];
        }
        return ['rules' => $rules, 'messages' => $customMessages, 'names' => $attributeNames];
    }

    public function index()
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::orderBy('title')->get();
        return view('projects.index', compact('projects'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Project::class);
        $customFormFields = $this->getProjectCustomFieldDefinitions(null);
        $formDisplayName = '新規衣装案件';

        $prefillStandardData = [];
        $prefillCustomAttributes = [];
        $externalSubmission = null;

        if ($request->has('external_request_id')) {
            $externalSubmission = ExternalProjectSubmission::find($request->input('external_request_id'));
            if ($externalSubmission && $externalSubmission->status === 'new') {
                $prefillStandardData['title'] = ($externalSubmission->submitter_name ?? '外部申請') . '様からの依頼案件（仮）';
                $prefillStandardData['client_name'] = $externalSubmission->submitter_name;
                $prefillStandardData['description'] = $externalSubmission->submitter_notes;
                // emailはカスタムフィールド 'contact_email' にマッピングするか、備考に追加
                $emailProcessedInCustom = false;
                if (isset($externalSubmission->submitted_data['contact_email'])) { // 'contact_email' というカスタムフィールドがある前提
                    // $prefillCustomAttributes['contact_email'] = $externalSubmission->submitter_email;
                    // $emailProcessedInCustom = true;
                }
                // if(!$emailProcessedInCustom && $externalSubmission->submitter_email) {
                //    $prefillStandardData['description'] = ($prefillStandardData['description'] ? $prefillStandardData['description'] . "\n" : '') . "連絡先Email(外部申請より): " . $externalSubmission->submitter_email;
                // }

                $prefillCustomAttributes = $externalSubmission->submitted_data ?? [];
            }
        }

        return view('projects.create', compact('customFormFields', 'formDisplayName', 'prefillStandardData', 'prefillCustomAttributes', 'externalSubmission'));
    }

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
            'status'
        ]);
        // title は required なので、バリデーション通過時点で存在するはず
        if (empty($dedicatedData['title'])) { // 万が一のためのフォールバック (通常は不要)
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

        // プロジェクトモデルを先に作成してIDを取得
        $project = new Project();
        $project->fill($dedicatedData);
        $project->form_definitions = $activeGlobalFieldDefinitions; // 先に定義をセット
        // attributes はファイル処理後にセットするため、ここではまだ保存しない
        $project->save(); // これで $project->id が利用可能

        $customAttributesValues = [];
        $submittedAttributes = $request->input('attributes', []);

        foreach ($activeGlobalFieldDefinitions as $field) {
            $fieldName = $field['name'];
            if ($field['type'] === 'checkbox') {
                $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
            } elseif ($field['type'] === 'file_multiple') {
                if ($request->hasFile('attributes.' . $fieldName)) {
                    $uploadedFiles = $request->file('attributes.' . $fieldName);
                    $storedFilePaths = [];
                    foreach ($uploadedFiles as $file) {
                        $directory = "project_files/{$project->id}/{$fieldName}";
                        $path = $file->store($directory, 'public'); // publicディスクに保存
                        $storedFilePaths[] = [
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
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

        $project->attributes = $customAttributesValues; // 属性をセット
        $project->save(); // 再度保存して属性を永続化

        if ($request->filled('external_submission_id_on_creation')) {
            $externalSubmission = ExternalProjectSubmission::find($request->input('external_submission_id_on_creation'));
            if ($externalSubmission && $externalSubmission->status === 'new') {
                $externalSubmission->status = 'processed';
                $externalSubmission->processed_by_user_id = auth()->id();
                $externalSubmission->processed_at = now();
                $externalSubmission->save();
            }
        }

        return redirect()->route('projects.show', $project)->with('success', '衣装案件が作成されました。');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);
        $customFormFields = $this->getProjectCustomFieldDefinitions($project);
        $project->load([
            'characters' => function ($query) {
                $query->with(['tasks', 'measurements', 'materials', 'costs'])->orderBy('name');
            },
            'tasksWithoutCharacter' => function ($query) {
                $query->orderByRaw('ISNULL(start_date), start_date ASC');
            }
        ]);

        return view('projects.show', compact('project', 'customFormFields'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        $customFormFields = $this->getProjectCustomFieldDefinitions($project);
        $formDisplayName = '衣装案件編集';
        return view('projects.edit', compact('project', 'customFormFields', 'formDisplayName'));
    }

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
            'status'
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

        $customAttributesValues = $project->attributes ?? [];
        $submittedAttributes = $request->input('attributes', []);
        $filesToDeletePaths = $request->input('delete_existing_files', []); // ビュー側で削除指定された既存ファイルのパス配列

        foreach ($currentCustomFieldDefinitions as $field) {
            $fieldName = $field['name'];
            if ($field['type'] === 'checkbox') {
                $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
            } elseif ($field['type'] === 'file_multiple') {
                $newlyUploadedFileMeta = [];
                if ($request->hasFile('attributes.' . $fieldName)) {
                    $uploadedFiles = $request->file('attributes.' . $fieldName);
                    foreach ($uploadedFiles as $file) {
                        $directory = "project_files/{$project->id}/{$fieldName}";
                        $path = $file->store($directory, 'public');
                        $newlyUploadedFileMeta[] = [
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                        ];
                    }
                }

                $existingFilesMeta = $customAttributesValues[$fieldName] ?? [];
                $keptFilesMeta = [];
                if (is_array($existingFilesMeta)) {
                    foreach ($existingFilesMeta as $fileMeta) {
                        if (is_array($fileMeta) && isset($fileMeta['path']) && in_array($fileMeta['path'], $filesToDeletePaths[$fieldName] ?? [])) {
                            Storage::disk('public')->delete($fileMeta['path']); // ストレージから削除
                        } else {
                            $keptFilesMeta[] = $fileMeta; // 保持するファイル
                        }
                    }
                }
                $customAttributesValues[$fieldName] = array_merge($keptFilesMeta, $newlyUploadedFileMeta);
            } elseif (array_key_exists($fieldName, $submittedAttributes)) {
                $customAttributesValues[$fieldName] = $submittedAttributes[$fieldName];
            } elseif ($isUpdate && !array_key_exists($fieldName, $submittedAttributes) && $field['type'] !== 'checkbox' && $field['type'] !== 'file_multiple' && $request->has('attributes')) {
                // フォームに存在したが値が送信されなかったフィールド（空のテキスト入力など）はnullにするか、既存値を維持するか
                // $customAttributesValues[$fieldName] = null;
            }
        }

        $project->fill($dedicatedDataToUpdate);
        $project->attributes = $customAttributesValues;
        $project->save();

        return redirect()->route('projects.show', $project)->with('success', '衣装案件が更新されました。');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        if (isset($project->attributes) && is_array($project->attributes)) {
            if (is_array($project->form_definitions)) { // form_definitions が配列であることを確認
                foreach ($project->form_definitions as $field) {
                    if (is_array($field) && isset($field['type']) && $field['type'] === 'file_multiple' && isset($field['name']) && !empty($project->attributes[$field['name']])) {
                        $filesData = $project->attributes[$field['name']];
                        if (is_array($filesData)) {
                            foreach ($filesData as $fileInfo) {
                                if (is_array($fileInfo) && isset($fileInfo['path'])) {
                                    Storage::disk('public')->delete($fileInfo['path']);
                                } elseif (is_string($fileInfo)) {
                                    Storage::disk('public')->delete($fileInfo);
                                }
                            }
                        }
                        // ディレクトリごと削除
                        $directory = "project_files/{$project->id}/{$field['name']}";
                        Storage::disk('public')->deleteDirectory($directory);
                    }
                }
            }
            // ルートのプロジェクトファイルディレクトリも削除 (念のため)
            Storage::disk('public')->deleteDirectory("project_files/{$project->id}");
        }

        $project->tasks()->delete();
        foreach ($project->characters as $character) {
            $character->measurements()->delete();
            $character->materials()->delete();
            $character->costs()->delete();
            $character->tasks()->delete();
            $character->delete();
        }
        $project->delete();
        return redirect()->route('projects.index')
            ->with('success', '衣装案件が削除されました。');
    }

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
            'new_status' => $project->status,
        ]);
    }

    public function updatePaymentFlag(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $allowedPaymentFlags = array_keys(\App\Models\Project::PAYMENT_FLAG_OPTIONS);
        $validated = $request->validate(['payment_flag' => ['nullable', Rule::in($allowedPaymentFlags)]]);
        $project->payment_flag = $validated['payment_flag'];
        $project->save();
        return response()->json([
            'success' => true,
            'message' => '支払いフラグを更新しました。',
            'payment_flag' => $project->payment_flag,
            'new_status' => $project->status,
        ]);
    }

    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $allowedProjectStatuses = array_keys(\App\Models\Project::PROJECT_STATUS_OPTIONS);
        $validated = $request->validate(['status' => ['required', Rule::in($allowedProjectStatuses)]]);
        $project->status = $validated['status'];
        $project->save();
        return response()->json([
            'success' => true,
            'message' => 'プロジェクトステータスを更新しました。',
            'new_status' => $project->status,
        ]);
    }
}

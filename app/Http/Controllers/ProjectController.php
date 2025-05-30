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

        // 専用カラムの基本バリデーション (変更なし)
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
            $validationKey = 'attributes.' . $fieldName; // attributes.custom_field_name
            $arrayValidationKey = $validationKey . '.*'; // attributes.custom_field_name.*

            // 更新時は sometimes を基本とする (file_multiple以外)
            if ($isUpdate && $field['type'] !== 'file_multiple') {
                $fieldRules[] = 'sometimes';
            }

            if ($field['required'] ?? false) {
                if ($field['type'] === 'file_multiple') {
                    // file_multiple の必須チェックは少し複雑
                    // 新規作成時: 必須なら 'required', 'array', 'min:1'
                    // 更新時:
                    //   - 新しいファイルがアップロードされる場合: OK
                    //   - 新しいファイルがなく、既存ファイルがあり、削除指定もない場合: OK
                    //   - 新しいファイルがなく、既存ファイルもない(or 全て削除指定)場合: NG (エラー)
                    $fieldRules[] = 'array'; // 常に配列であることを期待
                    if (!$isUpdate) { // 新規作成時
                        $fieldRules[] = 'required';
                        $fieldRules[] = 'min:1'; // 少なくとも1つのファイルが必要
                        $customMessages[$validationKey . '.required'] = $field['label'] . 'は必須です。';
                        $customMessages[$validationKey . '.min'] = $field['label'] . 'には少なくとも1つのファイルをアップロードしてください。';
                    } else { // 更新時
                        // $request->hasFile($validationKey) は個々のファイルではなく、配列の存在を見る
                        $hasNewFiles = $request->hasFile($validationKey) && count((array)$request->file($validationKey)) > 0;
                        $existingFiles = $project->attributes[$fieldName] ?? [];
                        $filesToDelete = $request->input("delete_existing_files.{$fieldName}", []);
                        $remainingExistingFiles = array_udiff($existingFiles, $filesToDelete, function ($a, $b) {
                            // $a, $b はファイル情報配列またはパス文字列の可能性がある
                            $pathA = is_array($a) ? ($a['path'] ?? null) : $a;
                            $pathB = is_array($b) ? ($b['path'] ?? null) : $b;
                            return strcmp((string)$pathA, (string)$pathB);
                        });

                        if (!$hasNewFiles && empty($remainingExistingFiles)) {
                            $fieldRules[] = 'required'; // 新規ファイルも残存ファイルもなければ必須エラー
                            $fieldRules[] = 'min:1';
                            $customMessages[$validationKey . '.required'] = $field['label'] . 'は必須です。';
                            $customMessages[$validationKey . '.min'] = $field['label'] . 'には少なくとも1つのファイルを指定または保持してください。';
                        }
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $fieldRules[] = 'accepted'; // チェックボックスが必須の場合
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
                if ($field['type'] === 'file_multiple') {
                    $fieldRules[] = 'array'; // nullableでも配列形式を期待
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
                    $fieldRules[] = 'string'; // より厳密なバリデーションも可能だが、今回はstring
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
                    // 必須の場合、かつ選択肢が定義されている場合、その選択肢内であることを検証
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
                    // 'accepted' または 'nullable' で処理済み
                    break;
                case 'file_multiple':
                    // 各ファイルに対するバリデーションルール
                    // $request->file($validationKey) は UploadedFile の配列または null
                    if ($request->hasFile($validationKey) && is_array($request->file($validationKey))) {
                        $rules[$arrayValidationKey] = 'file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,txt|max:10240'; // 各10MB
                    }
                    $attributeNames[$arrayValidationKey] = $field['label'] . 'の各ファイル';
                    $rules[$validationKey] = implode('|', array_unique($fieldRules)); // 配列自体のルール
                    continue 2; // switchを抜けて次のforeachへ
            }

            if (!empty($fieldRules)) {
                // 更新時で、既に 'sometimes' が先頭にない場合に追加
                if ($isUpdate && ($fieldRules[0] ?? '') !== 'sometimes' && $field['type'] !== 'file_multiple' && $field['type'] !== 'checkbox') {
                    array_unshift($fieldRules, 'sometimes');
                }
                $rules[$validationKey] = implode('|', array_unique($fieldRules));
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

        $project = new Project();
        $project->fill($dedicatedData);
        $project->form_definitions = $activeGlobalFieldDefinitions; // プロジェクト作成時の定義を保存
        // $project->attributes はファイル処理後に設定するため、ここではまだ保存しない
        $project->save(); // ProjectモデルのLogsActivityが発火 (createdイベント)

        $customAttributesValues = [];
        $submittedAttributes = $request->input('attributes', []);
        $uploadedFileLogDetails = []; // ★ ログ用のファイル詳細情報を格納する配列

        foreach ($activeGlobalFieldDefinitions as $field) {
            $fieldName = $field['name'];
            if ($field['type'] === 'checkbox') {
                $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
            } elseif ($field['type'] === 'file_multiple') {
                $fileInputKey = 'attributes.' . $fieldName; // ★ 正しいキー名
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
                        // ★ ログ用の情報を収集
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
                $customAttributesValues[$fieldName] = null; // 値がない場合はnullを設定
            }
        }

        $project->attributes = $customAttributesValues;
        // ★ プロジェクトの attributes が更新されるため、再度保存イベントがトリガーされる可能性がある。
        // ★ これにより Project モデルの LogsActivity が再度発火する。
        // ★ これを避けたい場合は、イベントを発火させずに更新する $project->updateQuietly(['attributes' => $customAttributesValues]); を使うか、
        // ★ attributes の更新を最初の save() に含める（ファイル処理を先に行う）必要がある。
        // ★ 今回は、ファイル処理後に attributes を設定し、再度 save() する。
        // ★ これにより、最初の作成ログと、attributes（ファイル情報含む）の更新ログが記録される。
        $project->save();


        // ★ ファイルアップロード操作のログを手動で記録
        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project) // 操作対象はプロジェクト
                    ->withProperties($logDetail) // ファイルごとの詳細情報をプロパティに
                    ->log("プロジェクト「{$project->title}」のカスタム項目「{$logDetail['field_label']}」にファイル「{$logDetail['original_name']}」がアップロードされました。");
            }
        }


        if ($request->filled('external_submission_id_on_creation')) {
            $externalSubmission = ExternalProjectSubmission::find($request->input('external_submission_id_on_creation'));
            if ($externalSubmission && $externalSubmission->status === 'new') {
                $externalSubmission->status = 'processed';
                $externalSubmission->processed_by_user_id = auth()->id();
                $externalSubmission->processed_at = now();
                $externalSubmission->save(); // ExternalProjectSubmissionモデルにもLogsActivityがあれば発火
                // ★ 外部申請処理のログ
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($externalSubmission)
                    ->log("外部申請 ID:{$externalSubmission->id} が処理されました。");
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
        $currentCustomFieldDefinitions = $this->getProjectCustomFieldDefinitions($project); // ★ プロジェクト固有の定義を取得
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

        // カスタム属性の処理
        $customAttributesValues = $project->attributes ?? []; // 既存のattributesを取得
        $submittedAttributes = $request->input('attributes', []);
        $uploadedFileLogDetails = []; // ★ ログ用の新規アップロードファイル詳細
        $deletedFileLogDetails = [];  // ★ ログ用の削除ファイル詳細

        foreach ($currentCustomFieldDefinitions as $field) {
            $fieldName = $field['name'];
            $fieldLabel = $field['label']; // ★ ログ用にラベルを取得

            if ($field['type'] === 'checkbox') {
                // チェックボックスは常に値を送信するため、存在すればtrue、なければfalse
                $customAttributesValues[$fieldName] = isset($submittedAttributes[$fieldName]) && filter_var($submittedAttributes[$fieldName], FILTER_VALIDATE_BOOLEAN);
            } elseif ($field['type'] === 'file_multiple') {
                $fileInputKey = 'attributes.' . $fieldName;
                $newlyUploadedFileMeta = [];

                // 新規ファイルのアップロード処理
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
                        // ★ ログ用の情報を収集 (新規アップロード)
                        $uploadedFileLogDetails[] = [
                            'field_label' => $fieldLabel,
                            'field_name' => $fieldName,
                            'original_name' => $fileMetaData['original_name'],
                            'path' => $fileMetaData['path'],
                            'size' => $fileMetaData['size'],
                        ];
                    }
                }

                // 既存ファイルの削除処理
                $existingFilesMeta = $customAttributesValues[$fieldName] ?? [];
                $keptFilesMeta = [];
                // "delete_existing_files[カスタムフィールド名][]" の形式で削除対象のパスが送信される想定
                $filesToDeletePathsForField = $request->input("delete_existing_files.{$fieldName}", []);

                if (is_array($existingFilesMeta)) {
                    foreach ($existingFilesMeta as $fileMeta) {
                        $existingFilePath = is_array($fileMeta) ? ($fileMeta['path'] ?? null) : $fileMeta;
                        $existingOriginalName = is_array($fileMeta) ? ($fileMeta['original_name'] ?? basename((string)$existingFilePath)) : basename((string)$existingFilePath);

                        if ($existingFilePath && in_array($existingFilePath, $filesToDeletePathsForField)) {
                            Storage::disk('public')->delete($existingFilePath);
                            // ★ ログ用の情報を収集 (削除)
                            $deletedFileLogDetails[] = [
                                'field_label' => $fieldLabel,
                                'field_name' => $fieldName,
                                'original_name' => $existingOriginalName,
                                'path' => $existingFilePath,
                            ];
                        } else {
                            $keptFilesMeta[] = $fileMeta; // 保持するファイル
                        }
                    }
                }
                // 保持するファイルと新規アップロードファイルをマージ
                $customAttributesValues[$fieldName] = array_merge($keptFilesMeta, $newlyUploadedFileMeta);
            } elseif (Arr::has($submittedAttributes, $fieldName)) { // inputにキーが存在する場合のみ更新 (nullも含む)
                $customAttributesValues[$fieldName] = Arr::get($submittedAttributes, $fieldName);
            }
            // ★ Arr::has でチェックしない場合、フォームに存在しないフィールドは更新対象外となる。
            // ★ もし、フォームに項目がなくても常に全ての定義済みフィールドを attributes に含めたい場合は、
            // ★ old() や $project->attributes から値を取得し、 $customAttributesValues[$fieldName] = ...; のように
            // ★ 常に値を設定する必要がある。現状は送信された値のみを更新する形。
        }

        $project->fill($dedicatedDataToUpdate);
        $project->attributes = $customAttributesValues; // ここで更新された属性をセット
        $project->save(); // これによりProjectモデルのLogsActivityが発火 (updatedイベント)

        // ★ 新規ファイルアップロード操作のログを手動で記録
        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project)
                    ->withProperties($logDetail)
                    ->log("プロジェクト「{$project->title}」のカスタム項目「{$logDetail['field_label']}」にファイル「{$logDetail['original_name']}」がアップロードされました。");
            }
        }
        // ★ 既存ファイル削除操作のログを手動で記録
        if (!empty($deletedFileLogDetails)) {
            foreach ($deletedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project)
                    ->withProperties($logDetail)
                    ->log("プロジェクト「{$project->title}」のカスタム項目「{$logDetail['field_label']}」からファイル「{$logDetail['original_name']}」が削除されました。");
            }
        }

        return redirect()->route('projects.show', $project)->with('success', '衣装案件が更新されました。');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $deletedFileLogDetails = []; // ★ ログ用の削除ファイル詳細を格納する配列

        // プロジェクトに紐づくカスタムフィールドのファイルを削除
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
                            } elseif (is_string($fileInfo)) { // 古い形式（ファイルパス文字列の配列）の場合
                                $filePath = $fileInfo;
                                $originalName = basename($filePath);
                            }

                            if ($filePath && Storage::disk('public')->exists($filePath)) {
                                Storage::disk('public')->delete($filePath);
                                // ★ ログ用の情報を収集 (削除)
                                $deletedFileLogDetails[] = [
                                    'field_label' => $field['label'] ?? $field['name'],
                                    'field_name' => $field['name'],
                                    'original_name' => $originalName,
                                    'path' => $filePath,
                                ];
                            }
                        }
                    }
                    // カスタムフィールドごとのディレクトリも削除 (任意、ファイルが全て削除された場合)
                    // $directory = "project_files/{$project->id}/{$field['name']}";
                    // if (empty(Storage::disk('public')->allFiles($directory)) && empty(Storage::disk('public')->allDirectories($directory))) {
                    //     Storage::disk('public')->deleteDirectory($directory);
                    // }
                }
            }
            // プロジェクトIDごとのルートディレクトリを削除 (関連ファイルが全て削除された後)
            // $projectFileDirectory = "project_files/{$project->id}";
            // if (empty(Storage::disk('public')->allFiles($projectFileDirectory)) && empty(Storage::disk('public')->allDirectories($projectFileDirectory))) {
            //     Storage::disk('public')->deleteDirectory($projectFileDirectory);
            // }
        }

        // 関連データの削除 (Task, Character など)
        // これらは各モデルの deleted イベントや Observer でログが記録される想定
        $project->tasks()->delete(); // TaskモデルのLogsActivityが発火
        foreach ($project->characters as $character) {
            $character->measurements()->delete(); // MeasurementモデルのLogsActivityが発火
            $character->materials()->delete();  // MaterialモデルのLogsActivityが発火
            $character->costs()->delete();      // CostモデルのLogsActivityが発火
            $character->tasks()->delete();      // TaskモデルのLogsActivityが発火 (キャラクター紐付きタスク)
            $character->delete();               // CharacterモデルのLogsActivityが発火
        }

        // ★ プロジェクト削除自体のログは Project モデルの LogsActivity によって 'deleted' イベントとして記録される

        // ★ カスタムフィールドのファイル削除操作のログを手動で記録
        if (!empty($deletedFileLogDetails)) {
            foreach ($deletedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($project) // 操作対象は削除されるプロジェクト
                    ->withProperties(array_merge($logDetail, ['action' => 'file_deleted_during_project_deletion']))
                    ->log("プロジェクト「{$project->title}」削除に伴い、カスタム項目「{$logDetail['field_label']}」からファイル「{$logDetail['original_name']}」が削除されました。");
            }
        }

        $project->delete(); // プロジェクトを削除

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

<?php

namespace App\Http\Controllers;

use App\Models\ExternalProjectSubmission;
use App\Models\FormFieldDefinition;
use App\Models\FormFieldCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Storage; // Storageファサードをインポート
use App\Models\ManagedContact; // ★ ManagedContactモデルをuse
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExternalFormCompletionMail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ExternalFormController extends Controller
{
    // create, store, thanks, index, updateStatus メソッドは変更なし (前回の回答を参照)
    public function create()
    {
        $customFormFields = FormFieldDefinition::where('is_enabled', true)
            ->where('category', 'project')
            ->orderBy('order')
            ->orderBy('label')
            ->get()
            ->map(function ($field) {
                $optionsString = '';
                if (is_array($field->options)) {
                    $optionsParts = [];
                    foreach ($field->options as $value => $label) {
                        $optionsParts[] = $value . ':' . $label;
                    }
                    $optionsString = implode(',', $optionsParts);
                } elseif (is_string($field->options)) {
                    $decoded = json_decode($field->options, true);
                    if (is_array($decoded)) {
                        $optionsParts = [];
                        foreach ($decoded as $value => $label) {
                            $optionsParts[] = $value . ':' . $label;
                        }
                        $optionsString = implode(',', $optionsParts);
                    } else {
                        $optionsString = $field->options;
                    }
                }

                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'options_string' => $optionsString,
                    'placeholder' => $field->placeholder,
                    'required' => $field->is_required,
                    'maxlength' => $field->max_length,
                ];
            })
            ->all();

        return view('external_form.form', compact('customFormFields'));
    }

    public function thanks()
    {
        return view('external_form.thanks');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ExternalProjectSubmission::class);

        $query = ExternalProjectSubmission::with('processedBy');

        if ($request->filled('submitter_name')) {
            $query->where('submitter_name', 'like', '%' . $request->input('submitter_name') . '%');
        }
        if ($request->filled('submitter_email')) {
            $query->where('submitter_email', 'like', '%' . $request->input('submitter_email') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(15);
        $statusOptions = ExternalProjectSubmission::STATUS_OPTIONS;

        return view('admin.external_submissions.index', compact('submissions', 'statusOptions'));
    }

    /**
     * Display the specified external project submission.
     *
     * @param  \App\Models\ExternalProjectSubmission  $submission
     * @return \Illuminate\View\View
     */
    public function show(ExternalProjectSubmission $submission)
    {
        $this->authorize('view', $submission);
        $submission->load('processedBy');

        // フォームカテゴリを取得
        $formCategory = $submission->form_category;

        $displayData = [];
        $fileFields = []; // ファイルタイプのフィールドを別途格納

        if ($formCategory) {
            // 特定のフォームカテゴリに関連するフィールド定義のみを取得
            $fieldDefinitions = \App\Models\FormFieldDefinition::where('is_enabled', true)
                ->where('category', $formCategory->name) // フォームカテゴリ名と一致するcategoryのフィールドを取得
                ->orderBy('order')
                ->orderBy('label')
                ->get();

            // フィールド定義が見つからない場合は、submitted_dataから動的に構築
            if ($fieldDefinitions->isEmpty()) {
                // submitted_dataのキーから動的にフィールドを構築（システムフィールドは除外）
                foreach ($submission->submitted_data as $key => $value) {
                    // システムフィールドをスキップ
                    if (in_array($key, ['form_category_id', 'form_category_name', '_token', 'submitter_name', 'submitter_email', 'submitter_notes'])) {
                        continue;
                    }

                    // ファイルフィールドかどうかを判定
                    $isFileField = is_array($value) && !empty($value) && isset($value[0]['path']);

                    if ($isFileField) {
                        $fileFields[] = [
                            'label' => ucfirst(str_replace('_', ' ', $key)),
                            'name' => $key,
                            'value' => $value,
                            'type' => 'file_multiple',
                        ];
                    } else {
                        $displayData[] = [
                            'label' => ucfirst(str_replace('_', ' ', $key)),
                            'name' => $key,
                            'value' => $value,
                            'type' => 'text',
                            'options' => [],
                        ];
                    }
                }
            } else {
                // フィールド定義が見つかった場合は定義に基づいて表示
                foreach ($fieldDefinitions as $definition) {
                    $fieldName = $definition->name;
                    $fieldValue = $submission->submitted_data[$fieldName] ?? null;

                    if (in_array($definition->type, ['file', 'file_multiple'])) { // ★ 'file'タイプも追加
                        $fileFields[] = [
                            'label' => $definition->label,
                            'name' => $fieldName,
                            'value' => $fieldValue,
                            'type' => $definition->type,
                        ];
                    } else {
                        $displayData[] = [
                            'label' => $definition->label,
                            'name' => $fieldName,
                            'value' => $fieldValue,
                            'type' => $definition->type,
                            'options' => $definition->options ?? [],
                        ];
                    }
                }
            }
        } else {
            // フォームカテゴリが見つからない場合は、submitted_dataから全て表示
            foreach ($submission->submitted_data as $key => $value) {
                // システムフィールドをスキップ
                if (in_array($key, ['form_category_id', 'form_category_name', '_token', 'submitter_name', 'submitter_email', 'submitter_notes'])) {
                    continue;
                }

                // ファイルフィールドかどうかを判定
                $isFileField = is_array($value) && !empty($value) && isset($value[0]['path']);

                if ($isFileField) {
                    $fileFields[] = [
                        'label' => ucfirst(str_replace('_', ' ', $key)),
                        'name' => $key,
                        'value' => $value,
                        'type' => 'file_multiple',
                    ];
                } else {
                    $displayData[] = [
                        'label' => ucfirst(str_replace('_', ' ', $key)),
                        'name' => $key,
                        'value' => $value,
                        'type' => 'text',
                        'options' => [],
                    ];
                }
            }
        }

        $statusOptions = \App\Models\ExternalProjectSubmission::STATUS_OPTIONS;
        return view('admin.external_submissions.show', compact('submission', 'displayData', 'fileFields', 'statusOptions', 'formCategory'));
    }


    public function updateStatus(Request $request, ExternalProjectSubmission $submission)
    {
        $this->authorize('update', $submission); // 既存の権限チェック

        $statusOptions = ExternalProjectSubmission::STATUS_OPTIONS; // ログメッセージ用に取得

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($statusOptions))],
            // 必要に応じて、在庫申請の manager_notes のように他のフィールドもバリデーションに追加できます。
            // 例: 'manager_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $submission->status;
        $newStatus = $validated['status'];

        $submission->status = $newStatus;

        // ステータスに応じて処理者情報と日時を更新
        if (in_array($newStatus, ['processed', 'rejected', 'on_hold'])) {
            // 既に同じ担当者で同じステータスにしようとしている場合などは、日時や担当者を更新しない条件も考慮できます。
            // ここではシンプルに、これらのステータスになったら担当者と日時を記録します。
            $submission->processed_by_user_id = Auth::id();
            $submission->processed_at = now();
        } elseif ($newStatus === 'new' || $newStatus === 'in_progress') {
            // 「新規」や「対応中」に戻す場合は、処理者情報をクリア
            $submission->processed_by_user_id = null;
            $submission->processed_at = null;
        }

        // 管理者メモを更新する場合 (フォームに追加した場合)
        // if ($request->filled('manager_notes')) {
        //     $submission->manager_notes = $request->input('manager_notes');
        // }

        $submission->save();

        // 操作ログの記録 (在庫申請の例を参考に)
        activity()
            ->causedBy(Auth::user())
            ->performedOn($submission)
            ->withProperties(['old_status' => $oldStatus, 'new_status' => $newStatus])
            ->log("外部案件依頼 (ID: {$submission->id}) のステータスが「" . ($statusOptions[$oldStatus] ?? $oldStatus) . "」から「" . ($statusOptions[$newStatus] ?? $newStatus) . "」に変更されました。");

        return redirect()->route('admin.external-submissions.show', $submission)
            ->with('success', '依頼ステータスを更新しました。');
    }

    /**
     * 外部向けの管理連絡先登録フォームを表示します。
     */
    public function createContact()
    {
        return view('external.contact.create');
    }

    /**
     * 外部から送信された管理連絡先情報を保存します。
     */
    public function storeContact(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255|unique:managed_contacts,email',
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'phone_number' => 'nullable|string|max:30',
            'fax_number' => 'nullable|string|max:30',
            'url' => 'nullable|string|url|max:255',
            'representative_name' => 'nullable|string|max:255',
            'establishment_date' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // デフォルト値と登録元情報を設定
        $validatedData['status'] = 'active'; // デフォルトで「有効」ステータスにする
        $validatedData['source_info'] = '手入力: ' . Auth::user()->name;

        ManagedContact::create($validatedData);

        return redirect()->route('external-contact.create')->with('success', '連絡先を登録しました。');;
    }

    /**
     * メールアドレスの重複をAJAXでチェック
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['exists' => false, 'message' => '無効なメールアドレスです。']);
        }

        $exists = ManagedContact::where('email', $request->input('email'))->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * 動的外部フォーム表示
     */
    public function showDynamicForm($slug)
    {
        $formCategory = \App\Models\FormFieldCategory::where('slug', $slug)
            ->where('is_external_form', true)
            ->where('is_enabled', true)
            ->firstOrFail();

        $customFormFields = FormFieldDefinition::where('is_enabled', true)
            ->where('category', $formCategory->name)
            ->orderBy('order')
            ->orderBy('label')
            ->get()
            ->map(function ($field) {
                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'is_required' => $field->is_required,
                    'placeholder' => $field->placeholder,
                    'help_text' => $field->help_text,
                    'options' => $field->options, // ★ optionsをそのまま渡す
                    'min_selections' => $field->min_selections, // ★ min_selections を追加
                    'max_selections' => $field->max_selections, // ★ max_selections を追加
                ];
            });

        return view('external.dynamic_form', compact('customFormFields', 'formCategory'));
    }

    /**
     * 動的外部フォーム送信処理
     */
    public function storeDynamicForm(Request $request, $slug)
    {
        // 「修正する」ボタンが押された場合、入力画面に戻る
        if ($request->input('action') === 'back') {
            // ★ 修正点: セッションから入力データを取得し、ファイル情報も一緒にリダイレクト先に渡す
            $formInput = $request->session()->get('form_input');
            return redirect()->route('external-form.show', $slug)
                ->withInput($formInput['validated_data'] ?? [])
                ->with('existing_temp_files', $formInput['temp_file_paths'] ?? []);
        }

        // セッションからフォーム入力データを取得
        $formInput = $request->session()->get('form_input');
        if (!$formInput) {
            // セッション切れの場合、フォーム入力画面にリダイレクト
            return redirect()->route('external-form.show', $slug)->withErrors(['session_expired' => 'セッションが切れました。もう一度入力してください。']);
        }

        $formCategory = \App\Models\FormFieldCategory::where('slug', $slug)
            ->where('is_external_form', true)
            ->where('is_enabled', true)
            ->firstOrFail();

        $customFormFields = \App\Models\FormFieldDefinition::where('is_enabled', true)
            ->where('category', $formCategory->name)
            ->orderBy('order')
            ->get();

        $validatedData = $formInput['validated_data'];
        $tempFilePaths = $formInput['temp_file_paths'];

        // データベースに保存するカスタムフィールドのデータを準備
        $customFieldData = [];
        foreach ($customFormFields as $field) {
            $dbFieldName = $field->name;
            $formFieldName = 'custom_' . $field->name;

            // ファイルフィールドの処理
            if (isset($tempFilePaths[$dbFieldName])) {
                $permanentFilePaths = [];
                foreach ($tempFilePaths[$dbFieldName] as $fileInfo) {
                    $tempPath = $fileInfo['path'];
                    if (Storage::disk('local')->exists($tempPath)) {
                        $permanentPath = 'external_submissions/' . basename($tempPath);
                        Storage::disk('public')->put($permanentPath, Storage::disk('local')->get($tempPath));
                        Storage::disk('local')->delete($tempPath);

                        $permanentFilePaths[] = [
                            'path' => $permanentPath,
                            'original_name' => $fileInfo['original_name'],
                            'size' => $fileInfo['size'],
                            'mime_type' => $fileInfo['mime_type'] ?? null,
                        ];
                    }
                }
                $customFieldData[$dbFieldName] = $field->type === 'file' ? ($permanentFilePaths[0] ?? null) : $permanentFilePaths;
            }
            // ファイル以外のフィールドの処理
            else {
                $customFieldData[$dbFieldName] = $validatedData[$formFieldName] ?? null;
            }
        }

        // 申請データをデータベースに保存
        $submission = \App\Models\ExternalProjectSubmission::create([
            'submitter_name' => $validatedData['submitter_name'],
            'submitter_email' => $validatedData['submitter_email'],
            'submitter_notes' => $validatedData['submitter_notes'],
            'submitted_data' => $customFieldData,
            'status' => 'new',
            'form_category_id' => $formCategory->id,      // ★ フォームカテゴリIDを追加
            'form_category_name' => $formCategory->name,  // ★ フォームカテゴリ名を追加
        ]);

        // 処理が完了したので、セッションデータを削除
        $request->session()->forget('form_input');

        // --- メール送信処理 ---
        // 送信完了メールを送信
        if ($formCategory->send_completion_email) {
            try {
                $emailCustomFields = [];
                foreach ($customFormFields as $field) {
                    // 保存されたデータから値を取得
                    $fieldValue = $customFieldData[$field->name] ?? null;

                    // 値が空でないフィールドのみメールに記載
                    if (!empty($fieldValue)) {
                        $emailCustomFields[] = [
                            'label' => $field->label, // ★ 表示ラベルを取得
                            'value' => $fieldValue,
                            'type'  => $field->type, // ファイルなどのタイプ判別用にタイプも渡す
                        ];
                    }
                }

                $emailData = [
                    'name' => $validatedData['submitter_name'],
                    'email' => $validatedData['submitter_email'],
                    'notes' => $validatedData['submitter_notes'] ?? '',
                    'custom_fields' => $emailCustomFields // ★ 新しい配列を渡す
                ];

                Mail::to($validatedData['submitter_email'])
                    ->send(new \App\Mail\ExternalFormCompletionMail(
                        $formCategory,
                        $emailData,
                        $validatedData['submitter_email'],
                        $validatedData['submitter_name']
                    ));
            } catch (\Exception $e) {
                \Log::error('外部フォーム送信完了メールの送信に失敗しました: ' . $e->getMessage(), [
                    'submitter_email' => $validatedData['submitter_email'],
                    'form_category' => $formCategory->name
                ]);
            }
        }

        // 管理者への通知メール送信
        if ($formCategory->notification_emails) {
            try {
                \Mail::to($formCategory->notification_emails)->send(
                    new \App\Mail\ExternalFormSubmissionMail($submission, $formCategory)
                );
            } catch (\Exception $e) {
                \Log::error('外部フォーム通知メール送信エラー: ' . $e->getMessage());
            }
        }
        // --- メール送信処理ここまで ---


        // 完了ページへリダイレクト
        return redirect()->route('external-form.thanks', $slug)
            ->with('success', 'フォームを送信しました。');
    }

    /**
     * 動的外部フォーム完了画面
     */
    public function showDynamicThanks($slug)
    {
        $formCategory = \App\Models\FormFieldCategory::where('slug', $slug)
            ->where('is_external_form', true)
            ->where('is_enabled', true)
            ->firstOrFail();

        return view('external.form_thanks', compact('formCategory'));
    }

    /**
     * 動的外部フォームの確認ページ表示
     */
    public function confirmDynamicForm(Request $request, $slug)
    {
        $formCategory = FormFieldCategory::where('slug', $slug)
            ->where('is_external_form', true)
            ->where('is_enabled', true)
            ->firstOrFail();

        $customFormFields = FormFieldDefinition::where('is_enabled', true)
            ->where('category', $formCategory->name)
            ->orderBy('order')
            ->get();

        list($rules, $customFieldNames, $customAttributes) = $this->buildValidationRules($customFormFields);
        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validatedData = $validator->validated();
        $existingTempFiles = [];
        if ($request->has('existing_temp_files')) {
            foreach ($request->input('existing_temp_files', []) as $fieldName => $jsonString) {
                $decoded = json_decode($jsonString, true);
                if (is_array($decoded)) {
                    $existingTempFiles[$fieldName] = $decoded;
                }
            }
        }

        $displayData = [];
        $tempFilePaths = $existingTempFiles;

        foreach ($customFormFields as $field) {
            $fieldName = 'custom_' . $field->name;
            $dbFieldName = $field->name;

            if (in_array($field->type, ['file', 'file_multiple'])) {
                $tempFilePaths[$dbFieldName] = $tempFilePaths[$dbFieldName] ?? [];

                if ($request->hasFile($fieldName)) {
                    $files = is_array($request->file($fieldName)) ? $request->file($fieldName) : [$request->file($fieldName)];

                    foreach ($files as $file) {
                        $tempPath = $file->store('temp_uploads', 'local');

                        $fileInfo = [
                            'path' => $tempPath,
                            'original_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime_type' => $file->getClientMimeType(), // ★ mime_typeを追加
                        ];

                        if (str_starts_with($fileInfo['mime_type'], 'image/')) {
                            try {
                                $fileContents = File::get(storage_path('app/' . $tempPath));
                                $fileInfo['preview_src'] = 'data:' . $fileInfo['mime_type'] . ';base64,' . base64_encode($fileContents);
                            } catch (\Exception $e) {
                                Log::error('Image preview generation failed: ' . $e->getMessage());
                            }
                        }
                        $tempFilePaths[$dbFieldName][] = $fileInfo;
                    }
                }
                $displayData[$dbFieldName] = $tempFilePaths[$dbFieldName];
            } elseif ($field->type === 'image_select') {
                $selectedValues = Arr::get($validatedData, $fieldName, []);
                // $field->optionsはモデルの$castsにより既に配列になっているはず
                $options = $field->options ?? [];

                $displayImages = [];
                if (is_array($selectedValues)) {
                    foreach ($selectedValues as $selectedValue) {
                        // $options配列からキー（選択値）に一致するURLを取得
                        if (isset($options[$selectedValue])) {
                            $displayImages[] = [
                                'url' => $options[$selectedValue],
                                'label' => $selectedValue
                            ];
                        }
                    }
                }
                $displayData[$dbFieldName] = $displayImages;
            } else {
                $displayData[$dbFieldName] = $request->input($fieldName);
            }
        }

        foreach ($customFormFields as $field) {
            if (in_array($field->type, ['file', 'file_multiple'])) {
                unset($validatedData['custom_' . $field->name]);
            }
        }

        session(['form_input' => [
            'validated_data' => $validatedData,
            'display_data' => $displayData,
            'temp_file_paths' => $tempFilePaths
        ]]);

        return view('external.confirm_dynamic', compact('formCategory', 'customFormFields', 'validatedData', 'displayData'));
    }


    /**
     * ★【ヘルパー】バリデーションルール構築（共通化のため）
     */
    private function buildValidationRules($customFormFields)
    {
        $rules = [
            'submitter_name' => 'required|string|max:255',
            'submitter_email' => 'required|email|max:255',
            'submitter_notes' => 'nullable|string|max:2000',
        ];
        $customFieldNames = [];
        $customAttributes = [
            'submitter_name' => 'お名前',
            'submitter_email' => 'メールアドレス',
            'submitter_notes' => '備考・ご要望など',
        ];
        foreach ($customFormFields as $field) {
            $fieldName = 'custom_' . $field->name;
            $fieldRules = [];

            $customAttributes[$fieldName] = $field->label;

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field->type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'text':
                case 'textarea':
                case 'tel':
                    $fieldRules[] = 'string';
                    if ($field->max_length) $fieldRules[] = 'max:' . $field->max_length;
                    break;
                case 'select':
                case 'radio':
                    if (!empty($field->options)) {
                        // オプションがJSON文字列で保存されていることを想定してデコードを試みる
                        // (is_arrayの場合はそのまま使用)
                        $optionsArray = is_array($field->options) ? $field->options : json_decode($field->options, true);

                        // 正しく配列に変換でき、その配列が空でないことを確認
                        if (is_array($optionsArray) && !empty($optionsArray)) {
                            // 配列のキーが、バリデーションで許可される値（value）となる
                            // 例: {"plan_a": "プランA"} -> 'plan_a' が許可値
                            $validValues = array_keys($optionsArray);

                            // 許可される値のいずれかであることを検証するルールを追加
                            $fieldRules[] = \Illuminate\Validation\Rule::in($validValues);
                        }
                    }
                    break;
                case 'checkbox':
                    if ($field->is_required) $fieldRules[] = 'array|min:1';
                    else $fieldRules[] = 'array';
                    break;
                case 'image_select':
                    $fieldRules[] = 'array';
                    if ($field->is_required) {
                        $fieldRules[] = 'required';
                        $fieldRules[] = 'min:1';
                        $customAttributes[$fieldName] = $field->label . 'は最低1つ選択してください。';
                    }
                    if (isset($field->min_selections) && $field->min_selections > 0) {
                        $fieldRules[] = 'min:' . $field->min_selections;
                    }
                    if (isset($field->max_selections) && $field->max_selections > 0) {
                        $fieldRules[] = 'max:' . $field->max_selections;
                    }
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    $fieldRules[] = 'max:10240'; // 10MB
                    break;
                case 'file_multiple':
                    if ($field->is_required) $fieldRules[] = 'array|min:1';
                    else $fieldRules[] = 'array';
                    $rules[$fieldName . '.*'] = ['file', 'max:10240'];
                    break;
            }

            $rules[$fieldName] = $fieldRules;
            $customFieldNames[] = $fieldName;
        }

        return [$rules, $customFieldNames, $customAttributes];
    }
}

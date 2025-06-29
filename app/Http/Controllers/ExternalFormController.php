<?php

namespace App\Http\Controllers;

use App\Models\ExternalProjectSubmission;
use App\Models\FormFieldDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Storage; // Storageファサードをインポート
use App\Models\ManagedContact; // ★ ManagedContactモデルをuse
use Illuminate\Http\JsonResponse;

class ExternalFormController extends Controller
{
    // create, store, thanks, index, updateStatus メソッドは変更なし (前回の回答を参照)
    public function create()
    {
        $customFormFields = FormFieldDefinition::where('is_enabled', true)
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

    public function store(Request $request)
    {
        $customFieldDefinitions = FormFieldDefinition::where('is_enabled', true)->get();
        $validationRules = [
            'submitter_name' => 'required|string|max:255',
            'submitter_email' => 'required|email|max:255',
            'submitter_notes' => 'nullable|string|max:5000',
        ];
        $validationAttributes = [
            'submitter_name' => 'お名前',
            'submitter_email' => 'メールアドレス',
            'submitter_notes' => '備考・ご要望など',
        ];
        $customMessages = [];

        // 許可するMIMEタイプと言語ファイル用の案件依頼メッセージ
        $allowedMimes = 'jpg,jpeg,png,gif,txt,pdf';
        $allowedMimesMessage = 'アップロードできるファイル形式は画像 (JPG, JPEG, PNG, GIF), テキスト (.txt), PDF (.pdf) のみです。';


        foreach ($customFieldDefinitions as $field) {
            $fieldName = 'custom_fields.' . $field->name;
            $fieldRules = [];

            if ($field->is_required) {
                if ($field->type === 'file_multiple') {
                    $fieldRules[] = 'required';
                    $fieldRules[] = 'array';
                    $fieldRules[] = 'min:1';
                    // 各ファイルへのバリデーション (MIMEタイプと言語ファイルメッセージを適用)
                    $validationRules[$fieldName . '.*'] = 'file|mimes:' . $allowedMimes . '|max:10240'; // 10MB per file
                    $validationAttributes[$fieldName . '.*'] = $field->label . 'の各ファイル';
                    $customMessages[$fieldName . '.*.mimes'] = $field->label . ': ' . $allowedMimesMessage;
                } elseif ($field->type === 'checkbox') {
                    $fieldRules[] = 'accepted';
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
                if ($field->type === 'file_multiple') {
                    $fieldRules[] = 'array';
                    // 各ファイルへのバリデーション (MIMEタイプと言語ファイルメッセージを適用)
                    $validationRules[$fieldName . '.*'] = 'nullable|file|mimes:' . $allowedMimes . '|max:10240';
                    $validationAttributes[$fieldName . '.*'] = $field->label . 'の各ファイル';
                    $customMessages[$fieldName . '.*.mimes'] = $field->label . ': ' . $allowedMimesMessage;
                }
            }

            switch ($field->type) {
                case 'text':
                case 'textarea':
                case 'color':
                    $fieldRules[] = 'string';
                    if ($field->max_length) {
                        $fieldRules[] = 'max:' . $field->max_length;
                    }
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    if ($field->max_length) {
                        $fieldRules[] = 'max:' . $field->max_length;
                    }
                    break;
                case 'tel':
                    $fieldRules[] = 'string';
                    if ($field->max_length) {
                        $fieldRules[] = 'max:' . $field->max_length;
                    }
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    if ($field->max_length) {
                        $fieldRules[] = 'max:' . $field->max_length;
                    }
                    break;
                case 'select':
                case 'radio':
                    if ($field->options) {
                        $optionsArray = is_array($field->options) ? $field->options : json_decode($field->options, true);
                        if (is_array($optionsArray)) {
                            $validValues = array_keys($optionsArray);
                            $fieldRules[] = Rule::in($validValues);
                        }
                    }
                    break;
            }
            if (!empty($fieldRules)) {
                $validationRules[$fieldName] = implode('|', $fieldRules);
            }
            $validationAttributes[$fieldName] = $field->label;
        }

        $validator = Validator::make($request->all(), $validationRules, $customMessages, $validationAttributes);

        if ($validator->fails()) {
            return redirect()->route('external-form.create')
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();
        $submittedCustomFields = $request->input('custom_fields', []);
        $processedCustomData = [];

        foreach ($customFieldDefinitions as $field) {
            $fieldNameInRequest = $field->name;

            if ($field->type === 'file_multiple') {
                if ($request->hasFile('custom_fields.' . $fieldNameInRequest)) {
                    $uploadedFiles = $request->file('custom_fields.' . $fieldNameInRequest);
                    $storedFilePaths = [];
                    foreach ($uploadedFiles as $file) {
                        $directory = 'external_submissions/' . date('Y-m');
                        $path = $file->store($directory, 'public');
                        $storedFilePaths[] = [
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                        ];
                    }
                    $processedCustomData[$fieldNameInRequest] = $storedFilePaths;
                } else {
                    $processedCustomData[$fieldNameInRequest] = [];
                }
            } elseif ($field->type === 'checkbox') {
                $processedCustomData[$fieldNameInRequest] = isset($submittedCustomFields[$fieldNameInRequest]);
            } elseif (isset($submittedCustomFields[$fieldNameInRequest])) {
                $processedCustomData[$fieldNameInRequest] = $submittedCustomFields[$fieldNameInRequest];
            } else {
                $processedCustomData[$fieldNameInRequest] = null;
            }
        }

        ExternalProjectSubmission::create([
            'submitter_name' => $validatedData['submitter_name'],
            'submitter_email' => $validatedData['submitter_email'],
            'submitted_data' => $processedCustomData,
            'submitter_notes' => $validatedData['submitter_notes'] ?? null,
            'status' => 'new',
        ]);

        return redirect()->route('external-form.thanks');
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

        // 全ての有効な案件依頼フィールド定義を取得 (順序も考慮)
        $allFieldDefinitions = FormFieldDefinition::where('is_enabled', true)
            ->orderBy('order')
            ->orderBy('label')
            ->get();

        // 提出されたデータと全フィールド定義をマージ
        $displayData = [];
        $fileFields = []; // ファイルタイプのフィールドを別途格納

        foreach ($allFieldDefinitions as $definition) {
            $fieldName = $definition->name;
            $fieldValue = $submission->submitted_data[$fieldName] ?? null; // submitted_data から値を取得、なければ null

            if ($definition->type === 'file_multiple') {
                $fileFields[] = [
                    'label' => $definition->label,
                    'name' => $fieldName, // name も渡す
                    'value' => $fieldValue, // これはファイルの配列になるはず
                    'type' => $definition->type, // type も渡す
                ];
            } else {
                $displayData[] = [
                    'label' => $definition->label,
                    'name' => $fieldName, // name も渡す
                    'value' => $fieldValue,
                    'type' => $definition->type, // type も渡す
                    'options' => $definition->options_array, // selectやradioの場合の選択肢 (モデルにアクセサ定義が必要)
                ];
            }
        }
        $statusOptions = ExternalProjectSubmission::STATUS_OPTIONS;
        return view('admin.external_submissions.show', compact('submission', 'displayData', 'fileFields', 'statusOptions'));
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
}

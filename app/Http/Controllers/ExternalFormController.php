<?php

namespace App\Http\Controllers;

use App\Models\FormFieldDefinition;
use App\Models\ExternalProjectSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule; // Rule を use

class ExternalFormController extends Controller
{
    // createメソッドは変更なし (ただし、map内で 'options' を 'options_string' に変更した点に注意)

    public function create()
    {
        $customFormFields = FormFieldDefinition::where('is_enabled', true)
            ->orderBy('order')
            ->orderBy('label')
            ->get()
            ->map(function ($def) {
                $optionsString = '';
                if (is_array($def->options)) {
                    $optionsParts = [];
                    foreach ($def->options as $value => $label) {
                        $optionsParts[] = $value . ':' . $label;
                    }
                    $optionsString = implode(',', $optionsParts);
                } elseif (is_string($def->options)) { // もしDBに文字列でoptionsが保存されている場合のフォールバック
                    $optionsString = $def->options;
                }
                return [
                    'name' => $def->name,
                    'label' => $def->label,
                    'type' => $def->type,
                    'options_string' => $optionsString, // ビューでパースしやすいように
                    'placeholder' => $def->placeholder,
                    'required' => $def->is_required,
                    'order' => $def->order,
                    'maxlength' => $def->max_length,
                ];
            })->all();

        return view('external.form', compact('customFormFields'));
    }

    public function store(Request $request)
    {
        $globalDefinitions = FormFieldDefinition::where('is_enabled', true)->get();

        $validationRules = [
            'submitter_name' => 'nullable|string|max:255',
            'submitter_email' => 'nullable|email|max:255',
            'submitter_notes' => 'nullable|string|max:2000',
            'custom_fields' => 'present|array',
        ];
        $attributeNames = [
            'submitter_name' => 'お名前',
            'submitter_email' => 'メールアドレス',
            'submitter_notes' => '備考',
        ];

        foreach ($globalDefinitions as $def) {
            $fieldName = 'custom_fields.' . $def->name;
            $fieldRules = [];
            if ($def->is_required) {
                $fieldRules[] = ($def->type === 'file_multiple' && $request->hasFile($fieldName)) ? 'present' : 'required';
                if ($def->type === 'checkbox') $fieldRules[] = 'accepted';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($def->type) {
                case 'text':
                case 'textarea':
                case 'color':
                    $fieldRules[] = 'string';
                    if ($def->max_length) $fieldRules[] = 'max:' . $def->max_length;
                    break;
                case 'date':
                    $fieldRules[] = 'date_format:Y-m-d';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'tel':
                    $fieldRules[] = 'string';
                    if ($def->max_length) $fieldRules[] = 'max:' . $def->max_length;
                    break; // 簡単なstringバリデーション
                case 'email':
                    $fieldRules[] = 'email';
                    if ($def->max_length) $fieldRules[] = 'max:' . $def->max_length;
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    if ($def->max_length) $fieldRules[] = 'max:' . $def->max_length;
                    break;
                case 'select':
                    if ($def->is_required && $request->input($fieldName) === '') {
                        $validOptions = is_array($def->options) ? array_keys($def->options) : [];
                        if (!empty($validOptions)) {
                            $fieldRules[] = Rule::in($validOptions);
                        }
                    }
                    break;
                case 'file_multiple':
                    // custom_fields.field_name自体が配列であることを期待
                    $validationRules[$fieldName] = $def->is_required ? 'required|array' : 'nullable|array';
                    // 配列の各要素に対するバリデーション
                    $validationRules[$fieldName . '.*'] = 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120'; // 例: 5MBまで
                    $attributeNames[$fieldName . '.*'] = $def->label . 'の各ファイル';
                    // 個別のルールは$fieldRulesには追加しないので、ここではbreak
                    break 2; // switchを抜ける
            }
            if (!empty($fieldRules)) {
                $validationRules[$fieldName] = implode('|', $fieldRules);
            }
            $attributeNames[$fieldName] = $def->label;
        }

        $validator = Validator::make($request->all(), $validationRules, [], $attributeNames);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validatedData = $validator->validated();
        $submittedCustomFields = $validatedData['custom_fields'] ?? [];

        // ファイルアップロード処理（ファイル名を保存する簡易版）
        if ($request->has('custom_fields')) {
            foreach ($request->file('custom_fields', []) as $fieldName => $files) {
                if (!empty($files)) {
                    $fileNames = [];
                    if (is_array($files)) { // file_multiple の場合
                        foreach ($files as $file) {
                            // ここで実際にファイルを保存する場合は $file->store(...) など
                            $fileNames[] = $file->getClientOriginalName(); // 例としてオリジナルファイル名
                        }
                    } else { // 単一ファイルの場合（現在はfile_multipleのみ実装）
                        // $fileNames[] = $files->getClientOriginalName();
                    }
                    if (!empty($fileNames)) {
                        $submittedCustomFields[$fieldName] = $fileNames; // ファイル名（の配列）で上書き
                    }
                }
            }
        }


        ExternalProjectSubmission::create([
            'submitter_name' => $validatedData['submitter_name'] ?? null,
            'submitter_email' => $validatedData['submitter_email'] ?? null,
            'submitter_notes' => $validatedData['submitter_notes'] ?? null,
            'submitted_data' => $submittedCustomFields,
            'status' => 'new',
        ]);

        return redirect()->route('external-form.thanks');
    }

    public function thanks()
    {
        return view('external.thanks');
    }
}

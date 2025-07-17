<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormFieldDefinition;
use App\Models\FormFieldCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // ★ 追加

class FormFieldDefinitionController extends Controller
{
    public function __construct()
    {
        // $this->authorizeResource(FormFieldDefinition::class, 'form_field_definition');
    }

    public function index(Request $request)
    {
        //$this->authorize('viewAny', FormFieldDefinition::class);

        $availableCategories = FormFieldCategory::enabled()
            ->excludeAnnouncement()
            ->ordered()
            ->pluck('display_name', 'name')
            ->toArray();

        $category = $request->get('category', 'project');

        if (!array_key_exists($category, $availableCategories)) {
            $category = array_key_first($availableCategories) ?: 'project';
        }

        $fieldDefinitions = FormFieldDefinition::category($category)
            ->ordered()
            ->get();

        return view('admin.form_definitions.index', compact('fieldDefinitions', 'availableCategories', 'category'));
    }

    public function create(Request $request)
    {
        //$this->authorize('create', FormFieldDefinition::class);

        $categories = FormFieldCategory::enabled()
            ->excludeAnnouncement()
            ->ordered()
            ->pluck('display_name', 'name')
            ->toArray();

        $category = $request->get('category', 'project');

        if (!array_key_exists($category, $categories)) {
            $category = array_key_first($categories) ?: 'project';
        }

        $fieldTypes = FormFieldDefinition::FIELD_TYPES;

        return view('admin.form_definitions.create', compact('fieldTypes', 'categories', 'category'));
    }

    public function store(Request $request)
    {
        //$this->authorize('create', FormFieldDefinition::class);

        $validCategories = FormFieldCategory::enabled()->pluck('name')->toArray();

        $validatedData = $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z0-9_]+$/u',
            'category' => 'required|string|in:' . implode(',', $validCategories),
            'label' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(FormFieldDefinition::FIELD_TYPES)),
            'options_text' => 'nullable|string',
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
            'max_length' => 'nullable|integer|min:1',
            'min_selections' => 'nullable|integer|min:0',
            'max_selections' => 'nullable|integer|min:0|gte:min_selections',
            'is_enabled' => 'boolean',
            'options' => 'nullable|array',
            'options.*.value' => 'nullable|string|max:255',
            'options.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'name.regex' => 'フィールド名は半角英数字とアンダースコアのみ使用できます。',
            'options.*.image.image' => '選択肢のファイルは画像である必要があります。',
            'options.*.image.mimes' => '選択肢の画像は jpeg, png, jpg, gif, svg, webp のいずれかの形式である必要があります。',
            'options.*.image.max' => '選択肢の画像のサイズは2MB以下にしてください。',
            'max_selections.gte' => '最大選択数は、最小選択数以上である必要があります。',
        ]);


        $existingField = FormFieldDefinition::where('category', $validatedData['category'])
            ->where('name', $validatedData['name'])
            ->first();

        if ($existingField) {
            return back()->withErrors(['name' => '同じカテゴリ内でフィールド名が重複しています。']);
        }

        $dataToSave = $validatedData;
        unset($dataToSave['options_text'], $dataToSave['options']);

        if ($validatedData['type'] === 'image_select') {
            $dataToSave['options'] = $this->handleImageOptions($request);
        } elseif ($request->filled('options_text') && in_array($validatedData['type'], ['select', 'radio', 'checkbox'])) {
            $optionsArray = [];
            $pairs = explode(',', $request->input('options_text'));
            foreach ($pairs as $pair) {
                $parts = explode(':', trim($pair), 2);
                if (count($parts) === 2) {
                    $optionsArray[trim($parts[0])] = trim($parts[1]);
                } elseif (!empty(trim($parts[0]))) {
                    $optionsArray[trim($parts[0])] = trim($parts[0]);
                }
            }
            $dataToSave['options'] = !empty($optionsArray) ? $optionsArray : null;
        } else {
            $dataToSave['options'] = null;
        }

        FormFieldDefinition::create($dataToSave);

        return redirect()->route('admin.form-definitions.index', ['category' => $dataToSave['category']])
            ->with('success', 'カスタム項目定義が作成されました。');
    }


    public function edit(FormFieldDefinition $formFieldDefinition)
    {
        if (!$formFieldDefinition->exists) {
            abort(404, '指定されたフォーム定義が見つかりません。');
        }

        $this->authorize('update', $formFieldDefinition);

        $fieldTypes = FormFieldDefinition::FIELD_TYPES;
        $categories = FormFieldCategory::enabled()
            ->excludeAnnouncement()
            ->ordered()
            ->pluck('display_name', 'name')
            ->toArray();

        $optionsText = '';
        if ($formFieldDefinition->type !== 'image_select' && !is_null($formFieldDefinition->options)) {
            $options = is_array($formFieldDefinition->options) ? $formFieldDefinition->options : json_decode($formFieldDefinition->options, true);
            if (is_array($options)) {
                $tempOptions = [];
                foreach ($options as $key => $value) {
                    $tempOptions[] = $key . ':' . $value;
                }
                $optionsText = implode(', ', $tempOptions);
            }
        }


        return view('admin.form_definitions.edit', compact('formFieldDefinition', 'fieldTypes', 'categories', 'optionsText'));
    }

    public function update(Request $request, FormFieldDefinition $formFieldDefinition)
    {
        if (!$formFieldDefinition->exists) {
            abort(404, '指定されたフォーム定義が見つかりません。');
        }

        $this->authorize('update', $formFieldDefinition);

        $validCategories = FormFieldCategory::enabled()->pluck('name')->toArray();

        $validatedRules = [
            'name' => 'required|string|max:100|regex:/^[a-z0-9_]+$/u',
            'category' => 'required|string|in:' . implode(',', $validCategories),
            'label' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(FormFieldDefinition::FIELD_TYPES)),
            'options_text' => 'nullable|string',
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
            'max_length' => 'nullable|integer|min:1',
            'min_selections' => 'nullable|integer|min:0',
            'max_selections' => 'nullable|integer|min:0|gte:min_selections',
            'is_enabled' => 'boolean',
            'options' => 'nullable|array',
            'options.*.value' => 'nullable|string|max:255',
            'options.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'options.*.existing_path' => 'nullable|string|max:1024',
        ];

        $validationMessages = [
            'name.regex' => 'フィールド名は半角英数字とアンダースコアのみ使用できます。',
            'options.*.image.image' => '選択肢のファイルは画像である必要があります。',
            'options.*.image.mimes' => '選択肢の画像は jpeg, png, jpg, gif, svg, webp のいずれかの形式である必要があります。',
            'options.*.image.max' => '選択肢の画像のサイズは2MB以下にしてください。',
            'max_selections.gte' => '最大選択数は、最小選択数以上である必要があります。',
        ];

        $validatedData = $request->validate($validatedRules, $validationMessages);

        $existingField = FormFieldDefinition::where('category', $validatedData['category'])
            ->where('name', $validatedData['name'])
            ->where('id', '!=', $formFieldDefinition->id)
            ->first();

        if ($existingField) {
            return back()->withErrors(['name' => '同じカテゴリ内でフィールド名が重複しています。']);
        }

        // 更新するデータを準備
        $dataToUpdate = $validatedData;

        // optionsを一度unsetしてから再構築する
        unset($dataToUpdate['options']);

        if ($validatedData['type'] === 'image_select') {
            $dataToUpdate['options'] = $this->handleImageOptions($request, $formFieldDefinition);
        } elseif ($request->filled('options_text') && in_array($validatedData['type'], ['select', 'radio', 'checkbox'])) {
            $optionsArray = [];
            $pairs = explode(',', $request->input('options_text'));
            foreach ($pairs as $pair) {
                $parts = explode(':', trim($pair), 2);
                if (count($parts) === 2) {
                    $optionsArray[trim($parts[0])] = trim($parts[1]);
                } elseif (!empty(trim($parts[0]))) {
                    $optionsArray[trim($parts[0])] = trim($parts[0]);
                }
            }
            $dataToUpdate['options'] = !empty($optionsArray) ? $optionsArray : null;
        } else {
            // 上記以外のフィールドタイプではoptionsをnullにする
            $dataToUpdate['options'] = null;
        }

        // チェックボックス系の値はbooleanに変換してセット
        $dataToUpdate['is_required'] = $request->boolean('is_required');
        $dataToUpdate['is_enabled'] = $request->boolean('is_enabled');

        // 不要なキーを削除
        unset($dataToUpdate['options_text']);

        $formFieldDefinition->update($dataToUpdate);


        return redirect()->route('admin.form-definitions.index', ['category' => $validatedData['category']])
            ->with('success', 'カスタム項目定義が更新されました。');
    }

    public function destroy(FormFieldDefinition $formFieldDefinition)
    {
        if (!$formFieldDefinition->exists) {
            abort(404, '指定されたフォーム定義が見つかりません。');
        }
        //$this->authorize('delete', $formFieldDefinition);

        if ($formFieldDefinition->isBeingUsed()) {
            $usageCount = $formFieldDefinition->getUsageCount();
            $usedInPosts = $formFieldDefinition->getUsedInPosts();

            $errorMessage = "この項目定義は {$usageCount} 件の投稿で使用されているため削除できません。";

            if (!empty($usedInPosts)) {
                $postTitles = implode('、', array_slice($usedInPosts, 0, 3));
                if (count($usedInPosts) > 3) {
                    $postTitles .= ' など';
                }
                $errorMessage .= "\n使用されている投稿例: {$postTitles}";
            }

            return redirect()->route('admin.form-definitions.index', ['category' => $formFieldDefinition->category])
                ->with('error', $errorMessage);
        }

        // ★ 画像選択タイプの場合、関連する画像を削除
        if ($formFieldDefinition->type === 'image_select' && is_array($formFieldDefinition->options)) {
            foreach ($formFieldDefinition->options as $url) {
                $pathToDelete = str_replace(Storage::disk('public')->url(''), '', $url);
                if (Storage::disk('public')->exists($pathToDelete)) {
                    Storage::disk('public')->delete($pathToDelete);
                }
            }
        }


        $category = $formFieldDefinition->category;
        $formFieldDefinition->delete();
        return redirect()->route('admin.form-definitions.index', ['category' => $category])
            ->with('success', 'カスタム項目定義が削除されました。');
    }

    public function reorder(Request $request)
    {
        //$this->authorize('update', FormFieldDefinition::class);

        $orderedIds = $request->input('orderedIds');

        if (!is_array($orderedIds) || empty($orderedIds)) {
            return response()->json(['success' => false, 'error' => '無効な順序データです。']);
        }

        DB::beginTransaction();
        try {
            foreach ($orderedIds as $index => $id) {
                FormFieldDefinition::where('id', $id)->update(['order' => $index]);
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FormFieldDefinition|null  $existingDefinition
     * @return string|null
     */
    private function handleImageOptions(Request $request, FormFieldDefinition $existingDefinition = null): ?array
    {
        $optionsInput = $request->input('options', []);
        $finalOptions = [];
        $keptImageUrls = [];

        if (!is_array($optionsInput)) {
            return null;
        }

        foreach ($optionsInput as $index => $optionData) {
            if (empty($optionData['value'])) {
                continue;
            }

            $value = trim($optionData['value']);
            $imageUrl = $optionData['existing_path'] ?? null;

            // 新しいファイルがアップロードされた場合
            if ($request->hasFile("options.{$index}.image")) {
                $file = $request->file("options.{$index}.image");
                $path = $file->store('form_option_images', 'public');
                $imageUrl = Storage::disk('public')->url($path);
            }

            if ($imageUrl) {
                $finalOptions[$value] = $imageUrl;
                $keptImageUrls[] = $imageUrl;
            }
        }

        // 更新時のみ、古い未使用の画像を削除
        if ($existingDefinition && is_array($existingDefinition->options)) {
            $oldImageUrls = array_values($existingDefinition->options);
            $deletedImageUrls = array_diff($oldImageUrls, $keptImageUrls);

            foreach ($deletedImageUrls as $url) {
                $pathToDelete = str_replace(Storage::disk('public')->url(''), '', $url);
                if (Storage::disk('public')->exists($pathToDelete)) {
                    Storage::disk('public')->delete($pathToDelete);
                }
            }
        }

        return !empty($finalOptions) ? $finalOptions : null;
    }
}

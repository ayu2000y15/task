<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormFieldDefinition;
use App\Models\FormFieldCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // ★ 追加
use App\Models\InventoryItem;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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

        $availableInventoryItems = InventoryItem::where('quantity', '>', 0)->orderBy('name')->get();

        return view('admin.form_definitions.index', compact('fieldDefinitions', 'availableCategories', 'category', 'availableInventoryItems'));
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
        $formFieldDefinition = new FormFieldDefinition(['is_enabled' => true]);
        $inventoryDisplayMap = collect();

        return view('admin.form_definitions.create', compact('fieldTypes', 'categories', 'category',  'inventoryDisplayMap'));
    }

    public function store(Request $request)
    {
        //$this->authorize('create', FormFieldDefinition::class);

        list($validationRules, $validationMessages) = $this->getValidationRulesAndMessages($request);
        $request->validate($validationRules, $validationMessages);

        if ($this->isDuplicateName($request->input('category'), $request->input('name'))) {
            return back()->withErrors(['name' => '同じカテゴリ内でフィールド名が重複しています。'])->withInput();
        }

        list($options, $inventoryMap) = $this->processOptionsFromRequest($request);

        $dataToSave = $request->except(['options', 'options_text', '_token']);
        $dataToSave['options'] = $options;
        $dataToSave['option_inventory_map'] = $request->boolean('is_inventory_linked') ? $inventoryMap : null;
        $dataToSave['is_required'] = $request->boolean('is_required');
        $dataToSave['is_enabled'] = $request->boolean('is_enabled');


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

        $inventoryMap = $formFieldDefinition->option_inventory_map ?? [];
        $inventoryIds = array_filter(array_column($inventoryMap, 'id'));
        $inventoryDisplayMap = !empty($inventoryIds)
            ? InventoryItem::whereIn('id', $inventoryIds)->get()->mapWithKeys(function ($item) {
                return [$item->id => $item->display_name];
            })
            : collect();

        return view('admin.form_definitions.edit', compact('formFieldDefinition', 'fieldTypes', 'categories', 'inventoryDisplayMap'));
    }

    public function update(Request $request, FormFieldDefinition $formFieldDefinition)
    {
        $this->authorize('update', $formFieldDefinition);

        list($validationRules, $validationMessages) = $this->getValidationRulesAndMessages($request, $formFieldDefinition->id);
        $request->validate($validationRules, $validationMessages);

        if ($this->isDuplicateName($request->input('category'), $request->input('name'), $formFieldDefinition->id)) {
            return back()->withErrors(['name' => '同じカテゴリ内でフィールド名が重複しています。'])->withInput();
        }

        list($options, $inventoryMap) = $this->processOptionsFromRequest($request, $formFieldDefinition);

        $dataToUpdate = $request->except(['options', 'options_text', '_token', '_method']);
        $dataToUpdate['options'] = $options;
        $dataToUpdate['option_inventory_map'] = $request->boolean('is_inventory_linked') ? $inventoryMap : null;
        $dataToUpdate['is_required'] = $request->boolean('is_required');
        $dataToUpdate['is_enabled'] = $request->boolean('is_enabled');

        $formFieldDefinition->update($dataToUpdate);

        return redirect()->route('admin.form-definitions.index', ['category' => $dataToUpdate['category']])
            ->with('success', 'カスタム項目定義が更新されました。');
    }

    // ★ 追加: 在庫品目リストを取得するためのヘルパーメソッド
    private function getInventoryItemsForSelect(): \Illuminate\Support\Collection
    {
        return InventoryItem::orderBy('name')->get()->mapWithKeys(function ($item) {
            return [$item->id => $item->display_name];
        })->prepend('在庫と紐付けない', '');
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

    /**
     * バリデーションルールとメッセージを生成
     */
    private function getValidationRulesAndMessages(Request $request, $ignoreId = null): array
    {
        $validCategories = FormFieldCategory::enabled()->pluck('name')->toArray();
        $rules = [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/u'],
            'category' => ['required', 'string', Rule::in($validCategories)],
            'label' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(array_keys(FormFieldDefinition::FIELD_TYPES))],
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'is_inventory_linked' => 'boolean',
            'order' => 'nullable|integer',
            'max_length' => 'nullable|integer|min:1',
            'min_selections' => 'nullable|integer|min:0',
            'max_selections' => 'nullable|integer|min:0|gte:min_selections',
            'is_enabled' => 'boolean',
            'options' => 'nullable|array',
            'options.*.value' => 'required_with:options.*.label,options.*.image|nullable|string|max:255',
            'options.*.label' => 'required_if:type,select,radio,checkbox|nullable|string|max:255',
            'options.*.existing_path' => 'nullable|string|max:1024',
            'options.*.inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
            'options.*.inventory_consumption_qty' => 'nullable|integer|min:1',

        ];

        $messages = [
            'name.regex' => 'フィールド名は半角英数字とアンダースコアのみ使用できます。',
            'options.*.value.required_with' => '選択肢の「値」は必須です。',
            'options.*.label.required_if' => '選択肢の「ラベル」は必須です。',
            'options.*.image.required_if' => '選択肢の「画像」は必須です。',
            'options.*.image.image' => '選択肢のファイルは画像である必要があります。',
            'max_selections.gte' => '最大選択数は、最小選択数以上である必要があります。',
            'options.*.inventory_consumption_qty.min' => '消費数は1以上で入力してください。',
        ];

        // フィールドタイプが「画像選択」の場合にのみ、画像に関するバリデーションを適用する
        if ($request->input('type') === 'image_select') {
            $rules['options.*.image'] = [
                'required_without:options.*.existing_path', // 既存パスが無い場合にのみ必須
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,svg,webp',
                'max:2048' // 2MB
            ];
            // カスタムエラーメッセージもここで追加
            $messages['options.*.image.required_without'] = '選択肢の画像を指定してください。';
        }

        return [$rules, $messages];
    }

    /**
     * フィールド名の重複をチェック
     */
    private function isDuplicateName(string $category, string $name, $ignoreId = null): bool
    {
        $query = FormFieldDefinition::where('category', $category)->where('name', $name);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    /**
     * リクエストから送られてきたオプション配列を処理し、
     * optionsとoption_inventory_map のためのデータを生成する
     */
    private function processOptionsFromRequest(Request $request, FormFieldDefinition $existingDefinition = null): array
    {
        $optionsInput = $request->input('options', []);
        $finalOptions = [];
        $inventoryMap = [];
        $keptImagePaths = [];

        if (!is_array($optionsInput)) {
            return [null, null];
        }

        $fieldType = $existingDefinition ? $existingDefinition->type : $request->input('type');

        foreach ($optionsInput as $index => $optionData) {
            $value = $optionData['value'] ?? null;
            if (empty($value)) continue;

            $label = $optionData['label'] ?? '';
            $inventoryItemId = $optionData['inventory_item_id'] ?? null;
            $inventoryConsumptionQty = $optionData['inventory_consumption_qty'] ?? 1;

            if (!empty($inventoryItemId)) {
                $inventoryMap[$value] = [
                    'id' => (int)$inventoryItemId,
                    'qty' => (int)$inventoryConsumptionQty,
                ];
            }

            if ($fieldType === 'image_select') {
                $imageUrl = $optionData['existing_path'] ?? null;
                $isNewOption = empty($imageUrl);

                // 新しい画像ファイルがアップロードされたかチェック
                if ($request->hasFile("options.{$index}.image")) {
                    $file = $request->file("options.{$index}.image");
                    $path = $file->store('form_option_images', 'public');
                    $imageUrl = Storage::disk('public')->url($path);
                } elseif ($isNewOption) {
                    // 新しい選択肢なのに、ファイルがリクエストに含まれていない場合
                    Log::warning('New image option file not received.', ['option_data' => $optionData]);
                    throw new \Exception("新しい選択肢 (値: {$value}) の画像ファイルが添付されていません。");
                }

                if ($imageUrl) {
                    $finalOptions[$value] = $imageUrl;
                    $keptImagePaths[] = $imageUrl;
                }
            } else {
                $finalOptions[$value] = $label;
            }
        }

        if ($existingDefinition && $fieldType === 'image_select' && is_array($existingDefinition->options)) {
            $oldImageUrls = array_values($existingDefinition->options);
            $deletedImageUrls = array_diff($oldImageUrls, $keptImagePaths);

            foreach ($deletedImageUrls as $url) {
                $pathToDelete = str_replace(Storage::disk('public')->url(''), '', $url);
                if (Storage::disk('public')->exists($pathToDelete)) {
                    Storage::disk('public')->delete($pathToDelete);
                }
            }
        }

        return [!empty($finalOptions) ? $finalOptions : null, !empty($inventoryMap) ? $inventoryMap : null];
    }

    /**
     * 選択肢（options）と在庫連携マップ（option_inventory_map）を非同期で更新します。
     */
    public function updateOptions(Request $request, FormFieldDefinition $formFieldDefinition)
    {
        $this->authorize('update', $formFieldDefinition);

        // バリデーションをシンプルにします（実際の必須チェックはprocessOptionsFromRequestで行う）
        $rules = [
            'is_inventory_linked' => 'nullable|boolean', // 在庫連携フラグのバリデーションを追加
            'options' => 'nullable|array',
            'options.*.value' => 'required_with:options.*.label,options.*.image|nullable|string|max:255',
            'options.*.label' => 'nullable|string|max:255',
            'options.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // 2MB
            'options.*.existing_path' => 'nullable|string|max:1024',
            'options.*.inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
            'options.*.inventory_consumption_qty' => 'nullable|integer|min:1',
        ];


        $messages = [
            'options.*.image.image' => 'アップロードされたファイルは画像形式である必要があります。',
            'options.*.image.mimes' => '画像の形式は jpeg, png, jpg, gif, svg, webp のいずれかである必要があります。',
            'options.*.image.max' => '画像ファイルのサイズは2MB以下にしてください。',
            'options.*.value.required_with' => '画像またはラベルを設定する場合、「値」は必須です。',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            list($options, $inventoryMap) = $this->processOptionsFromRequest($request, $formFieldDefinition);

            $isInventoryLinked = $request->boolean('is_inventory_linked');
            $formFieldDefinition->is_inventory_linked = $isInventoryLinked;
            $formFieldDefinition->options = $options;
            // 在庫連携がOFFの場合は、在庫マップをnullにする
            $formFieldDefinition->option_inventory_map = $isInventoryLinked ? $inventoryMap : null;
            $formFieldDefinition->save();

            DB::commit();

            $formFieldDefinition->refresh();

            return response()->json([
                'success' => true,
                'message' => '選択肢を更新しました。',
                'options' => $formFieldDefinition->options,
                'option_inventory_map' => $formFieldDefinition->option_inventory_map,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // エラーメッセージをそのままフロントエンドに返す
            return response()->json(['success' => false, 'message' => '更新中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }
}

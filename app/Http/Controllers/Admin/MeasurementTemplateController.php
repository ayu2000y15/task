<?php

namespace App\Http\Controllers\Admin; // ★ 名前空間を確認

use App\Http\Controllers\Controller;
use App\Models\MeasurementTemplate;
use App\Models\Project; // indexForCharacter, storeForCharacter, load で使用
use App\Models\Character; // indexForCharacter, storeForCharacter で使用
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MeasurementTemplateController extends Controller
{
    /**
     * Display a listing of the measurement templates for admin.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', MeasurementTemplate::class); // 必要に応じて認可
        $templates = MeasurementTemplate::orderBy('name')->paginate(15);
        return view('admin.measurement-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new measurement template.
     */
    public function create()
    {
        // $this->authorize('create', MeasurementTemplate::class); // 必要に応じて認可
        return view('admin.measurement-templates.create');
    }

    /**
     * Store a newly created measurement template in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', MeasurementTemplate::class); // 必要に応じて認可

        // まず name と description をバリデーション
        $validatedTemplateData = $request->validate([
            'name' => 'required|string|max:255|unique:measurement_templates,name',
            'description' => 'nullable|string|max:1000',
            'items' => 'nullable|json', // items はJSON文字列として受け取ることを期待
        ], [
            'name.required' => 'テンプレート名は必須です。',
            'name.unique' => 'そのテンプレート名は既に使用されています。',
            'items.json' => '採寸項目のデータ形式が無効です。(JSON形式ではありません)',
        ]);

        $itemsArray = [];
        if (!empty($validatedTemplateData['items'])) {
            $decodedItems = json_decode($validatedTemplateData['items'], true);
            if (is_array($decodedItems)) {
                $itemsArray = $decodedItems;
            } elseif ($validatedTemplateData['items'] !== '[]') { // 空の配列文字列は許容
                // JSON文字列ではあったが、配列にデコードできなかった場合
                return back()->withErrors(['items' => '採寸項目のデータ形式が無効です。(配列にデコードできませんでした)'])->withInput();
            }
        }

        // itemsArray に対する詳細なバリデーション
        $itemsValidator = Validator::make(['items_data' => $itemsArray], [
            'items_data' => 'present|array',
            'items_data.*.item' => 'required|string|max:255',
            'items_data.*.value' => 'nullable|string|max:255',
            'items_data.*.notes' => 'nullable|string|max:1000',
        ], [
            'items_data.array' => '採寸項目(items)は配列形式で指定してください。',
            'items_data.*.item.required' => '各採寸項目の項目名は必須です。',
        ]);

        if ($itemsValidator->fails()) {
            return redirect()->route('admin.measurement-templates.create')
                ->withErrors($itemsValidator)
                ->withInput();
        }

        $template = MeasurementTemplate::create([
            'name' => $validatedTemplateData['name'],
            'description' => $validatedTemplateData['description'],
            'items' => $itemsValidator->validated()['items_data'] ?? [],
            // 'user_id' => auth()->id(), // 必要であれば
        ]);

        return redirect()->route('admin.measurement-templates.edit', $template)->with('success', '採寸テンプレートを作成しました。項目を編集してください。');
    }

    /**
     * Show the form for editing the specified measurement template.
     */
    public function edit(MeasurementTemplate $measurementTemplate)
    {
        // $this->authorize('view', $measurementTemplate); // 必要に応じて認可
        return view('admin.measurement-templates.edit', compact('measurementTemplate'));
    }

    /**
     * Update the specified measurement template in storage.
     */
    public function update(Request $request, MeasurementTemplate $measurementTemplate)
    {
        // $this->authorize('update', $measurementTemplate); // 必要に応じて認可
        // まず name と description をバリデーション
        $validatedTemplateData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('measurement_templates')->ignore($measurementTemplate->id)],
            'description' => 'nullable|string|max:1000',
            'items' => 'required|json', // ★ items はJSON文字列として必須で受け取る
        ], [
            'name.required' => 'テンプレート名は必須です。',
            'name.unique' => 'そのテンプレート名は既に使用されています。',
            'items.required' => '採寸項目は必須です。',
            'items.json' => '採寸項目のデータ形式が無効です。(JSON形式ではありません)',
        ]);

        $itemsArray = [];
        // $validatedTemplateData['items'] にはJSON文字列が入っているのでデコード
        $decodedItems = json_decode($validatedTemplateData['items'], true);

        if (is_array($decodedItems)) {
            $itemsArray = $decodedItems;
        } else {
            // JSON文字列ではあったが、配列にデコードできなかった場合
            // (通常は 'items.json' バリデーションでキャッチされるが、念のため)
            return back()->withErrors(['items' => '採寸項目のデータ形式が無効です。(配列にデコードできませんでした)'])->withInput();
        }

        // itemsArray に対する詳細なバリデーション
        // Validator::makeの第一引数はバリデーションしたいデータなので、キーを'items_to_validate'のように変更して渡す
        $itemsValidator = Validator::make(['items_to_validate' => $itemsArray], [
            'items_to_validate' => 'present|array', // items_to_validate 自体が配列であることを確認
            'items_to_validate.*.item' => 'required|string|max:255',
            'items_to_validate.*.value' => 'nullable|string|max:255',
            'items_to_validate.*.notes' => 'nullable|string|max:1000',
        ], [
            'items_to_validate.array' => 'itemsは配列でなくてはなりません。', // このメッセージが出る可能性がある
            'items_to_validate.*.item.required' => '各採寸項目の項目名は必須です。',
        ]);

        if ($itemsValidator->fails()) {
            // バリデーション失敗時は編集ページに戻り、エラーと入力値を表示
            return redirect()->route('admin.measurement-templates.edit', $measurementTemplate)
                ->withErrors($itemsValidator) // itemsValidatorのエラーを渡す
                ->withInput(); // これにより old('name'), old('description'), old('items') が使える
            // old('items') には元のJSON文字列が渡るようにする
        }


        $updateData = [
            'name' => $validatedTemplateData['name'],
            'description' => $validatedTemplateData['description'],
            // バリデーション済みのitems配列を取得
            'items' => $itemsValidator->validated()['items_to_validate'] ?? [],
        ];

        $measurementTemplate->update($updateData);

        // ★★★ 更新後に一覧ページへリダイレクト ★★★
        return redirect()->route('admin.measurement-templates.index')->with('success', '採寸テンプレート情報を更新しました。');
    }


    /**
     * Remove the specified measurement template from storage.
     */
    public function destroy(MeasurementTemplate $measurementTemplate) // Request $request は不要なら削除
    {
        // $this->authorize('delete', $measurementTemplate); // 必要に応じて認可
        $measurementTemplate->delete();
        return redirect()->route('admin.measurement-templates.index')->with('success', '採寸テンプレートを削除しました。');
    }


    // === 既存のキャラクターコンテキストのメソッド群 (変更なし、ただし認可コメントは適宜調整) ===
    public function indexForCharacter(Project $project, Character $character)
    {
        $this->authorize('manageMeasurements', $project);
        $templates = MeasurementTemplate::orderBy('name')->get();
        return response()->json(['templates' => $templates]);
    }

    public function storeForCharacter(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMeasurements', $project);

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255|unique:measurement_templates,name',
            'items_to_save' => 'required|array|min:1',
            'items_to_save.*.item' => 'required|string|max:255',
            'items_to_save.*.value' => 'nullable|string|max:255',
            'items_to_save.*.notes' => 'nullable|string|max:1000',
        ], [
            'template_name.required' => 'テンプレート名は必須です。',
            'template_name.unique' => 'そのテンプレート名は既に使用されています。',
            'items_to_save.required' => '保存する採寸項目がありません。',
            'items_to_save.min' => '保存する採寸項目がありません。',
            'items_to_save.*.item.required' => '採寸項目の名称は必須です。',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $itemsToStore = $validated['items_to_save'];
        foreach ($itemsToStore as $key => $itemData) { // $item を $itemData に変更（ループ変数名の衝突回避）
            if (!isset($itemData['value'])) {
                $itemsToStore[$key]['value'] = '';
            }
            if (!isset($itemData['notes'])) {
                $itemsToStore[$key]['notes'] = '';
            }
        }

        $template = MeasurementTemplate::create([
            'name' => $validated['template_name'],
            'items' => $itemsToStore,
        ]);

        return response()->json([
            'success' => true,
            'message' => '採寸テンプレートを保存しました。',
            'template' => $template
        ]);
    }

    public function load(MeasurementTemplate $measurement_template) // Request $request は不要なら削除
    {
        // if (!auth()->check()) { // 認可はミドルウェアやポリシーで行うのが一般的
        //     abort(403);
        // }
        // $this->authorize('view', $measurement_template); // または適切な認可

        return response()->json($measurement_template);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormFieldDefinition;
use App\Models\FormFieldCategory;
use Illuminate\Http\Request; // 必要に応じて
use Illuminate\Support\Facades\DB;

class FormFieldDefinitionController extends Controller
{
    public function __construct()
    {
        // $this->authorizeResource(FormFieldDefinition::class, 'form_field_definition');
        // authorizeResource は便利ですが、問題の切り分けのため、
        // 各メソッドで明示的に $this->authorize() を呼ぶことを推奨します。
    }

    public function index(Request $request)
    {
        //$this->authorize('viewAny', FormFieldDefinition::class);

        // データベースからカテゴリを取得（お知らせを除外）
        $availableCategories = FormFieldCategory::enabled()
            ->excludeAnnouncement()
            ->ordered()
            ->pluck('display_name', 'name')
            ->toArray();

        $category = $request->get('category', 'project'); // デフォルトは案件依頼

        // 有効なカテゴリかチェック
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

        // データベースからカテゴリを取得（お知らせを除外）
        $categories = FormFieldCategory::enabled()
            ->excludeAnnouncement()
            ->ordered()
            ->pluck('display_name', 'name')
            ->toArray();

        $category = $request->get('category', 'project');

        // 有効なカテゴリかチェック
        if (!array_key_exists($category, $categories)) {
            $category = array_key_first($categories) ?: 'project';
        }

        $fieldTypes = FormFieldDefinition::FIELD_TYPES;

        return view('admin.form_definitions.create', compact('fieldTypes', 'categories', 'category'));
    }

    public function store(Request $request) // Request $request を追加
    {
        //$this->authorize('create', FormFieldDefinition::class);

        // 有効なカテゴリを取得
        $validCategories = FormFieldCategory::enabled()->pluck('name')->toArray();

        $validated = $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z0-9_]+$/u',
            'category' => 'required|string|in:' . implode(',', $validCategories),
            'label' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(FormFieldDefinition::FIELD_TYPES)),
            'options_text' => 'nullable|string', // options_textとして受け取る
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
            'max_length' => 'nullable|integer|min:1',
            'is_enabled' => 'boolean',
        ], [
            'name.regex' => 'フィールド名は半角英数字とアンダースコアのみ使用できます。',
        ]);

        // 同一カテゴリ内でのname重複チェック
        $existingField = FormFieldDefinition::where('category', $validated['category'])
            ->where('name', $validated['name'])
            ->first();

        if ($existingField) {
            return back()->withErrors(['name' => '同じカテゴリ内でフィールド名が重複しています。']);
        }

        $validated['options'] = null;
        if ($request->filled('options_text') && in_array($validated['type'], ['select', 'radio', 'checkbox'])) {
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
            if (!empty($optionsArray)) {
                $validated['options'] = json_encode($optionsArray);
            }
        }

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_enabled'] = $request->boolean('is_enabled');

        FormFieldDefinition::create($validated);

        return redirect()->route('admin.form-definitions.index', ['category' => $validated['category']])
            ->with('success', 'カスタム項目定義が作成されました。');
    }

    public function edit(FormFieldDefinition $formFieldDefinition) // ★ ルートモデルバインディング
    {
        // Laravelが {form_definition} のIDでモデルを見つけられない場合、
        // このメソッドが呼び出される前に通常は404エラーとなります。
        // 前回の修正で追加した明示的なチェックは、万が一空のモデルが渡された場合への対処です。
        if (!$formFieldDefinition->exists) {
            abort(404, '指定されたフォーム定義が見つかりません。');
        }

        $this->authorize('update', $formFieldDefinition); // 認可

        $fieldTypes = FormFieldDefinition::FIELD_TYPES;
        // データベースからカテゴリを取得（お知らせを除外）
        $categories = FormFieldCategory::enabled()
            ->excludeAnnouncement()
            ->ordered()
            ->pluck('display_name', 'name')
            ->toArray();

        $optionsText = '';
        if (!is_null($formFieldDefinition->options) && is_array($formFieldDefinition->options)) {
            $tempOptions = [];
            foreach ($formFieldDefinition->options as $key => $value) {
                $tempOptions[] = $key . ':' . $value;
            }
            $optionsText = implode(', ', $tempOptions);
        } elseif (is_string($formFieldDefinition->options)) {
            $decodedOptions = json_decode($formFieldDefinition->options, true);
            if (is_array($decodedOptions)) {
                $tempOptions = [];
                foreach ($decodedOptions as $key => $value) {
                    $tempOptions[] = $key . ':' . $value;
                }
                $optionsText = implode(', ', $tempOptions);
            }
        }

        return view('admin.form_definitions.edit', compact('formFieldDefinition', 'fieldTypes', 'categories', 'optionsText'));
    }

    public function update(Request $request, FormFieldDefinition $formFieldDefinition) // Request $request を追加
    {
        if (!$formFieldDefinition->exists) {
            abort(404, '指定されたフォーム定義が見つかりません。');
        }
        //$this->authorize('update', $formFieldDefinition);
        // ... (updateメソッドのロジックは前回の回答を参照) ...
        $validated = $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z0-9_]+$/u',
            'category' => 'required|string|in:' . implode(',', array_keys(FormFieldDefinition::CATEGORIES)),
            'label' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(FormFieldDefinition::FIELD_TYPES)),
            'options_text' => 'nullable|string', // options_textとして受け取る
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
            'max_length' => 'nullable|integer|min:1',
            'is_enabled' => 'boolean',
        ], [
            'name.regex' => 'フィールド名は半角英数字とアンダースコアのみ使用できます。',
        ]);

        // 同一カテゴリ内でのname重複チェック（自分自身は除外）
        $existingField = FormFieldDefinition::where('category', $validated['category'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $formFieldDefinition->id)
            ->first();

        if ($existingField) {
            return back()->withErrors(['name' => '同じカテゴリ内でフィールド名が重複しています。']);
        }

        $validated['options'] = null;
        if ($request->filled('options_text') && in_array($validated['type'], ['select', 'radio', 'checkbox'])) {
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
            if (!empty($optionsArray)) {
                $validated['options'] = json_encode($optionsArray);
            }
        }

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_enabled'] = $request->boolean('is_enabled');

        $formFieldDefinition->update($validated);

        return redirect()->route('admin.form-definitions.index', ['category' => $validated['category']])
            ->with('success', 'カスタム項目定義が更新されました。');
    }

    public function destroy(FormFieldDefinition $formFieldDefinition)
    {
        if (!$formFieldDefinition->exists) {
            abort(404, '指定されたフォーム定義が見つかりません。');
        }
        //$this->authorize('delete', $formFieldDefinition);

        // 使用状況をチェック
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

        $category = $formFieldDefinition->category;
        $formFieldDefinition->delete();
        return redirect()->route('admin.form-definitions.index', ['category' => $category])
            ->with('success', 'カスタム項目定義が削除されました。');
    }

    public function reorder(Request $request)
    {
        //$this->authorize('update', FormFieldDefinition::class); // 並び替えは更新権限が必要と仮定

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
}

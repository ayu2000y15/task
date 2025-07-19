<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormFieldCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormCategoryController extends Controller
{
    /**
     * フォームカテゴリ一覧を表示
     */
    public function index()
    {
        $categories = FormFieldCategory::form()
            ->withCount('formFieldDefinitions')
            ->ordered()
            ->get();

        return view('admin.form_categories.index', compact('categories'));
    }

    /**
     * 新規フォームカテゴリ作成フォームを表示
     */
    public function create()
    {
        return view('admin.form_categories.create');
    }

    /**
     * 新規フォームカテゴリを保存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:form_field_categories,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9\-_]+$/|unique:form_field_categories,slug',
            'form_title' => 'nullable|string|max:200',
            'form_description' => 'nullable|string',
            'thank_you_title' => 'nullable|string|max:200',
            'thank_you_message' => 'nullable|string',
            'delivery_estimate_text' => 'nullable|string|max:100',
            'is_external_form' => 'boolean',
            'requires_approval' => 'boolean',
            'send_completion_email' => 'boolean',
            'notification_emails_string' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_enabled' => 'boolean',
            'project_category_id' => 'nullable|exists:project_categories,id',
        ], [
            'name.regex' => 'カテゴリ名は半角英数字とアンダースコアのみ使用できます。',
            'slug.regex' => 'URLスラッグは半角英数字、ハイフン、アンダースコアのみ使用できます。',
        ]);

        $category = new FormFieldCategory();

        // --- データを1つずつ明示的に設定 ---
        $category->name = $validated['name'];
        $category->display_name = $validated['display_name'];
        $category->description = $validated['description'] ?? null;
        $category->slug = (isset($validated['is_external_form']) && $validated['is_external_form'] && empty($validated['slug'])) ? $validated['name'] : ($validated['slug'] ?? null);
        $category->form_title = $validated['form_title'] ?? null;
        $category->form_description = $validated['form_description'] ?? null;
        $category->thank_you_title = $validated['thank_you_title'] ?? null;
        $category->thank_you_message = $validated['thank_you_message'] ?? null;
        $category->delivery_estimate_text = $validated['delivery_estimate_text'] ?? null;
        $category->order = $validated['order'] ?? 0;
        $category->project_category_id = $validated['project_category_id'] ?? null;

        // チェックボックスの値を設定
        $category->is_external_form = $request->has('is_external_form');
        $category->requires_approval = $request->has('requires_approval');
        $category->send_completion_email = $request->has('send_completion_email');
        $category->is_enabled = $request->has('is_enabled');

        // 固定値を設定
        $category->type = 'form';

        // 通知先メールアドレスの処理
        if (!empty($validated['notification_emails_string'])) {
            $category->setNotificationEmailsFromString($validated['notification_emails_string']);
        }

        $category->save();

        return redirect()->route('admin.form-categories.index')
            ->with('success', 'フォームカテゴリを作成しました。');
    }

    /**
     * フォームカテゴリ詳細を表示
     */
    public function show(FormFieldCategory $formCategory)
    {
        $formCategory->loadCount('formFieldDefinitions');
        $formFieldDefinitions = $formCategory->formFieldDefinitions()
            ->orderBy('order')
            ->orderBy('label')
            ->get();

        return view('admin.form_categories.show', compact('formCategory', 'formFieldDefinitions'));
    }

    /**
     * フォームカテゴリ編集フォームを表示
     */
    public function edit(FormFieldCategory $formCategory)
    {
        return view('admin.form_categories.edit', compact('formCategory'));
    }

    /**
     * フォームカテゴリを更新
     */
    public function update(Request $request, FormFieldCategory $formCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('form_field_categories', 'name')->ignore($formCategory->id),
            ],
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-_]+$/',
                Rule::unique('form_field_categories', 'slug')->ignore($formCategory->id),
            ],
            'form_title' => 'nullable|string|max:200',
            'form_description' => 'nullable|string',
            'thank_you_title' => 'nullable|string|max:200',
            'thank_you_message' => 'nullable|string',
            'delivery_estimate_text' => 'nullable|string|max:100',
            'is_external_form' => 'boolean',
            'requires_approval' => 'boolean',
            'send_completion_email' => 'boolean',
            'notification_emails_string' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_enabled' => 'boolean',
            'project_category_id' => 'nullable|exists:project_categories,id',
        ], [
            'name.regex' => 'カテゴリ名は半角英数字とアンダースコアのみ使用できます。',
            'slug.regex' => 'URLスラッグは半角英数字、ハイフン、アンダースコアのみ使用できます。',
        ]);

        // --- データを1つずつ明示的に設定 ---
        $formCategory->name = $validated['name'];
        $formCategory->display_name = $validated['display_name'];
        $formCategory->description = $validated['description'] ?? null;
        $formCategory->slug = ($request->has('is_external_form') && empty($validated['slug'])) ? $validated['name'] : ($validated['slug'] ?? null);
        $formCategory->form_title = $validated['form_title'] ?? null;
        $formCategory->form_description = $validated['form_description'] ?? null;
        $formCategory->thank_you_title = $validated['thank_you_title'] ?? null;
        $formCategory->thank_you_message = $validated['thank_you_message'] ?? null;
        $formCategory->delivery_estimate_text = $validated['delivery_estimate_text'] ?? null;
        $formCategory->order = $validated['order'] ?? 0;
        $formCategory->project_category_id = $validated['project_category_id'] ?? null;

        // チェックボックスの値を設定
        $formCategory->is_external_form = $request->has('is_external_form');
        $formCategory->requires_approval = $request->has('requires_approval');
        $formCategory->send_completion_email = $request->has('send_completion_email');
        $formCategory->is_enabled = $request->has('is_enabled');

        // 通知先メールアドレスの処理
        if (!empty($validated['notification_emails_string'])) {
            $formCategory->setNotificationEmailsFromString($validated['notification_emails_string']);
        } else {
            $formCategory->notification_emails = null;
        }

        $formCategory->save();

        return redirect()->route('admin.form-categories.index')
            ->with('success', 'フォームカテゴリを更新しました。');
    }

    /**
     * フォームカテゴリを削除
     */
    public function destroy(FormFieldCategory $formCategory)
    {
        // 使用中の場合は削除不可
        if ($formCategory->isBeingUsed()) {
            return redirect()->route('admin.form-categories.index')
                ->with('error', 'このカテゴリは使用中のため削除できません。');
        }

        $formCategory->delete();

        return redirect()->route('admin.form-categories.index')
            ->with('success', 'フォームカテゴリを削除しました。');
    }

    /**
     * フォームカテゴリの順序を更新
     */
    public function reorder(Request $request)
    {
        $orderedIds = $request->input('orderedIds');

        if (!is_array($orderedIds) || empty($orderedIds)) {
            return response()->json(['success' => false, 'error' => '無効な順序データです。']);
        }

        try {
            foreach ($orderedIds as $index => $id) {
                FormFieldCategory::where('id', $id)->update(['order' => $index + 1]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => '順序の更新に失敗しました。']);
        }
    }

    /**
     * フォームカテゴリの有効/無効を切り替え
     */
    public function toggleEnabled(FormFieldCategory $formCategory)
    {
        $formCategory->is_enabled = !$formCategory->is_enabled;
        $formCategory->save();

        $status = $formCategory->is_enabled ? '有効' : '無効';
        return redirect()->back()
            ->with('success', "フォームカテゴリを{$status}にしました。");
    }

    /**
     * 外部フォーム公開の有効/無効を切り替え
     */
    public function toggleExternalForm(FormFieldCategory $formCategory)
    {
        $formCategory->is_external_form = !$formCategory->is_external_form;
        $formCategory->save();

        $status = $formCategory->is_external_form ? '公開' : '非公開';
        return redirect()->back()
            ->with('success', "外部フォームを{$status}にしました。");
    }
}

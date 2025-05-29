<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // DBトランザクション用
use Illuminate\Support\Facades\Validator;

class FeedbackCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', FeedbackCategory::class);
        $categories = FeedbackCategory::withCount('feedbacks')
            ->orderBy('display_order', 'asc') // ★ display_orderでソート
            ->orderBy('name', 'asc') // display_orderが同じ場合は名前順
            ->paginate(15); // ページネーション数は適宜調整
        return view('admin.feedback_categories.index', compact('categories'));
    }

    public function create()
    {
        // $this->authorize('create', FeedbackCategory::class);
        return view('admin.feedback_categories.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', FeedbackCategory::class);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:feedback_categories,name',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'カテゴリ名は必須です。',
            'name.unique' => 'そのカテゴリ名は既に使用されています。',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.feedback-categories.create')
                ->withErrors($validator)
                ->withInput();
        }
        $validatedData = $validator->validated();
        $validatedData['is_active'] = $request->boolean('is_active');

        // ★ 新規作成時にdisplay_orderを設定
        $maxOrder = FeedbackCategory::max('display_order');
        $validatedData['display_order'] = $maxOrder + 1;

        FeedbackCategory::create($validatedData);

        return redirect()->route('admin.feedback-categories.index')->with('success', 'フィードバックカテゴリが作成されました。');
    }

    public function edit(FeedbackCategory $feedbackCategory)
    {
        $this->authorize('update', $feedbackCategory);
        return view('admin.feedback_categories.edit', compact('feedbackCategory'));
    }

    public function update(Request $request, FeedbackCategory $feedbackCategory)
    {
        $this->authorize('update', $feedbackCategory);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:feedback_categories,name,' . $feedbackCategory->id,
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'カテゴリ名は必須です。',
            'name.unique' => 'そのカテゴリ名は既に使用されています。',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.feedback-categories.edit', $feedbackCategory)
                ->withErrors($validator)
                ->withInput();
        }
        $validatedData = $validator->validated();
        $validatedData['is_active'] = $request->boolean('is_active');
        // display_order は reorder メソッドで更新するため、ここでは触らない

        $feedbackCategory->update($validatedData);

        return redirect()->route('admin.feedback-categories.index')->with('success', 'フィードバックカテゴリが更新されました。');
    }

    public function destroy(FeedbackCategory $feedbackCategory)
    {
        $this->authorize('delete', $feedbackCategory);
        if ($feedbackCategory->feedbacks()->count() > 0) {
            return redirect()->route('admin.feedback-categories.index')->with('error', 'このカテゴリにはフィードバックが紐付いているため削除できません。');
        }

        $feedbackCategory->delete();
        // 他のカテゴリのdisplay_orderを再調整するロジックが必要な場合があるが、一旦省略
        return redirect()->route('admin.feedback-categories.index')->with('success', 'フィードバックカテゴリが削除されました。');
    }

    /**
     * Reorder feedback categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $this->authorize('update', FeedbackCategory::class); // or a more specific permission

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:feedback_categories,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->ids as $index => $id) {
                FeedbackCategory::where('id', $id)->update(['display_order' => $index + 1]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'カテゴリの順序を更新しました。']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => '順序の更新中にエラーが発生しました。権限設定を確認してください。', 'error' => $e->getMessage()], 500);
        }
    }
}

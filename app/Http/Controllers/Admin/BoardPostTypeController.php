<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardPostType;
use App\Models\FormFieldCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BoardPostTypeController extends Controller
{
    /**
     * 投稿タイプ一覧表示
     */
    public function index()
    {
        $this->authorize('viewAny', BoardPostType::class);

        // お知らせタイプは管理画面から除外
        $boardPostTypes = BoardPostType::where('name', '!=', 'announcement')
            ->ordered()
            ->get();

        return view('admin.board-post-types.index', compact('boardPostTypes'));
    }

    /**
     * 投稿タイプ作成フォーム表示
     */
    public function create()
    {
        $this->authorize('create', BoardPostType::class);

        return view('admin.board-post-types.create');
    }

    /**
     * 投稿タイプ保存
     */
    public function store(Request $request)
    {
        $this->authorize('create', BoardPostType::class);

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:board_post_types,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // nameが指定されていない場合、display_nameから生成
        if (empty($validated['name'])) {
            $validated['name'] = Str::slug($validated['display_name'], '_');
        }

        // is_activeのデフォルト値を設定
        $validated['is_active'] = $validated['is_active'] ?? true;

        // orderが指定されていない場合、最大値+1を設定
        if (empty($validated['order'])) {
            $maxOrder = BoardPostType::max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        DB::transaction(function () use ($validated) {
            // デフォルトフラグの処理：新しい投稿タイプをデフォルトにする場合、他のデフォルトを解除
            if (!empty($validated['is_default'])) {
                BoardPostType::where('is_default', true)->update(['is_default' => false]);
            }

            // 投稿タイプを作成（オブザーバーでカテゴリも自動作成される）
            BoardPostType::create($validated);
        });

        return redirect()->route('admin.board-post-types.index')
            ->with('success', '投稿タイプを作成しました。カスタム項目のカテゴリも自動作成されました。');
    }

    /**
     * 投稿タイプ詳細表示
     */
    public function show(BoardPostType $boardPostType)
    {
        $this->authorize('view', $boardPostType);

        return view('admin.board-post-types.show', compact('boardPostType'));
    }

    /**
     * 投稿タイプ編集フォーム表示
     */
    public function edit(BoardPostType $boardPostType)
    {
        $this->authorize('update', $boardPostType);

        // お知らせタイプは編集不可
        if ($boardPostType->name === 'announcement') {
            return redirect()->route('admin.board-post-types.index')
                ->with('error', 'お知らせタイプは編集できません。');
        }

        return view('admin.board-post-types.edit', compact('boardPostType'));
    }

    /**
     * 投稿タイプ更新
     */
    public function update(Request $request, BoardPostType $boardPostType)
    {
        $this->authorize('update', $boardPostType);

        // お知らせタイプは更新不可
        if ($boardPostType->name === 'announcement') {
            return redirect()->route('admin.board-post-types.index')
                ->with('error', 'お知らせタイプは編集できません。');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:board_post_types,name,' . $boardPostType->id,
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // is_activeのデフォルト値を設定
        $validated['is_active'] = $validated['is_active'] ?? false;

        DB::transaction(function () use ($validated, $boardPostType) {
            // デフォルトフラグの処理
            if (!empty($validated['is_default'])) {
                BoardPostType::where('is_default', true)
                    ->where('id', '!=', $boardPostType->id)
                    ->update(['is_default' => false]);
            }

            // 投稿タイプを更新（オブザーバーでカテゴリも自動更新される）
            $boardPostType->update($validated);
        });

        return redirect()->route('admin.board-post-types.index')
            ->with('success', '投稿タイプを更新しました。');
    }

    /**
     * 投稿タイプ削除
     */
    public function destroy(BoardPostType $boardPostType)
    {
        $this->authorize('delete', $boardPostType);

        // お知らせタイプは削除不可
        if ($boardPostType->name === 'announcement') {
            return back()->with('error', 'お知らせタイプは削除できません。');
        }

        // 使用中の投稿があるかチェック
        if ($boardPostType->boardPosts()->exists()) {
            return back()->with('error', 'この投稿タイプを使用している投稿があるため削除できません。');
        }

        DB::transaction(function () use ($boardPostType) {
            // 投稿タイプを削除（オブザーバーでカテゴリも自動処理される）
            $boardPostType->delete();
        });

        return redirect()->route('admin.board-post-types.index')
            ->with('success', '投稿タイプを削除しました。');
    }

    /**
     * 投稿タイプの表示順を更新
     */
    public function updateOrder(Request $request)
    {
        $this->authorize('update', BoardPostType::class);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:board_post_types,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                BoardPostType::where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
        });

        return response()->json(['success' => true, 'message' => '表示順を更新しました。']);
    }
}

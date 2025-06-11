<?php

namespace App\Http\Controllers;

use App\Models\BoardPost;
use App\Models\User;
use App\Models\BoardPostRead;
use App\Models\BoardComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\BoardCommentRead;

class BoardCommentController extends Controller
{
    /**
     * 新しいコメントを保存する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BoardPost  $post
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, BoardPost $post)
    {
        Gate::authorize('view', $post);

        // 【重要ポイント1】バリデーションに parent_id を含める
        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:board_comments,id',
        ]);

        // 【重要ポイント2】createメソッドのデータに parent_id を含める
        $comment = $post->comments()->create([
            'body' => $validated['body'],
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $cleanBody = strip_tags($comment->body);

        // 【重要ポイント3】以降のメンション通知処理は、この$commentオブジェクトを元に行われます
        if (preg_match('/@all\b/', $cleanBody)) {
            $allUserIds = User::where('status', User::STATUS_ACTIVE)
                ->where('id', '!=', auth()->id())
                ->pluck('id');

            foreach ($allUserIds as $userId) {
                BoardCommentRead::updateOrCreate(
                    ['user_id' => $userId, 'comment_id' => $comment->id],
                    ['read_at' => null]
                );
            }

            activity()
                ->performedOn($comment)
                ->causedBy(auth()->user())
                ->withProperties(['post_title' => $post->title])
                ->log("コメントで@allメンションを使用し、全ユーザーに通知しました。");
        } else {
            preg_match_all('/@([\p{L}\p{N}_-]+)/u', $cleanBody, $mentionMatches);
            if (!empty($mentionMatches[1])) {
                $mentionedAccessIds = array_unique($mentionMatches[1]);
                $mentionedUsers = User::whereIn('access_id', $mentionedAccessIds)->get();

                foreach ($mentionedUsers as $user) {
                    if ($user->id !== Auth::id()) {
                        BoardCommentRead::updateOrCreate(
                            ['user_id' => $user->id, 'comment_id' => $comment->id],
                            ['read_at' => null]
                        );
                    }
                }
            }
        }

        return redirect()->route('community.posts.show', $post)
            ->with('success', 'コメントを投稿しました。');
    }

    /**
     * 指定されたコメントを更新する
     */
    public function update(Request $request, BoardComment $comment)
    {
        // 1. ポリシーで更新権限をチェック
        $this->authorize('update', $comment);

        // 2. バリデーション
        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        // 3. 先にコメントを更新
        $comment->update($validated);

        // 4. 更新後の本文でメンションを再処理
        $cleanBody = strip_tags($comment->body);

        // @all が存在するかチェック
        if (preg_match('/@all\b/', $cleanBody)) {
            $allUserIds = User::where('status', User::STATUS_ACTIVE)
                ->where('id', '!=', auth()->id()) // コメント投稿者を除く
                ->pluck('id');

            // 各ユーザーに通知
            foreach ($allUserIds as $userId) {
                BoardPostRead::updateOrCreate(
                    ['user_id' => $userId, 'board_post_id' => $comment->board_post_id],
                    ['read_at' => null]
                );
            }

            activity()
                ->performedOn($comment)
                ->causedBy(auth()->user())
                ->withProperties(['post_title' => $comment->boardPost->title])
                ->log("コメントの更新で@allメンションを使用し、全ユーザーに通知しました。");
        } else {
            // @all がない場合は、個別のメンションを処理
            preg_match_all('/@([\p{L}\p{N}_-]+)/u', $cleanBody, $mentionMatches);
            if (!empty($mentionMatches[1])) {
                $mentionedAccessIds = array_unique($mentionMatches[1]);
                $mentionedUsers = User::whereIn('access_id', $mentionedAccessIds)->get();

                // $user変数はこのループの中でのみ定義・使用される
                foreach ($mentionedUsers as $user) {
                    if ($user->id !== auth()->id()) {
                        BoardPostRead::updateOrCreate(
                            ['user_id' => $user->id, 'board_post_id' => $comment->board_post_id],
                            ['read_at' => null]
                        );
                    }
                }
            }
        }

        // 5. 整形済みの新しい本文をJSONで返す
        return response()->json([
            'success' => true,
            'message' => 'コメントを更新しました。',
            'formatted_body' => $comment->fresh()->formatted_body,
        ]);
    }

    /**
     * 指定されたコメントを削除する
     */
    public function destroy(BoardComment $comment)
    {
        // ポリシーで削除権限をチェック
        $this->authorize('delete', $comment);

        $comment->delete(); // delete()がActivityLogを自動的に記録します

        // 成功したことを示すJSONレスポンスを返す
        return response()->json([
            'success' => true,
            'message' => 'コメントを削除しました。'
        ]);
    }

    public function toggleReaction(Request $request, BoardComment $comment)
    {
        $this->authorize('view', $comment->boardPost);

        $validated = $request->validate(['emoji' => 'required|string|max:8']);
        $userId = auth()->id();
        $emoji = $validated['emoji'];

        $existingReaction = $comment->reactions()->where('user_id', $userId)->where('emoji', $emoji)->first();

        if ($existingReaction) {
            $existingReaction->delete();
        } else {
            $comment->reactions()->create(['user_id' => $userId, 'emoji' => $emoji]);
        }

        $comment->load('reactions.user'); // 最新のリアクションとユーザー情報を読み込む
        $updatedHtml = view('community.posts.partials._comment_reactions', ['comment' => $comment])->render();

        return response()->json(['success' => true, 'html' => $updatedHtml]);
    }
}

<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\BoardPost;
use App\Models\BoardPostRead;
use Illuminate\Support\Facades\Auth;
use App\Models\BoardCommentRead;

class UnreadBoardPostComposer
{
    /**
     * ビューにデータをバインドします。
     * 権限を考慮して、ユーザーが閲覧可能な未読の項目数を計算します。
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $totalUnreadCount = 0;

        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            // ユーザーが所属するロールのIDリストを取得
            $userRoleIds = $user->roles()->pluck('id');

            // --- 1. 自分が閲覧可能なすべての投稿IDを取得 ---
            //    BoardPostPolicyの閲覧ロジックをクエリで再現します。
            $viewablePostQuery = BoardPost::query();

            $viewablePostQuery->where(function ($query) use ($userId, $userRoleIds) {
                $query
                    // a. 自分が投稿者の投稿
                    ->where('user_id', $userId)
                    // b. 全公開の投稿 (ロール指定がなく、個別ユーザー指定もない)
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNull('role_id')
                                 ->whereDoesntHave('readableUsers');
                    })
                    // c. 閲覧可能ユーザーとして自分が直接指定されている投稿
                    ->orWhereHas('readableUsers', function ($subQuery) use ($userId) {
                        $subQuery->where('user_id', $userId);
                    })
                    // d. 自分の所属するロールが閲覧対象として指定されている投稿
                    ->orWhereIn('role_id', $userRoleIds);
            });

            $viewablePostIds = $viewablePostQuery->pluck('id');


            // --- 2. 上記（閲覧可能な投稿）のうち、未読の投稿数をカウント ---

            // 自分が作成した投稿のIDリストを取得（これらは未読カウントに含めない）
            $myPostIds = BoardPost::where('user_id', $userId)->pluck('id');

            // 既に読んだ投稿（read_atが記録されている）のIDリストを取得
            $readPostIds = BoardPostRead::where('user_id', $userId)
                                       ->whereNotNull('read_at')
                                       ->pluck('board_post_id');

            // 「閲覧可能な投稿」から「自分が作成した投稿」と「既読の投稿」を除外して、未読投稿数を算出
            $unreadPostCount = $viewablePostIds->diff($myPostIds)->diff($readPostIds)->count();


            // --- 3. 未読のコメントメンション数をカウント ---
            // コメントでのメンションは、投稿自体の閲覧権限とは別の「通知」として扱います。
            // これにより、権限が後から変更された投稿内のコメントメンションも通知として補足できます。
            $unreadCommentMentionCount = BoardCommentRead::where('user_id', $userId)
                                                         ->whereNull('read_at')
                                                         ->count();


            // --- 4. 合計 ---
            // 未読の投稿数と、未読のコメントメンション数を合計
            $totalUnreadCount = $unreadPostCount + $unreadCommentMentionCount;
        }

        // 計算した合計値をビューに渡す
        $view->with('unreadMentionsCountGlobal', $totalUnreadCount);
    }
}
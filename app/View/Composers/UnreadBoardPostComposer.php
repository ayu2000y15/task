<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\BoardPost;
use App\Models\BoardPostRead;
use Illuminate\Support\Facades\Auth;
use App\Models\BoardCommentRead;

class UnreadBoardPostComposer
{
    public function compose(View $view)
    {
        $totalUnreadCount = 0; // ★ 合計値を格納する変数名を分かりやすく変更
        if (Auth::check()) {
            $userId = Auth::id();

            // --- 1. 未読の「投稿メンション」数をカウント（既存のロジック） ---
            $unreadPostMentionCount = BoardPostRead::where('user_id', $userId)
                ->whereNull('read_at')
                ->count();

            // --- 2. 未読の「コメントメンション」数をカウント（既存のロジック） ---
            $unreadCommentMentionCount = BoardCommentRead::where('user_id', $userId)
                ->whereNull('read_at')
                ->count();

            // --- 3. ★【追加】メンション以外の未読投稿数をカウントするロジック ---
            // まず、ログインユーザーが既読、またはメンションされている投稿のIDリストを取得します。
            $relatedPostIds = BoardPostRead::where('user_id', $userId)->pluck('board_post_id');

            // BoardPostReadテーブルに関連レコードがなく、かつ自分が作成していない投稿を「未読の一般投稿」としてカウントします。
            $unreadGeneralPostCount = BoardPost::where('user_id', '!=', $userId)      // 自分が作成した投稿は除く
                ->whereNotIn('id', $relatedPostIds) // 既読/メンション済みの投稿は除く
                ->count();

            // --- ★ 合計 ---
            // 3つのカウントを合計して、ユーザーが確認すべき全ての未読項目の総数を算出します。
            $totalUnreadCount = $unreadGeneralPostCount + $unreadPostMentionCount + $unreadCommentMentionCount;
        }

        // ★ 計算した合計値を 'unreadMentionsCountGlobal' としてビューに渡します。
        //    (Blade側の変数は変更不要です)
        $view->with('unreadMentionsCountGlobal', $totalUnreadCount);
    }
}

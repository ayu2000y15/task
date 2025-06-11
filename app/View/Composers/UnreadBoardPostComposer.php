<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\BoardPostRead; // モデルをインポート
use Illuminate\Support\Facades\Auth; // Authファサードをインポート
use App\Models\BoardCommentRead;

class UnreadBoardPostComposer
{
    public function compose(View $view)
    {
        $unreadMentionsCount = 0;
        if (Auth::check()) {
            $userId = Auth::id();
            // 未読の投稿メンション数をカウント
            $unreadPostCount = BoardPostRead::where('user_id', $userId)->whereNull('read_at')->count();
            // 未読のコメントメンション数をカウント
            $unreadCommentCount = BoardCommentRead::where('user_id', $userId)->whereNull('read_at')->count();
            // 合計する
            $unreadMentionsCount = $unreadPostCount + $unreadCommentCount;
        }
        $view->with('unreadMentionsCountGlobal', $unreadMentionsCount);
    }
}

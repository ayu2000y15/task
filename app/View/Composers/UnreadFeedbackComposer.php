<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Feedback; // Feedbackモデルをインポート
use Illuminate\Support\Facades\Auth; // Authファサードをインポート

class UnreadFeedbackComposer
{
    public function compose(View $view)
    {
        $unreadFeedbackCount = 0;
        // ログインしていて、かつユーザーがフィードバックを閲覧する権限がある場合のみ件数を取得
        // (権限設定は適宜調整してください。ここでは単純にログインユーザーであれば取得する例)
        if (Auth::check()) {
            // ここで 'viewAnyFeedbacks' のような権限チェックを入れるのが望ましい
            // if (Auth::user()->can('viewAny', Feedback::class)) {
            //     $unreadFeedbackCount = Feedback::where('status', Feedback::STATUS_UNREAD)->count();
            // }
            // 今回は権限設定は一旦不要とのことなので、ログインしていれば全管理者向けに件数を取得する想定
            $unreadFeedbackCount = Feedback::where('status', Feedback::STATUS_UNREAD)->count();
        }
        $view->with('unreadFeedbackCountGlobal', $unreadFeedbackCount);
    }
}

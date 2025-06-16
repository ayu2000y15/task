<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Request as TaskRequest; // 依頼モデルをインポート
use Illuminate\Support\Facades\Auth;   // Authファサードをインポート

class PendingRequestComposer
{
    /**
     * データをビューにバインドします。
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $pendingRequestsCount = 0;

        if (Auth::check()) {
            $user = Auth::user();

            // 自分に割り当てられ、かつ未完了の依頼件数を取得
            // ※他人から依頼されたもののみをカウント
            $pendingRequestsCount = TaskRequest::whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
                ->whereNull('completed_at')
                ->where('requester_id', '!=', $user->id) // 自分が依頼者のものは除外
                ->count();
        }

        // 全てのビューで $pendingRequestsCountGlobal 変数を利用できるようにする
        $view->with('pendingRequestsCountGlobal', $pendingRequestsCount);
    }
}

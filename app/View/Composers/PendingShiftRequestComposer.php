<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\ShiftChangeRequest; // 作成したModelをuse

class PendingShiftRequestComposer
{
    /**
     * ビューにデータをバインドします。
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $pendingCount = 0;

        if (Auth::check()) {
            $user = Auth::user();

            // ユーザーがシフト変更申請を承認する権限を持っているかチェック
            // Policyは後ほど作成しますが、ここでは 'approve' という権限名で仮定します。
            if ($user->can('approve', ShiftChangeRequest::class)) {
                // 'pending' (保留中) ステータスの申請件数をカウント
                $pendingCount = ShiftChangeRequest::where('status', 'pending')->count();
            }
        }

        // ビューに 'pendingShiftRequestsCountGlobal' という変数名で件数を渡す
        $view->with('pendingShiftRequestsCountGlobal', $pendingCount);
    }
}

<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\ExternalProjectSubmission; // モデルをインポート
use Illuminate\Support\Facades\Auth;      // Authファサードをインポート

class NewExternalSubmissionsComposer
{
    public function compose(View $view)
    {
        $newExternalSubmissionsCount = 0;
        if (Auth::check()) {
            // ユーザーが外部依頼一覧を閲覧する権限を持っているか確認
            // (ExternalProjectSubmissionPolicyのviewAnyメソッドで定義した権限を想定)
            if (Auth::user()->can('viewAny', ExternalProjectSubmission::class)) {
                $newExternalSubmissionsCount = ExternalProjectSubmission::where('status', 'new')->count();
            }
        }
        $view->with('newExternalSubmissionsCountGlobal', $newExternalSubmissionsCount);
    }
}

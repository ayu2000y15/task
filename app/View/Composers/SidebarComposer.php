<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SidebarComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view)
    {
        $favoriteProjects = collect();
        // ▼▼▼【変更箇所-START】▼▼▼
        $activeProjects = collect();
        $archivedProjects = collect();
        // ▲▲▲【変更箇所-END】▲▲▲
        $upcomingTasksForSidebar = collect();

        if (Auth::check()) {
            $user = Auth::user();
            $favoriteProjects = Project::where('is_favorite', true)
                ->orderBy('title')
                ->get();

            // ▼▼▼【変更箇所-START】▼▼▼
            // is_favorite = false のプロジェクトを全て取得
            $allNormalProjects = Project::where('is_favorite', false)
                ->orderBy('title')
                ->get();

            // 完了・キャンセルのステータスを定義
            $archivedStatus = ['completed', 'cancelled'];

            // ステータスを元に、プロジェクトを2つのコレクションに分割する
            list($archivedProjects, $activeProjects) = $allNormalProjects->partition(function ($project) use ($archivedStatus) {
                return in_array($project->status, $archivedStatus);
            });
            // ▲▲▲【変更箇所-END】▲▲▲

            $upcomingTasksForSidebar = Task::where(function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
                ->whereNotNull('end_date')
                // ->whereDate('end_date', '>=', Carbon::today()) // この行を削除またはコメントアウト
                ->whereDate('end_date', '<=', Carbon::today()->addDays(2))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where('is_milestone', false)
                ->where('is_folder', false)
                ->orderBy('end_date')
                ->with('project')
                ->get();
        }

        // ▼▼▼【変更箇所-START】▼▼▼
        // viewに渡す変数を変更
        $view->with(compact('favoriteProjects', 'activeProjects', 'archivedProjects', 'upcomingTasksForSidebar'));
        // ▲▲▲【変更箇所-END】▲▲▲
    }
}

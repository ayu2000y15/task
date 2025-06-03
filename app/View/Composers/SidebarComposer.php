<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Project; // Projectモデルをuse
use App\Models\Task;    // Taskモデルを追加
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;      // Carbonを追加

class SidebarComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $favoriteProjects = collect();
        $normalProjects = collect();
        $upcomingTasksForSidebar = collect(); // 期限間近の工程を初期化

        if (Auth::check()) {
            $favoriteProjects = Project::where('is_favorite', true)
                // ->where('user_id', Auth::id()) // もしユーザーごとの案件なら
                ->orderBy('title')
                ->get();
            $normalProjects = Project::where('is_favorite', false)
                // ->where('user_id', Auth::id()) // もしユーザーごとの案件なら
                ->orderBy('title')
                ->get();

            // 期限間近の工程を取得 (例: 2日以内、最大10件)
            $upcomingTasksForSidebar = Task::whereNotNull('end_date')
                ->whereDate('end_date', '>=', Carbon::today())
                ->whereDate('end_date', '<=', Carbon::today()->addDays(2))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where('is_milestone', false)
                ->where('is_folder', false)
                ->orderBy('end_date')
                ->with('project') // プロジェクト情報を Eager load
                ->get();
        }

        $view->with(compact('favoriteProjects', 'normalProjects', 'upcomingTasksForSidebar'));
    }
}

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
        $activeProjects = collect();
        $archivedProjects = collect();
        $activeProjectsByCategory = collect();
        $archivedProjectsByCategory = collect();
        $projectCategories = collect();
        $upcomingTasksForSidebar = collect();

        if (Auth::check()) {
            $user = Auth::user();
            $favoriteProjects = Project::where('is_favorite', true)
                ->orderBy('title')
                ->get();

            // ▼▼▼【変更箇所-START】▼▼▼
            // is_favorite = false のプロジェクトを全て取得（カテゴリリレーションを含む）
            $allNormalProjects = Project::where('is_favorite', false)
                ->with('projectCategory')
                ->orderBy('title')
                ->get();

            // 完了・キャンセルのステータスを定義
            $archivedStatus = ['completed', 'cancelled'];

            // ステータスを元に、プロジェクトを2つのコレクションに分割する
            list($archivedProjects, $activeProjects) = $allNormalProjects->partition(function ($project) use ($archivedStatus) {
                return in_array($project->status, $archivedStatus);
            });

            // カテゴリ別にグループ化（未分類を最後にするためのソート付き）
            $activeProjectsByCategory = $activeProjects->groupBy(function ($project) {
                return $project->projectCategory ? $project->projectCategory->name : 'uncategorized';
            })->sortBy(function ($projects, $categoryKey) use ($projectCategories) {
                if ($categoryKey === 'uncategorized') {
                    return 999999; // 未分類を最後に
                }
                $category = $projectCategories->where('name', $categoryKey)->first();
                return $category ? $category->display_order : 999998;
            });

            $archivedProjectsByCategory = $archivedProjects->groupBy(function ($project) {
                return $project->projectCategory ? $project->projectCategory->name : 'uncategorized';
            })->sortBy(function ($projects, $categoryKey) use ($projectCategories) {
                if ($categoryKey === 'uncategorized') {
                    return 999999; // 未分類を最後に
                }
                $category = $projectCategories->where('name', $categoryKey)->first();
                return $category ? $category->display_order : 999998;
            });

            // プロジェクトカテゴリ一覧も取得（display_order順）
            $projectCategories = \App\Models\ProjectCategory::orderBy('display_order')->orderBy('name')->get();
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
                ->whereHas('project', function ($query) {
                    $query->where('status', '!=', 'cancelled'); // ★キャンセルされた案件のタスクを除外
                })
                ->where('is_milestone', false)
                ->where('is_folder', false)
                ->orderBy('end_date')
                ->with('project')
                ->get();
        }

        // ▼▼▼【変更箇所-START】▼▼▼
        // viewに渡す変数を変更
        $view->with(compact('favoriteProjects', 'activeProjects', 'archivedProjects', 'activeProjectsByCategory', 'archivedProjectsByCategory', 'projectCategories', 'upcomingTasksForSidebar'));
        // ▲▲▲【変更箇所-END】▲▲▲
    }
}

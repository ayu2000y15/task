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
        $projectCategories = \App\Models\ProjectCategory::orderBy('display_order')->orderBy('name')->get();
        $upcomingTasksForSidebar = collect();


        if (Auth::check()) {
            $user = Auth::user();
            $favoriteProjects = Project::where('is_favorite', true)
                ->orderBy('title')
                ->get();

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
            $activeProjectsByCategory = collect();
            $archivedProjectsByCategory = collect();

            // カテゴリ順にグループ化
            foreach ($projectCategories as $category) {
                $catName = $category->name;
                $activeGroup = $activeProjects->filter(function ($p) use ($catName) {
                    return $p->projectCategory && $p->projectCategory->name === $catName;
                });
                if ($activeGroup->isNotEmpty()) {
                    $activeProjectsByCategory[$catName] = $activeGroup;
                }
                $archivedGroup = $archivedProjects->filter(function ($p) use ($catName) {
                    return $p->projectCategory && $p->projectCategory->name === $catName;
                });
                if ($archivedGroup->isNotEmpty()) {
                    $archivedProjectsByCategory[$catName] = $archivedGroup;
                }
            }
            // 未分類（カテゴリなし）は最後に
            $uncatActive = $activeProjects->filter(function ($p) {
                return !$p->projectCategory;
            });
            if ($uncatActive->isNotEmpty()) {
                $activeProjectsByCategory['uncategorized'] = $uncatActive;
            }
            $uncatArchived = $archivedProjects->filter(function ($p) {
                return !$p->projectCategory;
            });
            if ($uncatArchived->isNotEmpty()) {
                $archivedProjectsByCategory['uncategorized'] = $uncatArchived;
            }

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
                    $query->whereNotIn('status', ['completed', 'cancelled']); // ★完了・キャンセルされた案件のタスクを除外
                    $query->where('delivery_flag', 0); // ★ 納品済み案件のタスクを除外
                    $query->whereNotNull('project_category_id'); // ★ カテゴリ未設定の案件を除外
                })->whereHas('project.projectCategory', function ($q) {
                    $q->where('name', '<>', 'other');
                })
                ->where('is_milestone', false)
                ->where('is_folder', false)
                ->orderBy('end_date')
                ->with('project')
                ->get();
        }

        $view->with(compact('favoriteProjects', 'activeProjects', 'archivedProjects', 'activeProjectsByCategory', 'archivedProjectsByCategory', 'projectCategories', 'upcomingTasksForSidebar'));
    }
}

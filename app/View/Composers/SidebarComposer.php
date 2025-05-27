<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Project; // Projectモデルをuse
use Illuminate\Support\Facades\Auth;


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
        if (Auth::check()) {
            $favoriteProjects = Project::where('is_favorite', true)
                // ->where('user_id', Auth::id()) // もしユーザーごとの案件なら
                ->orderBy('title')
                ->get();
            $normalProjects = Project::where('is_favorite', false)
                // ->where('user_id', Auth::id()) // もしユーザーごとの案件なら
                ->orderBy('title')
                ->get();
        } else {
            $favoriteProjects = collect();
            $normalProjects = collect();
        }


        $view->with(compact('favoriteProjects', 'normalProjects'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * ホーム画面を表示
     */
    public function index()
    {
        Carbon::setLocale('ja');
        $projectCount = Project::count();
        $activeProjectCount = Project::where('end_date', '>=', Carbon::today())->count();
        $taskCount = Task::count();

        $recentTasks = Task::whereNotNull('end_date') // end_dateがnullでない工程のみ対象
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderBy('end_date')
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->limit(10)
            ->get();

        $upcomingTasks = Task::whereNotNull('end_date') // end_dateがnullでない工程のみ対象
            ->whereDate('end_date', '>=', Carbon::today())
            ->whereDate('end_date', '<=', Carbon::today()->addDays(7))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('end_date')
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->limit(5)
            ->get();

        // ToDoリスト用の工程
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        $todoTasks = Task::whereNull('start_date')
            ->whereNull('end_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $inProgressTasks = Task::whereNull('start_date')
            ->whereNull('end_date')
            ->where('status', 'in_progress')
            // ->whereBetween('updated_at', [$sevenDaysAgo, $todayEnd]) // 例：もし更新日で絞るなら
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('updated_at', 'desc') // 例：更新が新しい順
            ->limit(10)
            ->get();

        $onHoldTasks = Task::whereNull('start_date')
            ->whereNull('end_date')
            ->where('status', 'on_hold')
            // ->whereBetween('updated_at', [$sevenDaysAgo, $todayEnd]) // 例：もし更新日で絞るなら
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('updated_at', 'desc') // 例：更新が新しい順
            ->limit(10)
            ->get();

        return view('home.index', compact(
            'projectCount',
            'activeProjectCount',
            'taskCount',
            'recentTasks',
            'upcomingTasks',
            'todoTasks',
            'inProgressTasks',
            'onHoldTasks'
        ));
    }
}

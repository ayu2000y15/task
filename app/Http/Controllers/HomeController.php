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
        $this->authorize('viewAny', Project::class);
        Carbon::setLocale('ja');
        $projectCount = Project::count();
        $activeProjectCount = Project::where('end_date', '>=', Carbon::today())->count();
        $taskCount = Task::count();

        $recentTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderBy('end_date')
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->limit(10)
            ->get();

        // ▼▼▼【変更】取得期間を7日から2日に修正 ▼▼▼
        $upcomingTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', Carbon::today())
            ->whereDate('end_date', '<=', Carbon::today()->addDays(2))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('end_date')
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->limit(5)
            ->get();

        // ToDoリスト用の工程
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        $todoTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNull('start_date')
            ->whereNull('end_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $inProgressTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNull('start_date')
            ->whereNull('end_date')
            ->where('status', 'in_progress')
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $onHoldTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNull('start_date')
            ->whereNull('end_date')
            ->where('status', 'on_hold')
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('updated_at', 'desc')
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

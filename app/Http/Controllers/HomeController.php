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
        // プロジェクト数
        $projectCount = Project::count();


        // 進行中のプロジェクト数（終了日が今日以降のプロジェクト）
        $activeProjectCount = Project::where('end_date', '>=', Carbon::today())->count();

        // タスク数
        $taskCount = Task::count();

        // 最近のタスク（直近の終了日のタスク10件）
        $recentTasks = Task::whereDate('end_date', '>=', Carbon::today())
            ->orderBy('end_date')
            ->limit(10)
            ->get();

        // 期限間近のタスク（1週間以内に終了するタスク）
        $upcomingTasks = Task::whereDate('end_date', '>=', Carbon::today())
            ->whereDate('end_date', '<=', Carbon::today()->addDays(7))
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->orderBy('end_date')
            ->limit(5)
            ->get();

        // ToDoリスト用のタスク
        $todoTasks = Task::where('status', 'not_started')
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        $inProgressTasks = Task::where('status', 'in_progress')
            ->orderBy('end_date')
            ->limit(10)
            ->get();

        $onHoldTasks = Task::where('status', 'on_hold')
            ->orderBy('end_date')
            ->limit(10)
            ->get();

        $completedTasks = Task::where('status', 'completed')
            ->orderBy('end_date', 'desc')
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
            'onHoldTasks',
            'completedTasks'
        ));
    }
}

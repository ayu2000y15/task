<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // ★ Requestをuse
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use App\Models\UserHoliday;
use App\Models\Request as TaskRequest;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request // ★ Requestを受け取る
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);
        Carbon::setLocale('ja');

        // ★ リクエストから日付を取得、なければ今日の日付を使用
        $targetDate = $request->date('date', 'Y-m-d') ?? Carbon::today();

        // 1. 指定された日付の休日取得者を取得
        $todaysHolidays = UserHoliday::with('user')->where('date', $targetDate)->get()->filter(fn($h) => $h->user);

        // 2. 担当者別の「やることリスト」を格納する配列を準備
        $workItemsByAssignee = [];

        // --- データソースA: 指定日のタスクを取得 ---
        $todaysTasks = Task::with(['project', 'character', 'assignees'])
            ->where('is_milestone', false)->where('is_folder', false)
            ->whereNotIn('status', ['cancelled']) // ★完了タスクは除外
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->get();

        foreach ($todaysTasks as $task) {
            foreach ($task->assignees as $assignee) {
                // if ($todaysHolidays->contains('user_id', $assignee->id)) continue;
                if (!isset($workItemsByAssignee[$assignee->id])) {
                    $workItemsByAssignee[$assignee->id] = ['assignee' => $assignee, 'items' => collect()];
                }
                $workItemsByAssignee[$assignee->id]['items']->push($task);
            }
        }
        // --- データソースB: ピックアップされた依頼項目を取得 ---
        $myDayItems = RequestItem::where('my_day_date', $targetDate->format('Y-m-d'))
            ->with(['request.assignees', 'request.requester'])
            ->get();

        foreach ($myDayItems as $item) {
            foreach ($item->request->assignees as $assignee) {
                // if ($todaysHolidays->contains('user_id', $assignee->id)) continue;
                if (!isset($workItemsByAssignee[$assignee->id])) {
                    $workItemsByAssignee[$assignee->id] = ['assignee' => $assignee, 'items' => collect()];
                }
                if (!$workItemsByAssignee[$assignee->id]['items']->contains('id', 'request_item_' . $item->id)) {
                    $workItemsByAssignee[$assignee->id]['items']->push($item);
                }
            }
        }

        uasort($workItemsByAssignee, fn($a, $b) => strcmp($a['assignee']->name, $b['assignee']->name));

        // (その他のデータ取得は変更なし)
        $projectCount = Project::count();
        $activeProjectCount = Project::where('status', 'in_progress')->count();
        $taskCount = Task::count();
        $upcomingTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $targetDate)
            ->whereDate('end_date', '<=', $targetDate->copy()->addDays(2))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('end_date')
            ->limit(5)
            ->get();

        return view('home.index', compact(
            'projectCount',
            'activeProjectCount',
            'taskCount',
            'upcomingTasks',
            'todaysHolidays',
            'workItemsByAssignee',
            'targetDate' // ★ Viewに日付を渡す
        ));
    }
}

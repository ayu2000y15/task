<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // ★ Requestをuse
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use App\Models\WorkShift;
use App\Models\Request as TaskRequest;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkLog;
use App\Services\ProductivityService;
use App\Models\AttendanceLog;

class HomeController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request // ★ Requestを受け取る
     */
    public function index(Request $request, ProductivityService $productivityService)
    {
        $this->authorize('viewAny', Project::class);
        Carbon::setLocale('ja');

        $user = Auth::user();

        // ★ リクエストから日付を取得、なければ今日の日付を使用
        $targetDate = $request->date('date', 'Y-m-d') ?? Carbon::today();

        // 1. 指定された日付の休日取得者を取得
        $todaysHolidays = WorkShift::with('user')
            ->where('date', $targetDate)
            ->whereIn('type', ['full_day_off', 'am_off', 'pm_off']) // 休日タイプのものを取得
            ->get()
            ->filter(fn($shift) => $shift->user);

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
        $myDayItems = RequestItem::where('start_at', $targetDate->format('Y-m-d'))
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

        // 出勤中のユーザー情報を取得
        $searchWindowStart = $targetDate->copy()->endOfDay()->subHours(48);

        $latestAttendanceLogs = AttendanceLog::with('user')
            ->whereHas('user', function ($query) {
                // 有効なユーザーのみを対象とする
                $query->where('status', '!=', 'inactive');
            })
            // 検索範囲を48時間に広げる
            ->where('timestamp', '>=', $searchWindowStart)
            // 表示している日付より未来のログは含めない
            ->where('timestamp', '<=', $targetDate->copy()->endOfDay())
            ->orderBy('timestamp', 'desc')
            ->get()
            // 各ユーザーの最新のログ1件に絞り込む
            ->unique('user_id');

        // 最新のログが 'clock_out' (退勤) でないユーザーを「オンライン」とみなし、表示用のステータスを付与する
        $onlineUsers = $latestAttendanceLogs
            ->where('type', '!=', 'clock_out')
            ->filter(fn($log) => $log->user) // ユーザーがnullでないことを確認
            ->map(function ($log) {
                // 勤怠ログのタイプから表示用のステータスを決定
                $log->current_status = match ($log->type) {
                    'break_start' => 'on_break',
                    'away_start'  => 'on_away',
                    default       => 'working', // 'clock_in', 'break_end', 'away_end' は 'working' 扱い
                };
                return $log;
            })
            ->sortBy(fn($log) => $log->user->name); // ユーザー名でソート

        // (その他のデータ取得は変更なし)
        $projectCount = Project::count();
        $activeProjectCount = Project::where('status', 'in_progress')->count();
        $taskCount = Task::count();
        $upcomingTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNotNull('end_date')
            // ->whereDate('end_date', '>=', $targetDate) // この行を削除して、期限切れも対象に
            ->whereDate('end_date', '<=', $targetDate->copy()->addDays(2))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('end_date')
            // ->limit(5)
            ->get();

        // 現在進行中（タイマーが作動中）の作業ログを取得します
        // タスクごとに最新の1件に絞り込み、関連データも一緒に読み込みます
        $runningWorkLogs = WorkLog::where('status', 'active')
            ->with(['task.project', 'task.assignees', 'user'])
            ->latest('start_time') // 新しく開始されたものを上に
            ->get()
            ->unique('task_id'); // 同じタスクが複数あっても1つにまとめる

        $productivitySummaries = $productivityService->getSummariesForAllActiveUsers();

        return view('home.index', compact(
            'projectCount',
            'activeProjectCount',
            'taskCount',
            'upcomingTasks',
            'todaysHolidays',
            'workItemsByAssignee',
            'targetDate',
            'runningWorkLogs',
            'productivitySummaries',
            'onlineUsers'
        ));
    }
}

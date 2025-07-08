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
use App\Models\DefaultShiftPattern;

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
        $todaysTasks = Task::with(['project', 'character', 'assignees', 'workLogs'])
            ->where('is_milestone', false)->where('is_folder', false)
            ->whereNotIn('status', ['completed', 'cancelled']) // ★完了・キャンセルタスクは除外
            ->whereHas('project', function ($query) {
                $query->where('status', '!=', 'cancelled'); // ★キャンセルされた案件のタスクを除外
            })
            ->where(function ($query) use ($targetDate) {
                // 指定日に進行中のタスク、または期限切れの未完了タスクを取得
                $query->where(function ($q) use ($targetDate) {
                    // 指定日に進行中のタスク
                    $q->whereDate('start_date', '<=', $targetDate)
                        ->whereDate('end_date', '>=', $targetDate);
                })->orWhere(function ($q) use ($targetDate) {
                    // 期限切れの未完了タスク（開始日が指定日以前で、終了日が指定日より前）
                    $q->whereDate('start_date', '<=', $targetDate)
                        ->whereDate('end_date', '<', $targetDate);
                });
            })
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

        // ▼▼▼【ここから追加】1週間以内の予定を取得 ▼▼▼
        $upcomingSchedulesByAssignee = [];
        $upcomingRequests = TaskRequest::with(['assignees'])
            ->whereNull('completed_at') // まだ完了していない
            ->whereNotNull('start_at')  // 開始日が設定されている
            ->whereBetween('start_at', [Carbon::today(), Carbon::today()->addDays(7)]) // 今日から7日後まで
            ->orderBy('start_at')
            ->get();

        foreach ($upcomingRequests as $schedule) {
            foreach ($schedule->assignees as $assignee) {
                if (!isset($upcomingSchedulesByAssignee[$assignee->id])) {
                    $upcomingSchedulesByAssignee[$assignee->id] = [
                        'assignee' => $assignee,
                        'schedules' => collect()
                    ];
                }
                $upcomingSchedulesByAssignee[$assignee->id]['schedules']->push($schedule);
            }
        }
        // ユーザー名でソート
        uasort($upcomingSchedulesByAssignee, fn($a, $b) => strcmp($a['assignee']->name, $b['assignee']->name));
        // ▲▲▲【追加ここまで】▲▲▲


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

        $onlineUserIds = $onlineUsers->pluck('user.id')->filter();

        if ($onlineUserIds->isNotEmpty()) {
            $today = Carbon::today();
            $dayOfWeek = $today->dayOfWeek;

            // 1. オンラインユーザーの今日の個別シフト設定を取得
            $workShiftsToday = WorkShift::whereIn('user_id', $onlineUserIds)
                ->where('date', $today)
                ->get()
                ->keyBy('user_id');

            // 2. 今日の曜日に対応するデフォルトパターンを取得
            $defaultPatternsToday = DefaultShiftPattern::whereIn('user_id', $onlineUserIds)
                ->where('day_of_week', $dayOfWeek)
                ->get()
                ->keyBy('user_id');

            // 3. 各オンラインユーザーに勤務場所情報を追加
            foreach ($onlineUsers as $log) {
                if ($user = $log->user) {
                    $location = ''; // デフォルトは「出勤」

                    $override = $workShiftsToday->get($user->id);
                    $default = $defaultPatternsToday->get($user->id);

                    // 個別シフト設定（時間変更 or 場所のみ変更）が最優先
                    if ($override && in_array($override->type, ['work', 'location_only']) && $override->location) {
                        $location = $override->location;
                    }
                    // 個別設定がなく、デフォルトが出勤日になっている場合
                    elseif (!$override && $default && $default->is_workday) {
                        $location = $default->location;
                    }

                    // ユーザーオブジェクトにプロパティとして場所情報を追加
                    $user->todays_location = $location;
                }
            }
        }

        // (その他のデータ取得は変更なし)
        $projectCount = Project::count();
        $activeProjectCount = Project::where('status', 'in_progress')->count();
        $taskCount = Task::count();
        $upcomingTasks = Task::with(['project', 'character', 'assignees'])
            ->whereNotNull('end_date')
            // ->whereDate('end_date', '>=', $targetDate) // この行を削除して、期限切れも対象に
            ->whereDate('end_date', '<=', $targetDate->copy()->addDays(2))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('project', function ($query) {
                $query->where('status', '!=', 'cancelled'); // ★キャンセルされた案件のタスクを除外
            })
            ->where('is_milestone', false)
            ->where('is_folder', false)
            ->orderBy('end_date')
            // ->limit(5)
            ->get();

        // 現在進行中（タイマーが作動中）の作業ログを取得します
        // タスクごとに最新の1件に絞り込み、関連データも一緒に読み込みます
        $allActiveLogs = WorkLog::where('status', 'active')
            ->with(['task.project', 'task.assignees', 'user']) // 必要なリレーションを全てここで読み込む
            ->whereHas('task.project', function ($query) {
                $query->where('status', '!=', 'cancelled'); // ★キャンセルされた案件のタスクを除外
            })
            ->get();

        // 作業中の全ユーザーIDの配列（オンラインメンバー表示用）
        $workingUserIds = $allActiveLogs->pluck('user_id')->unique()->all();

        // 表示用のユニークなタスクリストを作成（同じタスクは1つにまとめる）
        $runningWorkLogs = $allActiveLogs->unique('task_id');

        // タスクIDをキー、作業中ユーザーのコレクションをバリューとするマップを作成
        $workingUsersByTask = $allActiveLogs
            ->filter(fn($log) => $log->user && $log->task)
            ->groupBy('task_id')
            ->map(fn($logs) => $logs->pluck('user'));

        // 表示用リストの各タスクに、作業中ユーザーの情報をプロパティとして付加する
        $runningWorkLogs->each(function ($log) use ($workingUsersByTask) {
            if ($log->task) {
                $log->task->workingUsers = $workingUsersByTask->get($log->task_id, collect());
            }
        });

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
            'onlineUsers',
            'workingUserIds',
            'upcomingSchedulesByAssignee' // ★ 追加した変数をビューに渡す
        ));
    }
}

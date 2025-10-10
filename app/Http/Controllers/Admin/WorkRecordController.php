<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkLog;
use App\Models\User;
use App\Models\Project;
use App\Models\HourlyRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Attendance;
use App\Models\AttendanceLog;

class WorkRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', WorkLog::class);

        // 手動修正を考慮した動的なカラムを定義
        $startTimeSql = "IF(work_logs.is_manually_edited, work_logs.edited_start_time, work_logs.start_time)";
        $endTimeSql = "IF(work_logs.is_manually_edited, work_logs.edited_end_time, work_logs.end_time)";
        $durationSql = "TIMESTAMPDIFF(SECOND, $startTimeSql, $endTimeSql)";

        // ソート可能な列の定義を修正
        $sortableColumns = [
            'user' => 'users.name',
            'project' => 'projects.title',
            'character' => 'characters.name',
            'task' => 'tasks.name',
            'start_time' => DB::raw($startTimeSql),
            'end_time' => DB::raw($endTimeSql),
            'duration' => DB::raw($durationSql),
            'salary' => 'calculated_salary',
        ];

        $sort = $request->query('sort', 'start_time');
        $direction = $request->query('direction', 'desc');

        if (!array_key_exists($sort, $sortableColumns)) {
            $sort = 'start_time';
            $direction = 'desc';
        }

        // 時給取得用のサブクエリ
        $rateSubQuery = HourlyRate::select('rate')
            ->whereColumn('user_id', 'users.id')
            ->where('effective_date', '<=', DB::raw("date($startTimeSql)"))
            ->orderBy('effective_date', 'desc')
            ->limit(1);

        $query = WorkLog::query()
            ->join('users', 'work_logs.user_id', '=', 'users.id')
            ->join('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->leftJoin('characters', 'tasks.character_id', '=', 'characters.id')
            ->select('work_logs.*') // work_logsの全カラムを選択
            ->selectSub($rateSubQuery, 'current_rate')
            ->selectRaw("($durationSql / 3600 * (COALESCE(({$rateSubQuery->toSql()}), 0))) as calculated_salary")
            ->mergeBindings($rateSubQuery->getQuery())
            ->where('work_logs.status', 'stopped');

        // フィルター処理
        if ($request->filled('user_id')) {
            $query->where('work_logs.user_id', $request->user_id);
        }
        if ($request->filled('project_id')) {
            $query->where('tasks.project_id', $request->project_id);
        }
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;
        if ($startDate) {
            $query->where(DB::raw($startTimeSql), '>=', $startDate);
        }
        if ($endDate) {
            $query->where(DB::raw($startTimeSql), '<=', $endDate);
        }


        // フィルタ後の合計時間を計算
        $totalSecondsQuery = clone $query;
        // DB::raw() を使って合計を計算する
        $totalSeconds = $totalSecondsQuery->sum(DB::raw($durationSql));

        // ソート適用
        $query->orderBy($sortableColumns[$sort], $direction);
        if ($sort !== 'start_time') {
            $query->orderBy(DB::raw($startTimeSql), 'desc');
        }

        $workLogs = $query->with(['user.hourlyRates', 'task.project', 'task.character'])->paginate(50)->withQueryString();

        // (月の給与サマリーやフィルター用データ取得部分は変更なし)
        $targetMonth = $request->has('month') ? Carbon::parse($request->input('month')) : Carbon::now();
        $summaryDateStrings = ['month' => $targetMonth->format('Y年n月')];
        $monthSummary = $this->calculateMonthlyAttendanceSummary($targetMonth);
        $users = User::with(['hourlyRates' => fn($q) => $q->orderBy('effective_date', 'desc')->limit(2)])->orderBy('name')->get();
        $projects = Project::orderBy('title')->get();

        return view('admin.work-records.index', compact(
            'workLogs',
            'users',
            'projects',
            'totalSeconds',
            'monthSummary',
            'summaryDateStrings',
            'sort',
            'direction',
            'targetMonth'
        ));
    }

    /**
     * ユーザーの時給を一括で登録・更新する
     */
    public function updateUserRates(Request $request)
    {
        $validated = $request->validate([
            'rates' => 'present|array',
            'rates.*.user_id' => 'required|exists:users,id',
            'rates.*.rate' => 'nullable|numeric|min:0',
            'rates.*.effective_date' => 'nullable|date',
        ], [
            'rates.*.rate.numeric' => '時給は数値で入力してください。',
            'rates.*.effective_date.date' => '適用日は有効な日付を入力してください。',
        ]);

        $updateCount = 0;
        foreach ($validated['rates'] as $rateData) {
            // 時給と適用日の両方が入力されている行のみを処理する
            if (!empty($rateData['rate']) && !empty($rateData['effective_date'])) {
                HourlyRate::updateOrCreate(
                    [
                        'user_id' => $rateData['user_id'],
                        'effective_date' => $rateData['effective_date'],
                    ],
                    [
                        'rate' => $rateData['rate'],
                    ]
                );
                $updateCount++;
            }
        }

        if ($updateCount > 0) {
            return redirect()->route('admin.work-records.index')->with('success', $updateCount . '件の時給情報を登録/更新しました。');
        } else {
            return redirect()->route('admin.work-records.index')->with('info', '更新する時給情報が入力されていませんでした。');
        }
    }

    /**
     * 勤怠(Attendance)ベースの月次サマリーを計算するメソッド
     * 勤怠明細ページの計算ロジックと完全に同期させる
     */
    private function calculateMonthlyAttendanceSummary(Carbon $targetMonth)
    {
        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        // --- 1. 必要なデータを全て取得 ---
        // ユーザーと時給情報
        $users = User::with(['hourlyRates' => function ($query) {
            $query->orderBy('effective_date', 'desc');
        }])->get();

        // 対象月の勤怠関連データ
        $workLogs = WorkLog::where('status', 'stopped')->whereBetween('start_time', [$startOfMonth, $endOfMonth])->get()->groupBy('user_id');
        $attendanceLogs = AttendanceLog::whereBetween('timestamp', [$startOfMonth, $endOfMonth])->orderBy('timestamp')->get()->groupBy('user_id');
        $overriddenAttendances = Attendance::where('status', 'edited')->whereBetween('date', [$startOfMonth, $endOfMonth])->get()->groupBy('user_id');

        $byUserSummary = [];
        $grandTotalDetentionSeconds = 0;
        $grandTotalBreakSeconds = 0;
        $grandTotalActualWorkSeconds = 0;
        $grandTotalSalary = 0;

        foreach ($users as $user) {
            $userAttendances = $overriddenAttendances->get($user->id, collect());
            $userAttendanceLogs = $attendanceLogs->get($user->id, collect());
            $userWorkLogs = $workLogs->get($user->id, collect());

            // ユーザーにその月のデータがなければスキップ
            if ($userAttendances->isEmpty() && $userAttendanceLogs->isEmpty()) {
                continue;
            }

            // ユーザーごとの合計値を初期化
            $userTotalDetention = 0;
            $userTotalBreak = 0;
            $userTotalActualWork = 0; // = Payable Work Seconds
            $userTotalSalary = 0;

            // --- 2. ユーザーごとに月の全日分のデータを処理 ---
            for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
                $currentDate = $targetMonth->copy()->day($day);
                $dateString = $currentDate->format('Y-m-d');

                $manualAttendance = $userAttendances->firstWhere('date', $currentDate);
                $dayAttendanceLogs = $userAttendanceLogs->filter(fn($log) => $log->timestamp->isSameDay($currentDate));

                if ($manualAttendance) {
                    // (A) 手動編集データがある場合
                    $userTotalDetention += $manualAttendance->detention_seconds;
                    $userTotalBreak += $manualAttendance->break_seconds;
                    // actual_work_secondsは「支払対象時間」として扱う
                    $userTotalActualWork += $manualAttendance->actual_work_seconds;
                    $userTotalSalary += $manualAttendance->daily_salary;
                } elseif ($dayAttendanceLogs->isNotEmpty()) {
                    // (B) 打刻ログから自動計算する場合
                    $sessions = $this->calculateSessionsFromLogs($dayAttendanceLogs, $user);
                    foreach ($sessions as $session) {
                        $userTotalDetention += $session['detention_seconds'];
                        $userTotalBreak += $session['break_seconds'];
                        $userTotalActualWork += $session['payable_work_seconds'];
                        $userTotalSalary += $session['daily_salary'];
                    }
                }
            }

            // 最新（月末時点）のレートを表示用として取得
            $rateForMonth = $user->getHourlyRateForDate($endOfMonth);

            $byUserSummary[] = [
                'user' => $user,
                'rate' => $rateForMonth,
                'total_detention_seconds' => $userTotalDetention,
                'total_break_seconds' => $userTotalBreak,
                'total_actual_work_seconds' => $userTotalActualWork,
                'total_salary' => $userTotalSalary,
            ];

            // 総合計に加算
            $grandTotalDetentionSeconds += $userTotalDetention;
            $grandTotalBreakSeconds += $userTotalBreak;
            $grandTotalActualWorkSeconds += $userTotalActualWork;
            $grandTotalSalary += $userTotalSalary;
        }

        usort($byUserSummary, fn($a, $b) => strcmp($a['user']->name, $b['user']->name));

        return [
            'by_user' => $byUserSummary,
            'totals' => [
                'detention_seconds' => $grandTotalDetentionSeconds,
                'break_seconds' => $grandTotalBreakSeconds,
                'actual_work_seconds' => $grandTotalActualWorkSeconds,
                'salary' => $grandTotalSalary,
            ]
        ];
    }

    /**
     * AttendanceControllerから移植したヘルパーメソッド群
     * 打刻ログから勤務セッションごとの時間を計算する
     */
    private function calculateSessionsFromLogs($dayAttendanceLogs, $user)
    {
        $sessions = [];
        $currentSession = null;

        // 打刻ログを「出勤」「退勤」でセッションに分割
        foreach ($dayAttendanceLogs as $log) {
            if ($log->type === 'clock_in') {
                if ($currentSession) $sessions[] = $currentSession; // 前のセッションが未完了のままなら一旦確定
                $currentSession = ['start_time' => $log->timestamp, 'end_time' => null, 'logs' => collect([$log])];
            } elseif ($currentSession) {
                $currentSession['logs']->push($log);
                if ($log->type === 'clock_out') {
                    $currentSession['end_time'] = $log->timestamp;
                    $sessions[] = $currentSession;
                    $currentSession = null;
                }
            }
        }
        if ($currentSession) $sessions[] = $currentSession; // 最後のセッションを追加

        // 各セッションの時間を計算
        return collect($sessions)->map(function ($session) use ($user) {
            $startTime = $session['start_time'];
            $endTime = $session['end_time'];

            // 休憩・中抜け時間を計算
            $breakLogs = $this->pairLogEvents($session['logs'], 'break_start', 'break_end');
            $awayLogs = $this->pairLogEvents($session['logs'], 'away_start', 'away_end');
            $breakSeconds = collect(array_merge($breakLogs, $awayLogs))->sum('duration');

            $detentionSeconds = $endTime ? $startTime->diffInSeconds($endTime) : 0;
            $payableWorkSeconds = max(0, $detentionSeconds - $breakSeconds); // 給与計算対象時間
            $rateForDay = $user->getHourlyRateForDate($startTime);
            $dailySalary = $rateForDay > 0 ? round(($payableWorkSeconds / 3600) * $rateForDay) : 0; //

            return [
                'detention_seconds' => $detentionSeconds,
                'break_seconds' => $breakSeconds,
                'payable_work_seconds' => $payableWorkSeconds,
                'daily_salary' => $dailySalary,
            ];
        });
    }

    private function pairLogEvents($logs, $startType, $endType): array
    {
        $pairs = [];
        $startTime = null;
        foreach ($logs as $log) {
            if ($log->type === $startType) {
                $startTime = $log->timestamp;
            } elseif ($log->type === $endType && $startTime) {
                $pairs[] = [
                    'start_time' => $startTime,
                    'end_time' => $log->timestamp,
                    'duration' => $startTime->diffInSeconds($log->timestamp),
                ];
                $startTime = null;
            }
        }
        return $pairs;
    }

    public function show(Request $request, User $user)
    {
        $this->authorize('viewAny', WorkLog::class);

        try {
            $targetMonth = Carbon::parse($request->input('month', 'now'))->startOfMonth();
        } catch (\Exception $e) {
            $targetMonth = Carbon::now()->startOfMonth();
        }

        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        $workLogs = WorkLog::with('task.project')
            ->where('user_id', $user->id)
            ->where('status', 'stopped')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get();

        $logsByDay = $workLogs->groupBy(fn($log) => $log->start_time->format('Y-m-d'));

        $monthlyReport = [];
        $daysInMonth = $targetMonth->daysInMonth;

        $monthTotalActualWorkSeconds = 0;
        $monthTotalEffectiveSeconds = 0;
        $monthTotalSalary = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $targetMonth->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');

            if ($logsByDay->has($dateString)) {
                $dayLogs = $logsByDay[$dateString];

                $firstStartTime = $dayLogs->min('start_time');
                $lastEndTime = $dayLogs->max('end_time');
                $attendanceSeconds = $lastEndTime->getTimestamp() - $firstStartTime->getTimestamp();
                $totalEffectiveSeconds = $dayLogs->sum('effective_duration');

                $sortedLogs = $dayLogs->sortBy('start_time')->values();
                $actualWorkSeconds = 0;
                if ($sortedLogs->isNotEmpty()) {
                    $mergedStart = $sortedLogs->first()->start_time;
                    $mergedEnd = $sortedLogs->first()->end_time;

                    foreach ($sortedLogs->slice(1) as $log) {
                        if ($log->start_time < $mergedEnd) {
                            if ($log->end_time > $mergedEnd) {
                                $mergedEnd = $log->end_time;
                            }
                        } else {
                            $actualWorkSeconds += $mergedEnd->getTimestamp() - $mergedStart->getTimestamp();
                            $mergedStart = $log->start_time;
                            $mergedEnd = $log->end_time;
                        }
                    }
                    $actualWorkSeconds += $mergedEnd->getTimestamp() - $mergedStart->getTimestamp();
                }

                $breakSeconds = $attendanceSeconds - $actualWorkSeconds;

                $hourlyRate = $user->getHourlyRateForDate($currentDate);
                $dailySalary = $hourlyRate > 0 ? ($actualWorkSeconds / 3600) * $hourlyRate : 0;

                $monthTotalActualWorkSeconds += $actualWorkSeconds;
                $monthTotalEffectiveSeconds += $totalEffectiveSeconds;
                $monthTotalSalary += $dailySalary;

                $logsForView = $dayLogs->map(function ($log) {
                    return (object) [
                        'task' => $log->task,
                        'start_time' => $log->display_start_time,
                        'end_time' => $log->display_end_time,
                        'effective_duration' => $log->effective_duration,
                        'is_manually_edited' => $log->is_manually_edited,
                    ];
                });

                $monthlyReport[] = [
                    'type' => 'workday',
                    'date' => $currentDate,
                    'first_start_time' => $firstStartTime,
                    'last_end_time' => $lastEndTime,
                    'attendance_seconds' => $attendanceSeconds,
                    'actual_work_seconds' => $actualWorkSeconds,
                    'total_work_seconds' => $totalEffectiveSeconds,
                    'total_break_seconds' => $breakSeconds > 0 ? $breakSeconds : 0,
                    'daily_salary' => $dailySalary,
                    'logs' => $logsForView,
                ];
            } else {
                $monthlyReport[] = [
                    'type' => 'day_off',
                    'date' => $currentDate,
                ];
            }
        }

        return view('admin.work-records.show', compact(
            'user',
            'targetMonth',
            'monthlyReport',
            'monthTotalActualWorkSeconds',
            'monthTotalEffectiveSeconds',
            'monthTotalSalary'
        ));
    }


    public function byProject(Request $request)
    {
        $this->authorize('viewProjectSummary', WorkLog::class);

        $workLogs = WorkLog::with(['task.project.tasks', 'task.character', 'user.hourlyRates', 'task.assignees'])
            ->where('status', 'stopped')
            ->get();

        $logsByProject = $workLogs->groupBy('task.project_id');

        $summary = [];
        // ▼▼▼【変更】総合計用の変数を追加 ▼▼▼
        $grandTotalPlannedSeconds = 0;
        $grandTotalSeconds = 0;
        $grandTotalSalary = 0;
        $grandTotalActualSeconds = 0;
        $grandTotalActualSalary = 0;

        foreach ($logsByProject as $projectId => $projectLogs) {
            if ($projectId === null || !$projectLogs->first()->task->project) {
                continue;
            }

            $project = $projectLogs->first()->task->project;

            $charactersSummary = [];
            foreach ($projectLogs as $log) {
                if (!$log->task || !$log->user) continue;

                $characterId = $log->task->character_id ?? '0';
                $taskId = $log->task_id;

                if (!isset($charactersSummary[$characterId])) {
                    $charactersSummary[$characterId] = [
                        'name' => $log->task->character->name ?? 'キャラクターなし',
                        'tasks' => [],
                    ];
                }

                if (!isset($charactersSummary[$characterId]['tasks'][$taskId])) {
                    $task = $log->task;
                    $charactersSummary[$characterId]['tasks'][$taskId] = [
                        'id' => $taskId,
                        'name' => $task->name,
                        'assignees' => $task->assignees->pluck('name')->all(),
                        'logs' => [],
                        'total_seconds' => 0,
                        'total_salary' => 0,
                        'planned_duration_minutes' => $task->duration,
                    ];
                }

                $duration = $log->effective_duration;
                $rate = $log->user->getHourlyRateForDate($log->start_time);
                $logSalary = ($duration / 3600) * ($rate ?? 0);

                $charactersSummary[$characterId]['tasks'][$taskId]['total_seconds'] += $duration;
                $charactersSummary[$characterId]['tasks'][$taskId]['total_salary'] += $logSalary;
                $charactersSummary[$characterId]['tasks'][$taskId]['logs'][] = [
                    'id' => $log->id,
                    'worker_name' => $log->user->name,
                    'start_time' => optional($log->display_start_time)->format('Y/m/d H:i'),
                    'end_time' => optional($log->display_end_time)->format('Y/m/d H:i'),
                    'duration_formatted' => format_seconds_to_hms($log->effective_duration),
                    'is_manually_edited' => $log->is_manually_edited,
                ];
            }

            // ▼▼▼【変更】案件ごとの計画時間を計算 ▼▼▼
            $projectPlannedSeconds = ($project->tasks()->sum('duration') ?? 0) * 60;

            $projectTotalSeconds = $projectLogs->sum('effective_duration');
            $projectTotalSalary = collect($charactersSummary)->flatMap(fn($char) => $char['tasks'])->sum('total_salary');

            $projectActualWorkSeconds = 0;
            $projectActualSalary = 0;
            $logsByUser = $projectLogs->groupBy('user_id');

            foreach ($logsByUser as $userId => $userLogs) {
                if ($userLogs->isEmpty()) continue;
                $user = $userLogs->first()->user;

                $sortedLogs = $userLogs->sortBy('start_time')->values();
                $userActualSeconds = 0;

                if ($sortedLogs->isNotEmpty()) {
                    $mergedStart = $sortedLogs->first()->start_time;
                    $mergedEnd = $sortedLogs->first()->end_time;
                    foreach ($sortedLogs->slice(1) as $log) {
                        if ($log->start_time < $mergedEnd) {
                            if ($log->end_time > $mergedEnd) {
                                $mergedEnd = $log->end_time;
                            }
                        } else {
                            $userActualSeconds += $mergedEnd->getTimestamp() - $mergedStart->getTimestamp();
                            $mergedStart = $log->start_time;
                            $mergedEnd = $log->end_time;
                        }
                    }
                    $userActualSeconds += $mergedEnd->getTimestamp() - $mergedStart->getTimestamp();
                }
                $projectActualWorkSeconds += $userActualSeconds;

                $latestRate = $user->hourlyRates->first()->rate ?? 0;
                $projectActualSalary += ($userActualSeconds / 3600) * $latestRate;
            }

            $statusKey = $project->status ?? 'not_started';
            $summary[$project->id] = [
                'id' => $project->id,
                'name' => $project->title,
                'color' => $project->color,
                'status_key' => $statusKey,
                'status_text' => \App\Models\Project::PROJECT_STATUS_OPTIONS[$statusKey] ?? ucfirst($statusKey),
                'project_planned_total_seconds' => $projectPlannedSeconds, // 計画時間を追加
                'project_total_seconds' => $projectTotalSeconds,
                'project_total_salary' => $projectTotalSalary,
                'project_actual_work_seconds' => $projectActualWorkSeconds,
                'project_actual_salary' => $projectActualSalary,
                'characters' => $charactersSummary,
            ];

            // ▼▼▼【変更】総合計に加算 ▼▼▼
            $grandTotalPlannedSeconds += $projectPlannedSeconds;
            $grandTotalSeconds += $projectTotalSeconds;
            $grandTotalSalary += $projectTotalSalary;
            $grandTotalActualSeconds += $projectActualWorkSeconds;
            $grandTotalActualSalary += $projectActualSalary;
        }

        return view('admin.work-records.by_project', compact('summary', 'grandTotalPlannedSeconds', 'grandTotalSeconds', 'grandTotalSalary', 'grandTotalActualSeconds', 'grandTotalActualSalary'));
    }

    /**
     * 日別の作業ログ一覧を表示する
     */
    public function dailyLog(Request $request)
    {
        $this->authorize('viewProjectSummary', WorkLog::class);

        $workLogs = WorkLog::with(['task.project', 'task.character', 'user', 'task.assignees'])
            ->where('status', 'stopped')
            ->orderBy('start_time', 'desc')
            ->get();

        $logsByDate = $workLogs->groupBy(fn($log) => $log->start_time->format('Y-m-d'));

        $dailySummary = [];
        foreach ($logsByDate as $date => $logsOnDate) {
            $projectsOnDate = [];
            $logsByProject = $logsOnDate->groupBy('task.project_id');

            foreach ($logsByProject as $projectId => $projectLogs) {
                $firstLog = $projectLogs->first();
                $projectModel = $firstLog->task->project;

                $tasksOnProject = [];
                $logsByTask = $projectLogs->groupBy('task_id');

                foreach ($logsByTask as $taskId => $taskLogs) {
                    $taskModel = $taskLogs->first()->task;

                    $logsForView = $taskLogs->map(function ($log) {
                        $log->start_time_formatted = optional($log->display_start_time)->format('n/j H:i:s');
                        $log->end_time_formatted = optional($log->display_end_time)->format('n/j H:i:s');
                        // effective_durationはアクセサで取れるのでそのままでOK
                        return $log;
                    });

                    $tasksOnProject[$taskId] = [
                        'id' => $taskId,
                        'name' => $taskModel->name,
                        'project_name' => optional($projectModel)->title ?? '案件なし',
                        'character_name' => optional($taskModel->character)->name,
                        'assignees' => $taskModel->assignees->pluck('name')->all(), //  <-- ▼▼▼ この行を追加 ▼▼▼
                        'planned_duration_minutes' => $taskModel->duration,
                        'total_seconds_on_day' => $taskLogs->sum('effective_duration'),
                        'logs' => $logsForView,
                    ];
                }

                $projectsOnDate[$projectId] = [
                    'id' => $projectId,
                    'name' => optional($projectModel)->title ?? '案件なし',
                    'color' => optional($projectModel)->color ?? '#cccccc',
                    'tasks' => $tasksOnProject,
                ];
            }
            $dailySummary[$date] = $projectsOnDate;
        }

        return view('admin.work-records.daily_log', compact('dailySummary'));
    }

    /**
     * 計画時間を超過している工程の一覧を表示する
     */
    public function overPlanned(Request $request)
    {
        $this->authorize('viewOverPlanned', WorkLog::class);

        // 計画時間が設定されている工程で、作業実績時間が計画時間を超過しているものを取得
        $overTasks = collect();

        // クエリビルダーを構築
        $query = \App\Models\Task::whereNotNull('duration')
            ->where('duration', '>', 0)
            ->with(['project', 'character', 'workLogs.user.hourlyRates', 'assignees']);

        // 案件ステータスでフィルター（デフォルトで完了・キャンセルを除外）
        $projectStatuses = $request->filled('project_status')
            ? (is_array($request->project_status) ? $request->project_status : [$request->project_status])
            : ['not_started', 'in_progress', 'on_hold']; // デフォルトで完了・キャンセル以外

        $query->whereHas('project', function ($q) use ($projectStatuses) {
            $q->whereIn('status', $projectStatuses);
        });

        // 案件でフィルター
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // 期間でフィルター（作業ログの日付で判定）
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->whereHas('workLogs', function ($q) use ($request) {
                if ($request->filled('start_date')) {
                    $q->whereDate('start_time', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('start_time', '<=', $request->end_date);
                }
            });
        }

        // 全てのタスクで計画時間が設定されているものを取得
        $tasksWithDuration = $query->get();

        foreach ($tasksWithDuration as $task) {
            $plannedSeconds = $task->duration * 60; // 計画時間（分）を秒に変換

            // 期間フィルターが適用されている場合は、該当期間の作業ログのみを対象とする
            $relevantWorkLogs = $task->workLogs->where('status', 'stopped');
            if ($request->filled('start_date') || $request->filled('end_date')) {
                $relevantWorkLogs = $relevantWorkLogs->filter(function ($log) use ($request) {
                    $logDate = $log->start_time->format('Y-m-d');
                    $startDate = $request->filled('start_date') ? $request->start_date : null;
                    $endDate = $request->filled('end_date') ? $request->end_date : null;

                    if ($startDate && $logDate < $startDate) return false;
                    if ($endDate && $logDate > $endDate) return false;
                    return true;
                });
            }

            $actualSeconds = $relevantWorkLogs->sum('effective_duration');

            if ($actualSeconds > $plannedSeconds) {
                $overageSeconds = $actualSeconds - $plannedSeconds;
                $overagePercentage = $plannedSeconds > 0 ? ($overageSeconds / $plannedSeconds) * 100 : 0;

                // 最新の作業ログから時給を取得して概算給与を計算
                $totalSalary = 0;
                $workLogsByUser = $relevantWorkLogs->groupBy('user_id');
                $actualWorkers = collect();

                foreach ($workLogsByUser as $userId => $userLogs) {
                    $user = $userLogs->first()->user;
                    $userTotalSeconds = $userLogs->sum('effective_duration');
                    $latestRate = $user->hourlyRates->first()->rate ?? 0;
                    $totalSalary += ($userTotalSeconds / 3600) * $latestRate;

                    // 実際に作業した人の情報を収集
                    $actualWorkers->push([
                        'user' => $user,
                        'total_seconds' => $userTotalSeconds,
                        'salary' => ($userTotalSeconds / 3600) * $latestRate,
                    ]);
                }

                // 担当者でフィルター（実際に作業した人で判定）
                if ($request->filled('user_id')) {
                    $hasTargetUser = $actualWorkers->contains(function ($worker) use ($request) {
                        return $worker['user']->id == $request->user_id;
                    });
                    if (!$hasTargetUser) {
                        continue;
                    }
                }

                $overTasks->push([
                    'task' => $task,
                    'planned_seconds' => $plannedSeconds,
                    'actual_seconds' => $actualSeconds,
                    'overage_seconds' => $overageSeconds,
                    'overage_percentage' => $overagePercentage,
                    'total_salary' => $totalSalary,
                    'work_logs_count' => $relevantWorkLogs->count(),
                    'actual_workers' => $actualWorkers, // 実際に作業した人の情報を追加
                    'relevant_work_logs' => $relevantWorkLogs, // フィルター適用済みの作業ログ
                ]);
            }
        }

        // 超過率でソート（降順）
        $overTasks = $overTasks->sortByDesc('overage_percentage');

        // 統計情報を計算
        $totalOverageSeconds = $overTasks->sum('overage_seconds');
        $totalOverageSalary = $overTasks->sum('total_salary');
        $averageOveragePercentage = $overTasks->isNotEmpty() ? $overTasks->avg('overage_percentage') : 0;

        // フィルター用データを取得
        $users = \App\Models\User::orderBy('name')->get();
        $projects = \App\Models\Project::orderBy('title')->get();
        $projectStatusOptions = \App\Models\Project::PROJECT_STATUS_OPTIONS;

        return view('admin.work-records.over_planned', compact(
            'overTasks',
            'totalOverageSeconds',
            'totalOverageSalary',
            'averageOveragePercentage',
            'users',
            'projects',
            'projectStatusOptions'
        ));
    }
}

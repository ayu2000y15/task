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

class WorkRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', WorkLog::class);

        // --- 期間別サマリー (変更なし) ---
        $now = Carbon::now();
        $summaryDateStrings = [
            'today' => $now->format('n/j'),
            'week' => $now->copy()->startOfWeek()->format('n/j') . ' - ' . $now->copy()->endOfWeek()->format('n/j'),
            'month' => $now->format('Y年n月'),
        ];
        $todayLogs = $this->getLogsForPeriod($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $weekLogs = $this->getLogsForPeriod($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
        $monthLogs = $this->getLogsForPeriod($now->copy()->startOfMonth(), $now->copy()->endOfMonth());
        $todaySummary = $this->calculateSummary($todayLogs);
        $weekSummary = $this->calculateSummary($weekLogs);
        $monthSummary = $this->calculateSummary($monthLogs);


        // --- ▼▼▼【ここからクエリとソート処理を修正】▼▼▼ ---

        // ソート可能な列を定義
        $sortableColumns = [
            'user' => 'users.name',
            'project' => 'projects.title',
            'character' => 'characters.name',
            'task' => 'tasks.name',
            'start_time' => 'work_logs.start_time',
            'end_time' => 'work_logs.end_time',
            'duration' => DB::raw('TIMESTAMPDIFF(SECOND, work_logs.start_time, work_logs.end_time)'),
            'salary' => 'calculated_salary', // 計算済みの給与カラム名に変更
        ];

        // リクエストからソート情報を取得（デフォルトは開始日時の降順）
        $sort = $request->query('sort', 'start_time');
        $direction = $request->query('direction', 'desc');

        // 不正な列名でのソートを防ぐ
        if (!array_key_exists($sort, $sortableColumns)) {
            $sort = 'start_time';
            $direction = 'desc';
        }

        // 各作業ログの開始時刻に対応する時給を取得するためのサブクエリ
        $rateSubQuery = HourlyRate::select('rate')
            ->whereColumn('user_id', 'users.id')
            ->where('effective_date', '<=', DB::raw('date(work_logs.start_time)'))
            ->orderBy('effective_date', 'desc')
            ->limit(1);

        $query = WorkLog::query()
            ->join('users', 'work_logs.user_id', '=', 'users.id')
            ->join('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->leftJoin('characters', 'tasks.character_id', '=', 'characters.id')
            // selectにサブクエリを追加して時給と給与を動的に計算
            ->select('work_logs.*')
            ->selectSub($rateSubQuery, 'current_rate')
            ->selectRaw('(TIMESTAMPDIFF(SECOND, work_logs.start_time, work_logs.end_time) / 3600 * (' . $rateSubQuery->toSql() . ')) as calculated_salary')
            ->mergeBindings($rateSubQuery->getQuery()) // toSql()で失われるバインディングをマージ
            ->where('work_logs.status', 'stopped');

        // フィルター処理 (変更なし)
        if ($request->filled('user_id')) {
            $query->where('work_logs.user_id', $request->user_id);
        }
        if ($request->filled('project_id')) {
            $query->where('tasks.project_id', $request->project_id);
        }
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;
        if ($startDate && $endDate) {
            $query->whereBetween('work_logs.start_time', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('work_logs.start_time', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('work_logs.start_time', '<=', $endDate);
        }

        // フィルタ後の合計時間を計算
        $totalSecondsQuery = clone $query;
        $totalSeconds = $totalSecondsQuery->sum(DB::raw('TIMESTAMPDIFF(SECOND, work_logs.start_time, work_logs.end_time)'));

        // ソートを適用
        $query->orderBy($sortableColumns[$sort], $direction);
        if ($sort !== 'start_time') {
            $query->orderBy('work_logs.start_time', 'desc'); // 第2ソートキー
        }

        // withに hourlyRates を追加して、ユーザーの最新時給をEager Loadする
        $workLogs = $query->with(['user.hourlyRates', 'task.project', 'task.character'])->paginate(50)->withQueryString();

        // フィルタリング・時給登録用の選択肢 (ユーザーは最新の時給情報も取得)
        $users = User::with(['hourlyRates' => function ($query) {
            $query->orderBy('effective_date', 'desc')->limit(2);
        }])->orderBy('name')->get();
        $projects = Project::orderBy('title')->get();

        return view('admin.work-records.index', compact(
            'workLogs',
            'users',
            'projects',
            'totalSeconds',
            'todaySummary',
            'weekSummary',
            'monthSummary',
            'summaryDateStrings',
            'sort',
            'direction'
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

    private function getLogsForPeriod(Carbon $start, Carbon $end)
    {
        return WorkLog::with('user.hourlyRates') // hourlyRatesもEager Load
            ->where('status', 'stopped')
            ->whereBetween('start_time', [$start, $end])
            ->get();
    }

    private function calculateSummary($logs)
    {
        $byUser = [];
        $grandTotalSeconds = 0;
        $grandTotalSalary = 0;

        foreach ($logs as $log) {
            if (!$log->user) continue;

            $userId = $log->user->id;

            if (!isset($byUser[$userId])) {
                $byUser[$userId] = [
                    'user' => $log->user,
                    'total_seconds' => 0,
                    'total_salary' => 0,
                ];
            }

            $duration = $log->effective_duration;
            $byUser[$userId]['total_seconds'] += $duration;
            $grandTotalSeconds += $duration;

            // ▼▼▼【変更】ユーザーの時給を日付ベースで取得 ▼▼▼
            $rate = $log->user->getHourlyRateForDate($log->start_time);
            if ($rate > 0) {
                $salary = ($duration / 3600) * $rate;
                $byUser[$userId]['total_salary'] += $salary;
                $grandTotalSalary += $salary;
            }
        }

        uasort($byUser, function ($a, $b) {
            return strcmp($a['user']->name, $b['user']->name);
        });

        return [
            'by_user' => $byUser,
            'total_seconds' => $grandTotalSeconds,
            'total_salary' => $grandTotalSalary,
        ];
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

                // ▼▼▼【変更】日給の計算を日付ベースの時給で行う ▼▼▼
                $hourlyRate = $user->getHourlyRateForDate($currentDate);
                $dailySalary = $hourlyRate > 0 ? ($actualWorkSeconds / 3600) * $hourlyRate : 0;

                $monthTotalActualWorkSeconds += $actualWorkSeconds;
                $monthTotalEffectiveSeconds += $totalEffectiveSeconds;
                $monthTotalSalary += $dailySalary;

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
                    'logs' => $dayLogs,
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


    /**
     * ▼▼▼【ここが不足していたメソッド】▼▼▼
     * 案件別の作業時間サマリーを表示する
     */

    public function byProject(Request $request)
    {
        $this->authorize('viewAny', WorkLog::class);

        // userリレーションに加えて、時給履歴(hourlyRates)もEager Loadする
        $workLogs = WorkLog::with(['task.project', 'task.character', 'user.hourlyRates'])
            ->where('status', 'stopped')
            ->get();

        // まず、全ログを案件IDでグループ化
        $logsByProject = $workLogs->groupBy('task.project_id');

        $summary = [];
        $grandTotalSeconds = 0;
        $grandTotalSalary = 0;
        $grandTotalActualSeconds = 0; // 実働時間の総合計
        $grandTotalActualSalary = 0;  // 実働給与の総合計

        foreach ($logsByProject as $projectId => $projectLogs) {
            if ($projectId === null || !$projectLogs->first()->task->project) {
                continue;
            }

            $project = $projectLogs->first()->task->project;
            $projectId = $project->id;

            // --- 1. これまでの集計（単純合計と詳細データ作成） ---
            $projectTotalSeconds = 0;
            $projectTotalSalary = 0;
            $charactersSummary = [];

            foreach ($projectLogs as $log) {
                if (!$log->task || !$log->user) continue;
                $characterId = $log->task->character_id ?? '0';
                $taskId = $log->task_id;

                if (!isset($charactersSummary[$characterId])) {
                    $charactersSummary[$characterId] = [
                        'id' => $characterId,
                        'name' => $log->task->character->name ?? 'キャラクターなし',
                        'tasks' => [],
                        'character_total_seconds' => 0,
                        'character_total_salary' => 0,
                    ];
                }
                if (!isset($charactersSummary[$characterId]['tasks'][$taskId])) {
                    $charactersSummary[$characterId]['tasks'][$taskId] = [
                        'id' => $taskId,
                        'name' => $log->task->name,
                        'workers' => [],
                        'logs' => [],
                        'total_seconds' => 0,
                        'total_salary' => 0,
                    ];
                }
                $charactersSummary[$characterId]['tasks'][$taskId]['workers'][$log->user->id] = $log->user->name;
                $charactersSummary[$characterId]['tasks'][$taskId]['logs'][] = [
                    'worker_name' => $log->user->name,
                    'start_time' => $log->start_time->format('Y/m/d H:i'),
                    'end_time' => $log->end_time->format('H:i'),
                    'duration_formatted' => gmdate('H:i:s', $log->effective_duration),
                ];
                $duration = $log->effective_duration;

                // ▼▼▼【変更箇所】作業日時に基づいて時給を取得し、給与を計算する ▼▼▼
                $rate = $log->user->getHourlyRateForDate($log->start_time);
                $logSalary = ($duration / 3600) * ($rate ?? 0);

                $charactersSummary[$characterId]['tasks'][$taskId]['total_seconds'] += $duration;
                $charactersSummary[$characterId]['tasks'][$taskId]['total_salary'] += $logSalary;
                $charactersSummary[$characterId]['character_total_seconds'] += $duration;
                $charactersSummary[$characterId]['character_total_salary'] += $logSalary;
                $projectTotalSeconds += $duration;
                $projectTotalSalary += $logSalary;
            }

            // --- 2. 実働時間と実働給与の計算 ---
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

                // ▼▼▼【変更箇所】実働給与の計算 ▼▼▼
                // 注意: この計算は複雑（日をまたぐ勤務や、同日でも時給が変わるケース）なため、
                // ここではユーザーの最新の時給で簡易的に計算しています。
                // 正確性を期すには、日ごとの実働時間を算出し、その日の時給を適用するロジックが必要です。
                $latestRate = $user->hourlyRates->first()->rate ?? 0;
                $projectActualSalary += ($userActualSeconds / 3600) * $latestRate;
            }

            // --- 3. 最終的なサマリー配列の構築 ---
            $statusKey = $project->status ?? 'not_started';
            $summary[$projectId] = [
                'id' => $projectId,
                'name' => $project->title,
                'color' => $project->color,
                'status_key' => $statusKey,
                'status_text' => \App\Models\Project::PROJECT_STATUS_OPTIONS[$statusKey] ?? ucfirst($statusKey),
                'project_total_seconds' => $projectTotalSeconds,
                'project_total_salary' => $projectTotalSalary,
                'project_actual_work_seconds' => $projectActualWorkSeconds, // 実働時間を追加
                'project_actual_salary' => $projectActualSalary,         // 実働給与を追加
                'characters' => $charactersSummary,
            ];

            // 総合計に加算
            $grandTotalSeconds += $projectTotalSeconds;
            $grandTotalSalary += $projectTotalSalary;
            $grandTotalActualSeconds += $projectActualWorkSeconds;
            $grandTotalActualSalary += $projectActualSalary;
        }

        return view('admin.work-records.by_project', compact('summary', 'grandTotalSeconds', 'grandTotalSalary', 'grandTotalActualSeconds', 'grandTotalActualSalary'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkLog;
use App\Models\User;
use App\Models\Project;
use Carbon\Carbon;

class WorkRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // app/Http/Controllers/Admin/WorkRecordController.php

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
            'duration' => \DB::raw('TIMESTAMPDIFF(SECOND, work_logs.start_time, work_logs.end_time)'),
            'salary' => \DB::raw('(TIMESTAMPDIFF(SECOND, work_logs.start_time, work_logs.end_time) / 3600 * users.hourly_rate)'),
        ];

        // リクエストからソート情報を取得（デフォルトは開始日時の降順）
        $sort = $request->query('sort', 'start_time');
        $direction = $request->query('direction', 'desc');

        // 不正な列名でのソートを防ぐ
        if (!array_key_exists($sort, $sortableColumns)) {
            $sort = 'start_time';
            $direction = 'desc';
        }

        $query = WorkLog::query()
            ->join('users', 'work_logs.user_id', '=', 'users.id')
            ->join('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->leftJoin('characters', 'tasks.character_id', '=', 'characters.id')
            ->select('work_logs.*') // work_logsの全カラムを選択
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
        $totalSeconds = $totalSecondsQuery->sum(\DB::raw('TIMESTAMPDIFF(SECOND, work_logs.start_time, work_logs.end_time)'));

        // ソートを適用
        $query->orderBy($sortableColumns[$sort], $direction);
        if ($sort !== 'start_time') {
            $query->orderBy('work_logs.start_time', 'desc'); // 第2ソートキー
        }

        $workLogs = $query->with(['user', 'task.project', 'task.character'])->paginate(50)->withQueryString();

        // フィルタリング用の選択肢 (変更なし)
        $users = User::orderBy('name')->get();
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
            'direction' // ソート情報をビューに渡す
        ));
    }

    public function updateUserRate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'hourly_rate' => 'required|numeric|min:0',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->hourly_rate = $validated['hourly_rate'];
        $user->save();

        return redirect()->route('admin.work-records.index')->with('success', $user->name . 'さんの時給を更新しました。');
    }

    private function getLogsForPeriod(Carbon $start, Carbon $end)
    {
        return WorkLog::with('user')
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

            if ($log->user->hourly_rate > 0) {
                $salary = ($duration / 3600) * $log->user->hourly_rate;
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
                $dailySalary = $user->hourly_rate > 0 ? ($actualWorkSeconds / 3600) * $user->hourly_rate : 0;

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

        $workLogs = WorkLog::with(['task.project', 'task.character', 'user'])
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
                $logSalary = ($duration / 3600) * ($log->user->hourly_rate ?? 0);
                $charactersSummary[$characterId]['tasks'][$taskId]['total_seconds'] += $duration;
                $charactersSummary[$characterId]['tasks'][$taskId]['total_salary'] += $logSalary;
                $charactersSummary[$characterId]['character_total_seconds'] += $duration;
                $charactersSummary[$characterId]['character_total_salary'] += $logSalary;
                $projectTotalSeconds += $duration;
                $projectTotalSalary += $logSalary;
            }

            // --- 2. ▼▼▼【新規】実働時間と実働給与の計算 ▼▼▼ ---
            $projectActualWorkSeconds = 0;
            $projectActualSalary = 0;
            $logsByUser = $projectLogs->groupBy('user_id');

            foreach ($logsByUser as $userId => $userLogs) {
                if ($userLogs->isEmpty()) continue;
                $user = $userLogs->first()->user;
                $hourlyRate = $user->hourly_rate ?? 0;

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
                $projectActualSalary += ($userActualSeconds / 3600) * $hourlyRate;
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

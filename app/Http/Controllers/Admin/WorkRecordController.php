<?php
// app/Http/Controllers/Admin/WorkRecordController.php

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
    public function index(Request $request)
    {
        // ポリシーでアクセス権をチェック
        $this->authorize('viewAny', WorkLog::class);

        // ▼▼▼【ここから集計処理を追加】▼▼▼

        // --- 期間別サマリーの計算 ---
        $now = Carbon::now();

        $summaryDateStrings = [
            'today' => $now->format('n/j'),
            'week' => $now->copy()->startOfWeek()->format('n/j') . ' - ' . $now->copy()->endOfWeek()->format('n/j'),
            'month' => $now->format('Y年n月'),
        ];

        // 1. 各期間のログを取得
        $todayLogs = $this->getLogsForPeriod($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $weekLogs = $this->getLogsForPeriod($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
        $monthLogs = $this->getLogsForPeriod($now->copy()->startOfMonth(), $now->copy()->endOfMonth());

        // 2. 各期間のサマリーを計算
        $todaySummary = $this->calculateSummary($todayLogs);
        $weekSummary = $this->calculateSummary($weekLogs);
        $monthSummary = $this->calculateSummary($monthLogs);

        // ▲▲▲【集計処理ここまで】▲▲▲

        $query = WorkLog::with(['user', 'task.project', 'task.character'])
            ->where('status', 'stopped')
            ->orderBy('start_time', 'desc');

        $query = WorkLog::with(['user', 'task.project'])
            ->where('status', 'stopped') // 完了したログのみ
            ->orderBy('start_time', 'desc');

        // ユーザーによる絞り込み
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 案件による絞り込み
        if ($request->filled('project_id')) {
            $query->whereHas('task', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        // 日付範囲による絞り込み
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;

        if ($startDate && $endDate) {
            $query->whereBetween('start_time', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('start_time', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('start_time', '<=', $endDate);
        }

        $workLogs = $query->paginate(50)->withQueryString();

        // フィルタリング後の合計時間を計算
        // 注意：ページネーションの影響を受けないように、同じ条件で別途sumを取得
        $totalSeconds = $query->sum(
            \DB::raw('TIMESTAMPDIFF(SECOND, start_time, end_time)')
        );

        // フィルタリング用の選択肢を取得
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
            'summaryDateStrings'
        ));
    }

    /**
     * ▼▼▼【ここから追加】ユーザーの時給を更新するメソッド ▼▼▼
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * 指定された期間の作業ログを取得するヘルパーメソッド
     */
    private function getLogsForPeriod(Carbon $start, Carbon $end)
    {
        return WorkLog::with('user')
            ->where('status', 'stopped')
            ->whereBetween('start_time', [$start, $end])
            ->get();
    }

    /**
     * ログのコレクションからサマリーを計算するヘルパーメソッド
     */
    private function calculateSummary($logs)
    {
        $byUser = [];
        $grandTotalSeconds = 0;
        $grandTotalSalary = 0;

        foreach ($logs as $log) {
            if (!$log->user) continue;

            $userId = $log->user->id;

            // ユーザーごとの集計配列を初期化
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

        // ユーザー名でソート
        uasort($byUser, function ($a, $b) {
            return strcmp($a['user']->name, $b['user']->name);
        });

        return [
            'by_user' => $byUser,
            'total_seconds' => $grandTotalSeconds,
            'total_salary' => $grandTotalSalary,
        ];
    }

    /**
     * ▼▼▼【ここから追加】担当者別の詳細な作業実績を表示する ▼▼▼
     */
    /**
     * ▼▼▼【ここを全面的に修正】▼▼▼
     * 担当者別の詳細な作業実績を表示する
     */
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

        // 月の全日リストを生成するための配列
        $monthlyReport = [];
        $daysInMonth = $targetMonth->daysInMonth;

        // 月の合計値を初期化
        $monthTotalWorkSeconds = 0;
        $monthTotalSalary = 0;

        // 月の1日から最終日までループ
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $targetMonth->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');

            // その日に作業ログがあるかチェック
            if ($logsByDay->has($dateString)) {
                $dayLogs = $logsByDay[$dateString];

                $firstStartTime = $dayLogs->min('start_time');
                $lastEndTime = $dayLogs->max('end_time');
                $totalWorkSeconds = $dayLogs->sum('effective_duration');
                $attendanceSeconds = $lastEndTime->diffInSeconds($firstStartTime);
                $breakSeconds = $attendanceSeconds - $totalWorkSeconds;
                $dailySalary = $user->hourly_rate > 0 ? ($totalWorkSeconds / 3600) * $user->hourly_rate : 0;

                // 月の合計に加算
                $monthTotalWorkSeconds += $totalWorkSeconds;
                $monthTotalSalary += $dailySalary;

                $monthlyReport[] = [
                    'type' => 'workday', // 'workday' タイプ
                    'date' => $currentDate,
                    'first_start_time' => $firstStartTime,
                    'last_end_time' => $lastEndTime,
                    'attendance_seconds' => $attendanceSeconds,
                    'total_work_seconds' => $totalWorkSeconds,
                    'total_break_seconds' => $breakSeconds > 0 ? $breakSeconds : 0,
                    'daily_salary' => $dailySalary,
                    'logs' => $dayLogs,
                ];
            } else {
                // 作業ログがない日は 'day_off' タイプ
                $monthlyReport[] = [
                    'type' => 'day_off',
                    'date' => $currentDate,
                ];
            }
        }

        // 日付の降順にする場合は配列を反転させる
        //$monthlyReport = array_reverse($monthlyReport);

        return view('admin.work-records.show', compact(
            'user',
            'targetMonth',
            'monthlyReport', // 新しいデータ配列を渡す
            'monthTotalWorkSeconds', // 月の合計を渡す
            'monthTotalSalary' // 月の合計を渡す
        ));
    }
}

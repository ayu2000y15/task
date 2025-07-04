<?php
// app/Http/Controllers/WorkRecordController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkLog;
use App\Models\AttendanceLog;
use Carbon\Carbon;

class WorkRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewOwn', WorkLog::class);
        $user = Auth::user();
        $viewMode = $request->input('view', 'timeline');

        $period = $request->input('period', 'day');
        try {
            $targetDate = Carbon::parse($request->input('date', now()));
        } catch (\Exception $e) {
            $targetDate = now();
        }

        switch ($period) {
            case 'week':
                $startDate = $targetDate->copy()->startOfWeek();
                $endDate = $targetDate->copy()->endOfWeek();
                break;
            case 'month':
                $startDate = $targetDate->copy()->startOfMonth();
                $endDate = $targetDate->copy()->endOfMonth();
                break;
            case 'day':
            default:
                $startDate = $targetDate->copy()->startOfDay();
                $endDate = $targetDate->copy()->endOfDay();
                break;
        }

        $summary = $this->calculateAttendanceSummary($user, $startDate, $endDate);

        $templateData = [
            'viewMode' => $viewMode,
            'user' => $user,
            'period' => $period,
            'summary' => $summary,
            'targetDate' => $targetDate,
        ];


        if ($viewMode === 'list') {
            $monthString = $request->input('month', now()->format('Y-m'));
            try {
                $currentMonth = Carbon::createFromFormat('Y-m', $monthString)->startOfMonth();
            } catch (\Exception $e) {
                $currentMonth = now()->startOfMonth();
            }

            $listStartDate = $currentMonth->copy()->startOfMonth();
            $listEndDate = $currentMonth->copy()->endOfMonth();

            $workLogs = WorkLog::where('user_id', $user->id)
                ->whereBetween('start_time', [$listStartDate, $listEndDate])
                ->get();
            $attendanceLogs = AttendanceLog::where('user_id', $user->id)
                ->whereBetween('timestamp', [$listStartDate, $listEndDate])
                ->get();

            $allLogs = $this->mapAndMergeLogs($workLogs, $attendanceLogs);
            $logsByDate = $allLogs->groupBy(fn($item) => $item->timestamp->format('Y-m-d'));

            // ▼▼▼【ここから追加】欠落していた dailySummaries の生成ロジックを復元 ▼▼▼
            $dailySummaries = collect();
            for ($date = $listStartDate->copy(); $date->lte($listEndDate); $date->addDay()) {
                $dateString = $date->format('Y-m-d');
                $dayLogs = $logsByDate->get($dateString);

                if ($dayLogs && $dayLogs->isNotEmpty()) {
                    $sortedDayLogs = $dayLogs->sortBy('timestamp');
                    $firstClockIn = $sortedDayLogs->first(fn($item) => data_get($item, 'model.type') === 'clock_in');
                    $lastClockOut = $sortedDayLogs->last(fn($item) => data_get($item, 'model.type') === 'clock_out');

                    $dailySummaries->push((object)[
                        'date' => $date->copy(),
                        'clockInTime' => optional($firstClockIn)->timestamp,
                        'clockOutTime' => optional($lastClockOut)->timestamp,
                        'totalWorkSeconds' => $sortedDayLogs->where('type', 'work_log')->sum('model.effective_duration'),
                        'totalBreakSeconds' => $this->calculateTimeDifference($sortedDayLogs, 'break_start', 'break_end'),
                        'totalAwaySeconds' => $this->calculateTimeDifference($sortedDayLogs, 'away_start', 'away_end'),
                        'details' => $sortedDayLogs,
                    ]);
                }
            }
            // ▲▲▲【追加ここまで】▲▲▲

            $templateData['dailySummaries'] = $dailySummaries;
            $templateData['currentMonth'] = $currentMonth;
        } else {
            // タイムライン表示のロジック
            $currentDate = $targetDate;
            $workLogs = WorkLog::where('user_id', $user->id)->whereDate('start_time', $currentDate)->get();
            $attendanceLogs = AttendanceLog::where('user_id', $user->id)->whereDate('timestamp', $currentDate)->get();

            $templateData['timelineItems'] = $this->mapAndMergeLogs($workLogs, $attendanceLogs)->sortBy('timestamp');
            $templateData['totalSeconds'] = $workLogs->sum('effective_duration');
            $templateData['currentDate'] = $currentDate;
        }

        return view('work-records.index', $templateData);
    }

    private function mapAndMergeLogs($workLogs, $attendanceLogs)
    {
        $workLogItems = $workLogs->map(fn($log) => (object)['timestamp' => $log->start_time, 'type' => 'work_log', 'model' => $log]);
        $attendanceLogItems = $attendanceLogs->map(fn($log) => (object)['timestamp' => $log->timestamp, 'type' => 'attendance_log', 'model' => $log]);
        return $workLogItems->toBase()->merge($attendanceLogItems);
    }

    private function calculateTimeDifference($logs, $startType, $endType): int
    {
        $totalSeconds = 0;
        $startTime = null;

        foreach ($logs as $log) {
            if (data_get($log, 'model.type') === $startType) {
                $startTime = $log->timestamp;
            } elseif (data_get($log, 'model.type') === $endType && $startTime) {
                $totalSeconds += $startTime->diffInSeconds($log->timestamp);
                $startTime = null;
            }
        }
        return $totalSeconds;
    }

    /**
     * 指定された期間の勤怠サマリーを計算する
     * @param \App\Models\User $user
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function calculateAttendanceSummary($user, $startDate, $endDate): array
    {
        // 期間内の勤怠ログを取得
        $attendanceLogs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp')
            ->get();

        // 日付ごとにグループ化
        $logsByDate = $attendanceLogs->groupBy(fn($log) => $log->timestamp->format('Y-m-d'));

        $totalDetentionSeconds = 0;
        $totalBreakSeconds = 0;
        $totalAwaySeconds = 0;

        foreach ($logsByDate as $date => $dayLogs) {
            // その日の最初の出勤記録
            $firstClockIn = $dayLogs->firstWhere('type', 'clock_in');
            // その日の最後の退勤記録
            $lastClockOut = $dayLogs->last(fn($log) => $log->type === 'clock_out');

            // 出勤・退勤が揃っている日のみ拘束時間を計算
            if ($firstClockIn && $lastClockOut) {
                $totalDetentionSeconds += $firstClockIn->timestamp->diffInSeconds($lastClockOut->timestamp);
            }

            // 休憩と中抜けの時間を計算
            $totalBreakSeconds += $this->calculateDurationForPairs($dayLogs, 'break_start', 'break_end');
            $totalAwaySeconds += $this->calculateDurationForPairs($dayLogs, 'away_start', 'away_end');
        }

        $totalCombinedBreak = $totalBreakSeconds + $totalAwaySeconds;
        $payableSeconds = max(0, $totalDetentionSeconds - $totalCombinedBreak);

        return [
            'detention_seconds' => $totalDetentionSeconds,
            'break_seconds' => $totalCombinedBreak,
            'payable_seconds' => $payableSeconds,
        ];
    }

    /**
     * AttendanceLogのコレクションからペアの合計時間を計算するヘルパー
     */
    private function calculateDurationForPairs($logs, $startType, $endType): int
    {
        $totalSeconds = 0;
        $startTime = null;

        foreach ($logs as $log) {
            if ($log->type === $startType) {
                $startTime = $log->timestamp;
            } elseif ($log->type === $endType && $startTime) {
                $totalSeconds += $startTime->diffInSeconds($log->timestamp);
                $startTime = null; // ペアを成立させたのでリセット
            }
        }
        return $totalSeconds;
    }
}

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
        $templateData = ['viewMode' => $viewMode, 'user' => $user];

        if ($viewMode === 'list') {
            // --- ▼▼▼【ここから変更】リスト表示のロジックを月単位に修正 ▼▼▼ ---
            $monthString = $request->input('month', now()->format('Y-m'));
            try {
                // YYYY-MM形式の文字列からCarbonインスタンスを生成
                $currentMonth = Carbon::createFromFormat('Y-m', $monthString)->startOfMonth();
            } catch (\Exception $e) {
                // 不正なフォーマットの場合は当月にフォールバック
                $currentMonth = now()->startOfMonth();
            }

            $startDate = $currentMonth->copy()->startOfMonth();
            $endDate = $currentMonth->copy()->endOfMonth();

            $workLogs = WorkLog::where('user_id', $user->id)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->get();
            $attendanceLogs = AttendanceLog::where('user_id', $user->id)
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->get();

            // これ以降の集計ロジックは変更なし
            $allLogs = $this->mapAndMergeLogs($workLogs, $attendanceLogs);
            $logsByDate = $allLogs->groupBy(fn($item) => $item->timestamp->format('Y-m-d'));

            $dailySummaries = collect();
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
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

            $templateData['dailySummaries'] = $dailySummaries;
            $templateData['currentMonth'] = $currentMonth; // ビューでのナビゲーション用に渡す
            // --- ▲▲▲【変更ここまで】▲▲▲ ---

        } else {
            // --- タイムライン表示のロジック (変更なし) ---
            try {
                $currentDate = Carbon::parse($request->input('date', now()));
            } catch (\Exception $e) {
                $currentDate = now();
            }

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

    /**
     * 特定の開始/終了タイプのペア間の合計時間を計算する
     */
    private function calculateTimeDifference($logs, $startType, $endType): int
    {
        $totalSeconds = 0;
        $startTime = null;

        foreach ($logs as $log) {
            if (data_get($log, 'model.type') === $startType) {
                $startTime = $log->timestamp;
            } elseif (data_get($log, 'model.type') === $endType && $startTime) {
                $totalSeconds += $startTime->diffInSeconds($log->timestamp);
                $startTime = null; // 次のペアのためにリセット
            }
        }
        return $totalSeconds;
    }
}

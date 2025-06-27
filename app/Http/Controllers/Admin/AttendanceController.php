<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\WorkShift;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠明細の表示
     */
    public function show(User $user, $month = null)
    {
        $targetMonth = $month ? Carbon::parse($month) : Carbon::now();
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // --- 1. 必要なデータを全て取得 ---
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));
        $workShifts = WorkShift::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));
        $workLogs = WorkLog::where('user_id', $user->id)->where('status', 'stopped')->whereBetween('start_time', [$startDate, $endDate])->with('task.project', 'task.character')->orderBy('start_time')->get();
        $attendanceLogs = AttendanceLog::where('user_id', $user->id)->whereBetween('timestamp', [$startDate, $endDate])->orderBy('timestamp')->get();
        $applicableRates = $user->getApplicableRatesForMonth($targetMonth);

        // 手動編集された勤怠データを取得
        $overriddenAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // --- 2. 月の全日分の表示データを生成 ---
        $monthlyReport = [];
        for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
            $currentDate = $targetMonth->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');

            $manualAttendance = $overriddenAttendances->get($dateString);
            $dayAttendanceLogs = $attendanceLogs->filter(fn($log) => $log->timestamp->isSameDay($currentDate));

            $reportData = [
                'date' => $currentDate,
                'public_holiday' => $holidays->get($dateString),
                'work_shift' => $workShifts->get($dateString),
            ];

            // 表示タイプを決定する
            if ($manualAttendance) {
                // 手動編集データが最優先
                $reportData['type'] = 'edited';
                $reportData['summary'] = $manualAttendance;
                $dayWorkLogs = $workLogs->filter(fn($log) => $log->start_time->isSameDay($currentDate));
                $reportData['logs'] = $dayWorkLogs;
                $reportData['worklog_total_seconds'] = $dayWorkLogs->sum('effective_duration');
            } elseif ($dayAttendanceLogs->isNotEmpty()) {
                // 勤怠ログがあれば「勤務日」
                $reportData['type'] = 'workday';
                $reportData['sessions'] = $this->calculateSessionsFromLogs($dayAttendanceLogs, $workLogs, $user);
            } else {
                // ログがなければ「休日」
                $reportData['type'] = 'day_off';
                $reportData['sessions'] = []; // 空のセッション
            }
            $monthlyReport[$dateString] = $reportData;
        }

        // --- 3. 月の合計を計算 ---
        $monthTotalSalary = 0;
        $monthTotalActualWorkSeconds = 0;
        foreach ($monthlyReport as $report) {
            if ($report['type'] === 'edited') {
                // 手動編集データの日給は保存時に計算済みのものを利用
                $monthTotalActualWorkSeconds += $report['worklog_total_seconds'];
                $monthTotalSalary += $report['summary']->daily_salary;
            } elseif ($report['type'] === 'workday') {
                $monthTotalActualWorkSeconds += collect($report['sessions'])->sum('actual_work_seconds');
                $monthTotalSalary += collect($report['sessions'])->sum('daily_salary');
            }
        }

        return view('admin.attendances.show', compact(
            'user',
            'targetMonth',
            'monthlyReport',
            'monthTotalActualWorkSeconds',
            'monthTotalSalary',
            'applicableRates'
        ));
    }

    private function calculateSessionsFromLogs($dayAttendanceLogs, $allWorkLogs, $user)
    {
        // このメソッドは変更の必要はありませんが、以前のシンプルな状態に戻します
        $sessions = [];
        $currentSession = null;
        foreach ($dayAttendanceLogs as $log) {
            if ($log->type === 'clock_in') {
                if ($currentSession) $sessions[] = $currentSession;
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
        if ($currentSession) $sessions[] = $currentSession;

        return collect($sessions)->map(function ($session) use ($allWorkLogs, $user) {
            $startTime = $session['start_time'];
            $endTime = $session['end_time'];
            $breakSeconds = $this->calculateTimeDifference($session['logs'], 'break_start', 'break_end') + $this->calculateTimeDifference($session['logs'], 'away_start', 'away_end');
            $detentionSeconds = $endTime ? $startTime->diffInSeconds($endTime) : 0;
            $payableWorkSeconds = max(0, $detentionSeconds - $breakSeconds);
            $sessionWorkLogs = $allWorkLogs->where('start_time', '>=', $startTime)->when($endTime, fn($q) => $q->where('start_time', '<=', $endTime));
            $actualWorkSeconds = $sessionWorkLogs->sum('effective_duration');
            $rateForDay = $user->getHourlyRateForDate($startTime);
            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'detention_seconds' => $detentionSeconds,
                'break_seconds' => $breakSeconds,
                'actual_work_seconds' => $actualWorkSeconds,
                'daily_salary' => $rateForDay > 0 ? round(($payableWorkSeconds / 3600) * $rateForDay) : 0,
                'logs' => $sessionWorkLogs,
            ];
        });
    }

    /**
     * 1行分の勤怠データを更新する (日付単位での上書き)
     */
    public function updateSingle(Request $request, User $user, $date)
    {
        // このメソッドは以前のままでOK
        $validated = $request->validate([
            'start_time' => 'nullable|required_with:end_time|date_format:H:i',
            'end_time' => 'nullable|required_with:start_time|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $targetDate = Carbon::parse($date);

        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $startTime = $targetDate->copy()->setTimeFromTimeString($validated['start_time']);
            $endTime = $targetDate->copy()->setTimeFromTimeString($validated['end_time']);
            if ($endTime <= $startTime) $endTime->addDay();
            $breakSeconds = (int)($validated['break_minutes'] ?? 0) * 60;
            $detentionSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();
            $actualWorkSeconds = max(0, $detentionSeconds - $breakSeconds);
            $rate = $user->getHourlyRateForDate($targetDate);
            $dailySalary = $rate > 0 ? round(($actualWorkSeconds / 3600) * $rate) : 0;

            Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'break_seconds' => $breakSeconds,
                    'actual_work_seconds' => $actualWorkSeconds,
                    'note' => $validated['note'],
                    'status' => 'edited',
                    'daily_salary' => $dailySalary, // 日給も保存
                ]
            );
        } else {
            // データが空なら削除
            Attendance::where('user_id', $user->id)->where('date', $date)->delete();
        }

        return response()->json(['success' => true, 'message' => '更新しました。']);
    }

    // ▼▼▼【不要なメソッドを削除】▼▼▼
    // public function updateLogBasedSession(Request $request, User $user) { ... }

    private function calculateTimeDifference($logs, $startType, $endType): int
    {
        // このメソッドは変更なし
        $totalSeconds = 0;
        $startTime = null;
        foreach ($logs as $log) {
            if ($log->type === $startType) {
                $startTime = $log->timestamp;
            } elseif ($log->type === $endType && $startTime) {
                $totalSeconds += $startTime->diffInSeconds($log->timestamp);
                $startTime = null;
            }
        }
        return $totalSeconds;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\Attendance;
use App\Models\AttendanceLog; // ▼▼▼【追加】勤怠ログモデルをインポート
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

        // --- 1. 必要なデータを全て取得 (ShiftControllerと同様) ---
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // ユーザーのスケジュール設定（休日や勤務変更）を一括取得
        $workShifts = WorkShift::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // 手動編集された勤怠データを取得
        $overriddenAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        $workLogs = WorkLog::where('user_id', $user->id)->where('status', 'stopped')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->with('task.project', 'task.character')->orderBy('start_time')->get();

        $attendanceLogs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])->orderBy('timestamp')->get();

        $applicableRates = $user->getApplicableRatesForMonth($targetMonth);


        // --- 2. 月の全日分の表示データを生成 ---
        $monthlyReport = [];
        for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
            $currentDate = $targetMonth->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');

            // ▼▼▼【ここからロジックを修正】▼▼▼
            // その日のデータをそれぞれ取得
            $workShiftForDay = $workShifts->get($dateString); // ユーザー設定
            $manualAttendance = $overriddenAttendances->get($dateString); // 手動編集データ
            $dayAttendanceLogs = $attendanceLogs->filter(fn($log) => $log->timestamp->isSameDay($currentDate)); // 勤怠ログ

            // ビューに渡す基本データを作成
            $reportData = [
                'date' => $currentDate,
                'public_holiday' => $holidays->get($dateString),
                'work_shift' => $workShiftForDay, // 'user_holiday'の代わりに'work_shift'として渡す
            ];

            // 表示タイプを決定する
            if ($manualAttendance) {
                // 手動編集データが最優先
                $reportData['type'] = 'edited';
                $reportData['summary'] = $manualAttendance;
                $dayWorkLogs = $workLogs->filter(fn($log) => $log->start_time->isSameDay($currentDate));
                $reportData['logs'] = $dayWorkLogs;
                $reportData['worklog_total_seconds'] = $dayWorkLogs->sum('effective_duration'); // WorkLogベースの実働時間を追加
            } elseif ($dayAttendanceLogs->isNotEmpty()) {
                // 勤怠ログがあれば「勤務日」
                $reportData['type'] = 'workday';
                $reportData['sessions'] = $this->calculateSessionsFromLogs($dayAttendanceLogs, $workLogs, $user);
            } else {
                // ログがなければ「休日」
                $reportData['type'] = 'day_off';
                $reportData['sessions'] = [];
            }
            $monthlyReport[$dateString] = $reportData;
        }

        // --- 3. 月の合計を計算 ---
        $monthTotalSalary = 0;
        $monthTotalActualWorkSeconds = 0;
        foreach ($monthlyReport as $report) {
            if ($report['type'] === 'edited') {
                $monthTotalActualWorkSeconds += $report['worklog_total_seconds']; // 実働時間をWorkLogベースで集計
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

    // ▼▼▼【追加】勤怠ログから勤務セッションを計算するメソッドを抽出 ▼▼▼
    private function calculateSessionsFromLogs($dayAttendanceLogs, $allWorkLogs, $user)
    {
        $sessions = [];
        $currentSession = null;
        foreach ($dayAttendanceLogs as $log) {
            if ($log->type === 'clock_in') {
                if ($currentSession) {
                    $sessions[] = $currentSession;
                }
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
        if ($currentSession) {
            $sessions[] = $currentSession;
        }

        return collect($sessions)->map(function ($session) use ($allWorkLogs, $user) {
            $startTime = $session['start_time'];
            $endTime = $session['end_time'];

            // ▼▼▼【ここから修正】給与計算のロジックを拘束時間ベースに変更 ▼▼▼
            $breakSeconds = $this->calculateTimeDifference($session['logs'], 'break_start', 'break_end') + $this->calculateTimeDifference($session['logs'], 'away_start', 'away_end');
            $payableWorkSeconds = 0;
            $detentionSeconds = 0;

            // 退勤打刻がある場合のみ、勤務時間を計算する
            if ($endTime) {
                $detentionSeconds = $startTime->diffInSeconds($endTime);
                $payableWorkSeconds = max(0, $detentionSeconds - $breakSeconds); // 給与計算用の時間は拘束時間ベース
            }
            // ▲▲▲【ここまで修正】▲▲▲

            $sessionWorkLogs = $allWorkLogs->where('start_time', '>=', $startTime)
                ->when($endTime, fn($q) => $q->where('end_time', '<=', $endTime));
            $actualWorkSeconds = $sessionWorkLogs->sum('effective_duration'); // 実働時間はWorkLogベースに戻す
            $rateForDay = $user->getHourlyRateForDate($startTime);
            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'detention_seconds' => $detentionSeconds, // 拘束時間
                'break_seconds' => $breakSeconds, // 休憩・中抜け時間
                'actual_work_seconds' => $actualWorkSeconds, // 実働時間
                'daily_salary' => $rateForDay > 0 ? round(($payableWorkSeconds / 3600) * $rateForDay) : 0, // 新しい基準で給与を計算
                'logs' => $sessionWorkLogs,
            ];
        });
    }


    /**
     * 1行分の勤怠データを更新する (手動編集・オーバーライド用)
     * ▼▼▼ このメソッドは変更なし、そのまま利用 ▼▼▼
     */
    public function updateSingle(Request $request, User $user, $date)
    {
        // ... 既存のコードのまま ...
        $validated = $request->validate([
            'start_time' => 'nullable|required_with:end_time|date_format:H:i',
            'end_time' => 'nullable|required_with:start_time|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $targetDate = Carbon::parse($date);
        if (!$targetDate) {
            return response()->json(['success' => false, 'message' => '無効な日付です。'], 400);
        }

        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $startTime = $targetDate->copy()->setTimeFromTimeString($validated['start_time']);
            $endTime = $targetDate->copy()->setTimeFromTimeString($validated['end_time']);

            if ($endTime <= $startTime) {
                $endTime->addDay();
            }

            $breakSeconds = (int)($validated['break_minutes'] ?? 0) * 60;

            $attendanceSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();
            $actualWorkSeconds = max(0, $attendanceSeconds - $breakSeconds);

            $attendance = Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'break_seconds' => $breakSeconds,
                    'actual_work_seconds' => $actualWorkSeconds,
                    'note' => $validated['note'],
                    'status' => 'edited',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => '更新しました。',
                'attendance' => [
                    'actual_work_seconds_formatted' => gmdate('H:i:s', $attendance->actual_work_seconds),
                    'daily_salary_formatted' => '¥' . number_format($attendance->daily_salary)
                ]
            ]);
        }

        Attendance::where('user_id', $user->id)->where('date', $date)->delete();
        return response()->json(['success' => true, 'message' => 'データをクリアしました。']);
    }

    /**
     * ▼▼▼【追加】休憩・中抜け時間計算用のヘルパーメソッド ▼▼▼
     */
    private function calculateTimeDifference($logs, $startType, $endType): int
    {
        $totalSeconds = 0;
        $startTime = null;
        foreach ($logs as $log) {
            if ($log->type === $startType) {
                $startTime = $log->timestamp;
            } elseif ($log->type === $endType && $startTime) {
                $totalSeconds += $startTime->diffInSeconds($log->timestamp);
                $startTime = null; // 次のペアのためにリセット
            }
        }
        return $totalSeconds;
    }
}

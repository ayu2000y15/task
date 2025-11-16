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
use App\Models\AttendanceBreak;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\DefaultShiftPattern;
use App\Models\TransportationExpense;

class AttendanceController extends Controller
{
    /**
     * 勤怠明細の表示
     */
    public function show(User $user, $month = null)
    {
        $this->authorize('viewAny', WorkLog::class);

        $targetMonth = $month ? Carbon::parse($month) : Carbon::now();
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // --- 1. 必要なデータを全て取得 ---
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));
        $workShifts = WorkShift::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));
        $workLogs = WorkLog::where('user_id', $user->id)
            ->where('status', 'stopped')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->with('task.project', 'task.character')
            ->orderBy('start_time')->get();

        // 月のWorkLog合計時間を計算
        $monthTotalWorkLogSeconds = $workLogs->sum('effective_duration');
        $attendanceLogs = AttendanceLog::where('user_id', $user->id)->whereBetween('timestamp', [$startDate, $endDate])->orderBy('timestamp')->get();
        $applicableRates = $user->getApplicableRatesForMonth($targetMonth);

        // 手動編集された勤怠データを取得
        $overriddenAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('breaks') // ▼▼▼【修正】リレーションをEager Loadする
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // --- 2. 必要な追加データを取得 (交通費) ---
        $transportationExpenses = TransportationExpense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($t) => $t->date->format('Y-m-d'));

        // --- 3. 月の全日分の表示データを生成 ---
        $monthlyReport = [];
        for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
            $currentDate = $targetMonth->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');

            $manualAttendance = $overriddenAttendances->get($dateString);
            $dayAttendanceLogs = $attendanceLogs->filter(fn($log) => $log->timestamp->isSameDay($currentDate));

            // build transportation items and tooltip
            $tItems = [];
            if (isset($transportationExpenses[$dateString])) {
                foreach ($transportationExpenses[$dateString] as $t) {
                    $tItems[] = [
                        'project' => $t->project?->name ?? '-',
                        'route' => trim(($t->departure ?? '') . ' → ' . ($t->destination ?? '')),
                        'notes' => $t->notes ?? '',
                        'amount' => $t->amount,
                    ];
                }
            }
            $transportationTooltip = '';
            if (!empty($tItems)) {
                $lines = array_map(function ($it) {
                    $parts = [];
                    $parts[] = '案件: ' . ($it['project'] ?? '-');
                    if (!empty($it['route'])) $parts[] = '区間: ' . $it['route'];
                    if (!empty($it['notes'])) $parts[] = '備考: ' . $it['notes'];
                    $parts[] = '金額: ¥' . number_format($it['amount'], 0);
                    return implode(' / ', $parts);
                }, $tItems);
                $transportationTooltip = implode("\n", $lines);
            }

            $reportData = [
                'date' => $currentDate,
                'public_holiday' => $holidays->get($dateString),
                'work_shift' => $workShifts->get($dateString),
                'location' => null,
                'transportation' => isset($transportationExpenses[$dateString]) ? $transportationExpenses[$dateString]->sum('amount') : 0,
                'transportation_items' => $tItems,
                'transportation_tooltip' => $transportationTooltip,
            ];

            // Decide location: prefer explicit work_shift, otherwise fallback to default shift pattern
            $ws = $workShifts->get($dateString);
            if ($ws && isset($ws->location)) {
                $reportData['location'] = $ws->location;
            } else {
                $dow = $currentDate->dayOfWeek; // 0 (Sunday) - 6 (Saturday)
                $defaultPattern = DefaultShiftPattern::where('user_id', $user->id)
                    ->where('day_of_week', $dow)
                    ->first();
                if ($defaultPattern && $defaultPattern->is_workday) {
                    $reportData['location'] = $defaultPattern->location;
                } else {
                    $reportData['location'] = null;
                }
            }


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
        $monthTotalActualWorkSeconds = 0; // 支払対象時間
        $monthTotalDetentionSeconds = 0; // 総勤務時間
        $monthTotalBreakSeconds = 0;     // 総休憩時間

        foreach ($monthlyReport as $report) {
            if ($report['type'] === 'edited') {
                $monthTotalDetentionSeconds += $report['summary']->detention_seconds;
                $monthTotalBreakSeconds += $report['summary']->break_seconds;
                // 「実働時間」ではなく、日給計算の基準となる「支払対象時間」を加算する
                $monthTotalActualWorkSeconds += $report['summary']->actual_work_seconds;
                $monthTotalSalary += $report['summary']->daily_salary;
            } elseif ($report['type'] === 'workday') {
                $monthTotalDetentionSeconds += collect($report['sessions'])->sum('detention_seconds');
                $monthTotalBreakSeconds += collect($report['sessions'])->sum('break_seconds');
                // 「実働時間」ではなく、日給計算の基準となる「支払対象時間」を加算する
                $monthTotalActualWorkSeconds += collect($report['sessions'])->sum('payable_work_seconds');
                $monthTotalSalary += collect($report['sessions'])->sum('daily_salary');
            }
        }

        return view('admin.attendances.show', compact(
            'user',
            'targetMonth',
            'monthlyReport',
            'monthTotalActualWorkSeconds',
            'monthTotalSalary',
            'applicableRates',
            'monthTotalDetentionSeconds',
            'monthTotalBreakSeconds',
            'monthTotalWorkLogSeconds'
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

            $breakLogs = $this->pairLogEvents($session['logs'], 'break_start', 'break_end', '休憩');
            $awayLogs = $this->pairLogEvents($session['logs'], 'away_start', 'away_end', '中抜け');
            $breakDetails = collect(array_merge($breakLogs, $awayLogs))->sortBy('start_time')->values()->all();
            $breakSeconds = collect($breakDetails)->sum('duration');


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
                'actual_work_seconds' => $actualWorkSeconds, // テーブルの「実働時間」列に表示
                'payable_work_seconds' => $payableWorkSeconds, // サマリーの「支払対象時間」の計算に使用
                'daily_salary' => $rateForDay > 0 ? round(($payableWorkSeconds / 3600) * $rateForDay) : 0,
                'logs' => $sessionWorkLogs,
                'break_details' => $breakDetails,
            ];
        });
    }

    /**
     * 1行分の勤怠データを更新する (日付単位での上書き)
     */
    public function updateSingle(Request $request, User $user, $date)
    {
        $validated = $request->validate([
            'start_time' => 'nullable|required_with:end_time|date_format:H:i',
            'end_time' => 'nullable|required_with:start_time|date_format:H:i',
            'note' => 'nullable|string|max:500',
            'breaks' => 'nullable|array',
            'breaks.*.type' => 'required|string|in:break,away',
            'breaks.*.start_time' => 'required|date_format:H:i',
            'breaks.*.end_time' => 'required|date_format:H:i',
            'is_day_off' => 'nullable|boolean',
        ]);

        $targetDate = Carbon::parse($date);

        // --- 休日として登録する場合の処理 ---
        if (!empty($validated['is_day_off'])) {
            DB::transaction(function () use ($user, $targetDate, $validated) {
                // Attendanceレコードを休日として登録（時刻と給料はnull/0）
                $attendance = Attendance::where('user_id', $user->id)->where('date', $targetDate)->first();
                if ($attendance) {
                    // 既存のbreaksを削除
                    $attendance->breaks()->delete();
                }

                Attendance::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $targetDate->format('Y-m-d')],
                    [
                        'start_time' => null,
                        'end_time' => null,
                        'break_seconds' => 0,
                        'actual_work_seconds' => 0,
                        'note' => $validated['note'] ?? null,
                        'status' => 'edited',
                        'daily_salary' => 0,
                        'is_registered_day_off' => true,
                    ]
                );

                // WorkShiftにも休日として登録
                WorkShift::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date' => $targetDate->format('Y-m-d')
                    ],
                    [
                        'type' => 'full_day_off',
                        'notes' => $validated['note'] ?? null,
                        'name' => '登録休日',
                        'start_time' => null,
                        'end_time' => null,
                        'break_minutes' => 0,
                        'location' => null,
                    ]
                );

                // その日の交通費を削除
                TransportationExpense::where('user_id', $user->id)
                    ->whereDate('date', $targetDate)
                    ->delete();
            });

            return response()->json([
                'success' => true,
                'message' => '休日として登録しました。交通費がある場合は削除されました。'
            ]);
        }

        // --- 出勤・退勤時刻がなければ勤怠データと休日登録を削除 ---
        if (empty($validated['start_time']) || empty($validated['end_time'])) {
            DB::transaction(function () use ($user, $targetDate) {
                $attendance = Attendance::where('user_id', $user->id)->where('date', $targetDate)->first();
                if ($attendance) {
                    $attendance->breaks()->delete();
                    $attendance->delete();
                }

                // 手動で登録した休日も削除
                WorkShift::where('user_id', $user->id)
                    ->where('date', $targetDate)
                    ->where('type', 'full_day_off')
                    ->delete();
            });
            return response()->json(['success' => true, 'message' => 'データをクリアしました。']);
        }


        // --- 1. 勤務時間と休憩時間のDateTimeオブジェクトを準備 ---
        $shiftStart = $targetDate->copy()->setTimeFromTimeString($validated['start_time']);
        $shiftEnd = $targetDate->copy()->setTimeFromTimeString($validated['end_time']);
        if ($shiftEnd->lte($shiftStart)) {
            $shiftEnd->addDay();
        }

        $breakIntervals = []; // 休憩期間を格納する配列
        if (!empty($validated['breaks'])) {
            foreach ($validated['breaks'] as $index => $breakData) {
                if (empty($breakData['start_time']) || empty($breakData['end_time'])) continue;

                // 休憩時間の日付またぎを正確に処理する
                $breakStart = $targetDate->copy()->setTimeFromTimeString($breakData['start_time']);
                $breakEnd = $targetDate->copy()->setTimeFromTimeString($breakData['end_time']);

                // 勤務開始時刻より休憩開始時刻が早い場合、休憩は翌日と判断
                if ($breakStart->lt($shiftStart)) {
                    $breakStart->addDay();
                    // 終了時刻も同様に調整が必要になる可能性がある
                    if ($breakEnd->lt($breakStart)) {
                        $breakEnd->addDay();
                    }
                }
                // 上記調整後、純粋に休憩自体が日付をまたぐ場合
                if ($breakEnd->lte($breakStart)) {
                    $breakEnd->addDay();
                }

                $breakIntervals[$index] = [
                    'start' => $breakStart,
                    'end' => $breakEnd,
                    'type' => $breakData['type'],
                ];
            }
        }

        // --- 2. バリデーションチェック ---
        $totalBreakSeconds = 0;
        foreach ($breakIntervals as $index => $interval) {
            // [バリデーション] 休憩が勤務時間の範囲外でないか
            if ($interval['start']->lt($shiftStart) || $interval['end']->gt($shiftEnd)) {
                throw ValidationException::withMessages([
                    'breaks.' . $index . '.start_time' => "休憩時間が勤務時間の範囲外です。",
                ]);
            }
            $totalBreakSeconds += $interval['start']->diffInSeconds($interval['end']);
        }

        // [バリデーション] 休憩の合計が拘束時間を超えていないか
        $detentionSeconds = $shiftStart->diffInSeconds($shiftEnd);
        if ($totalBreakSeconds > $detentionSeconds) {
            throw ValidationException::withMessages(['breaks.0.start_time' => '休憩時間の合計が拘束時間を超えています。']);
        }

        // [バリデーション] 休憩時間同士が重複していないか
        $count = count($breakIntervals);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a_start = $breakIntervals[$i]['start'];
                $a_end = $breakIntervals[$i]['end'];
                $b_start = $breakIntervals[$j]['start'];
                $b_end = $breakIntervals[$j]['end'];

                if ($a_start->lt($b_end) && $a_end->gt($b_start)) {
                    throw ValidationException::withMessages([
                        'breaks.' . $i . '.start_time' => '休憩時間が他の休憩時間と重複しています。',
                        'breaks.' . $j . '.start_time' => '', // 片方だけで良い
                    ]);
                }
            }
        }

        // --- 3. 日給計算 ---
        $rate = $user->getHourlyRateForDate($targetDate);
        $payableWorkSeconds = max(0, $detentionSeconds - $totalBreakSeconds);
        $dailySalary = ($rate > 0) ? round(($payableWorkSeconds / 3600) * $rate) : 0;

        // --- 4. データベースへの保存 ---
        DB::transaction(function () use ($user, $targetDate, $shiftStart, $shiftEnd, $totalBreakSeconds, $payableWorkSeconds, $dailySalary, $validated, $breakIntervals) {
            // 既存の休日登録があれば削除（勤務データを保存する場合は休日ではない）
            WorkShift::where('user_id', $user->id)
                ->where('date', $targetDate)
                ->where('type', 'full_day_off')
                ->delete();

            $attendance = Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $targetDate->format('Y-m-d')],
                [
                    'start_time' => $shiftStart,
                    'end_time' => $shiftEnd,
                    'break_seconds' => $totalBreakSeconds,
                    'actual_work_seconds' => $payableWorkSeconds,
                    'note' => $validated['note'],
                    'status' => 'edited',
                    'daily_salary' => $dailySalary,
                    'is_registered_day_off' => false,
                ]
            );

            $attendance->breaks()->delete();
            foreach ($breakIntervals as $interval) {
                $attendance->breaks()->create([
                    'type' => $interval['type'],
                    'start_time' => $interval['start'],
                    'end_time' => $interval['end'],
                ]);
            }
        });


        return response()->json(['success' => true, 'message' => '更新しました。']);
    }

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

    private function pairLogEvents($logs, $startType, $endType, $label): array
    {
        $pairs = [];
        $startTime = null;
        foreach ($logs as $log) {
            if ($log->type === $startType) {
                // 開始が連続した場合、最後を優先
                $startTime = $log->timestamp;
            } elseif ($log->type === $endType && $startTime) {
                $pairs[] = [
                    'type' => $label,
                    'start_time' => $startTime,
                    'end_time' => $log->timestamp,
                    'duration' => $startTime->diffInSeconds($log->timestamp),
                ];
                $startTime = null; // ペアを成立させたのでリセット
            }
        }
        return $pairs;
    }
}

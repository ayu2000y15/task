<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\Attendance;
use App\Models\UserHoliday;
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

        // 祝日データを取得
        $dbHolidays = Holiday::whereBetween('date', [$startDate, $endDate])->get();
        $publicHolidays = [];
        foreach ($dbHolidays as $holiday) {
            $publicHolidays[$holiday->date->format('Y-m-d')] = $holiday->name;
        }

        // その月の既存の勤怠データを取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // ユーザーの登録休日を取得
        $userHolidays = UserHoliday::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // 詳細表示用に、その月の作業ログも取得
        $workLogs = WorkLog::where('user_id', $user->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->where('status', 'stopped')
            ->with('task.project')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn($log) => $log->start_time->format('Y-m-d'));

        // 1. 月の開始日時点で有効な最新の時給を1件取得
        $baseRate = $user->hourlyRates()
            ->where('effective_date', '<=', $startDate)
            ->orderBy('effective_date', 'desc')
            ->first();

        // 2. 月の途中で有効になる時給を全て取得 (初日は除く)
        $ratesStartingInMonth = $user->hourlyRates()
            ->whereBetween('effective_date', [$startDate->copy()->addDay(), $endDate])
            ->orderBy('effective_date', 'asc')
            ->get();

        // 3. 上記2つを結合して、その月に適用される時給リストを作成
        $applicableRates = collect();
        if ($baseRate) {
            $applicableRates->push($baseRate);
        }
        $applicableRates = $applicableRates->merge($ratesStartingInMonth);

        // 月の全日分のレポートデータを生成
        $monthlyReport = [];
        for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
            $currentDate = $targetMonth->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateString) ?? new Attendance(['date' => $currentDate]);

            $monthlyReport[] = [
                'date' => $currentDate,
                'attendance' => $attendance,
                'user_holiday' => $userHolidays->get($dateString),
                'public_holiday_name' => $publicHolidays[$dateString] ?? null,
                'logs' => $workLogs->get($dateString, collect()),
            ];
        }

        // 月の合計を計算
        $monthTotalActualWorkSeconds = $attendances->sum('actual_work_seconds');
        $monthTotalSalary = $attendances->sum(fn($att) => $att->daily_salary);

        // JavaScriptでの計算用に、ユーザーの時給履歴をJSONで渡す
        $hourlyRatesJson = $user->hourlyRates()->get(['rate', 'effective_date'])->toJson();

        return view('admin.attendances.show', compact(
            'user',
            'targetMonth',
            'monthlyReport',
            'monthTotalActualWorkSeconds',
            'monthTotalSalary',
            'hourlyRatesJson',
            'applicableRates'
        ));
    }

    /**
     * 作業ログから勤怠データを生成・更新
     */
    public function generate(User $user, $month)
    {

        $targetMonth = Carbon::parse($month)->startOfMonth();
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        $existingAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));

        $workLogs = WorkLog::where('user_id', $user->id)
            ->where('status', 'stopped')->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')->get()->groupBy(fn($log) => $log->start_time->format('Y-m-d'));

        $processedCount = 0;

        foreach ($workLogs as $date => $dayLogs) {
            // 手動編集済みのデータはスキップする
            if ($existingAttendances->has($date) && $existingAttendances->get($date)->status === 'edited') {
                continue;
            }

            $firstStartTime = $dayLogs->min('start_time');
            $lastEndTime = $dayLogs->max('end_time');

            $actualWorkSeconds = 0;
            $sortedLogs = $dayLogs->sortBy('start_time')->values();
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

            $attendanceSeconds = $lastEndTime->getTimestamp() - $firstStartTime->getTimestamp();
            $breakSeconds = $attendanceSeconds - $actualWorkSeconds;

            Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'start_time' => $firstStartTime,
                    'end_time' => $lastEndTime,
                    'break_seconds' => max(0, $breakSeconds),
                    'actual_work_seconds' => $actualWorkSeconds,
                    'note' => null,
                    'status' => 'calculated',
                ]
            );

            $processedCount++;
        }

        if ($processedCount > 0) {
            $message = $targetMonth->format('Y年n月') . 'の勤怠データ（' . $processedCount . '日分）を更新しました。手動編集された行はスキップされました。';
        } else {
            $message = '更新対象のデータがありませんでした。';
        }

        return redirect()->route('admin.attendances.show', ['user' => $user, 'month' => $month])
            ->with('success', $message);
    }

    /**
     * 1行分の勤怠データを更新する
     */
    public function updateSingle(Request $request, User $user, $date)
    {

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

            // ▼▼▼【ここを修正】拘束時間の計算方法をより確実な方法に変更 ▼▼▼
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

            // 更新後のデータをJSONで返す
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
}

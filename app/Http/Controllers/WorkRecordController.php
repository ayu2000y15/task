<?php
// app/Http/Controllers/WorkRecordController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkLog;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewOwn', WorkLog::class);
        $user = Auth::user();
        $viewMode = $request->input('view', 'list');

        $period = $request->input('period', 'month');
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

        // 期間内の作業ログ(WorkLog)の実働時間合計を算出してサマリーに追加
        $workLogsForSummary = WorkLog::where('user_id', $user->id)
            ->where(function ($query) use ($startDate, $endDate) {
                // 期間内に開始したログ
                $query->whereBetween('start_time', [$startDate, $endDate])
                    // または、期間開始前に開始して、まだ実行中か、期間内に終了したログ
                    ->orWhere(function ($q) use ($startDate) {
                        $q->where('start_time', '<', $startDate)
                            ->where(function ($sub) use ($startDate) {
                                $sub->whereNull('end_time')
                                    ->orWhere('end_time', '>=', $startDate);
                            });
                    });
            })->get();

        $totalWorkLogSeconds = $workLogsForSummary->sum(function ($log) use ($startDate, $endDate) {
            // 期間との重なり部分のみを合算する
            $periodStart = $startDate;
            $periodEnd = $endDate;

            $startTimeInPeriod = $log->display_start_time->isBefore($periodStart) ? $periodStart : $log->display_start_time;

            $endTime = $log->display_end_time;
            if ($endTime === null) {
                $endTimeInPeriod = now()->isAfter($periodEnd) ? $periodEnd : now();
            } else {
                $endTimeInPeriod = $endTime->isAfter($periodEnd) ? $periodEnd : $endTime;
            }

            if ($startTimeInPeriod->isAfter($endTimeInPeriod)) {
                return 0;
            }

            return $startTimeInPeriod->diffInSeconds($endTimeInPeriod);
        });

        $summary['worklog_seconds'] = $totalWorkLogSeconds;

        $templateData = [
            'viewMode' => $viewMode,
            'user' => $user,
            'period' => $period,
            'summary' => $summary,
            'targetDate' => $targetDate,
        ];


        if ($viewMode === 'list') {
            $workLogs = WorkLog::where('user_id', $user->id)
                ->where(function ($query) use ($startDate, $endDate) {
                    // 期間内に開始したログ
                    $query->whereBetween('start_time', [$startDate, $endDate])
                        // または、期間開始前に開始して、まだ実行中か、期間内に終了したログ
                        ->orWhere(function ($q) use ($startDate) {
                            $q->where('start_time', '<', $startDate)
                                ->where(function ($sub) use ($startDate) {
                                    $sub->whereNull('end_time')
                                        ->orWhere('end_time', '>=', $startDate);
                                });
                        });
                })->get();

            $attendanceLogs = AttendanceLog::where('user_id', $user->id)
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->get();

            $allLogs = $this->mapAndMergeLogs($workLogs, $attendanceLogs);
            $logsByDate = $allLogs->groupBy(fn($item) => $item->timestamp->format('Y-m-d'));

            $dailySummaries = collect();
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateString = $date->format('Y-m-d');
                $dayLogs = $logsByDate->get($dateString);

                if ($dayLogs && $dayLogs->isNotEmpty()) {
                    $sortedDayLogs = $this->sortTimelineItems($dayLogs);
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
        } else { // timeline
            $currentDate = $targetDate;
            $workLogs = WorkLog::where('user_id', $user->id)
                ->where(function ($query) use ($currentDate) {
                    $dayStart = $currentDate->copy()->startOfDay();
                    $dayEnd = $currentDate->copy()->endOfDay();
                    // 表示日に開始したログ
                    $query->whereBetween('start_time', [$dayStart, $dayEnd])
                        // または、表示日より前に開始して、まだ実行中か、表示日以降に終了したログ
                        ->orWhere(function ($q) use ($dayStart) {
                            $q->where('start_time', '<', $dayStart)
                                ->where(function ($sub) use ($dayStart) {
                                    $sub->whereNull('end_time')
                                        ->orWhere('end_time', '>=', $dayStart);
                                });
                        });
                })->get();

            $attendanceLogs = AttendanceLog::where('user_id', $user->id)->whereDate('timestamp', $currentDate)->get();

            $mergedLogs = $this->mapAndMergeLogs($workLogs, $attendanceLogs);
            $templateData['timelineItems'] = $this->sortTimelineItems($mergedLogs);

            $templateData['totalSeconds'] = $workLogs->sum(function ($log) use ($currentDate) {
                // 日跨ぎログの当日の稼働時間だけを合計する
                $dayStart = $currentDate->copy()->startOfDay();
                $dayEnd = $currentDate->copy()->endOfDay();

                // ログの開始がその日の開始より前なら、その日の開始時刻を計算の開始点とする
                $startTimeInPeriod = $log->display_start_time->isBefore($dayStart) ? $dayStart : $log->display_start_time;

                // ログの終了がその日の終了より後なら、その日の終了時刻を計算の終了点とする
                // ログが継続中(end_timeがnull)なら、現在時刻を計算の終了点とする
                $endTime = $log->display_end_time;
                if ($endTime === null) {
                    $endTimeInPeriod = now()->isAfter($dayEnd) ? $dayEnd : now();
                } else {
                    $endTimeInPeriod = $endTime->isAfter($dayEnd) ? $dayEnd : $endTime;
                }

                // 開始が終了より後の場合は0を返す
                if ($startTimeInPeriod->isAfter($endTimeInPeriod)) {
                    return 0;
                }

                return $startTimeInPeriod->diffInSeconds($endTimeInPeriod);
            });
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

    /**
     * 作業時間を手動で更新する
     */
    public function updateTime(Request $request, WorkLog $workLog)
    {
        $this->authorize('update', $workLog);

        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
        ]);

        $originalDate = $workLog->display_start_time->copy()->startOfDay();

        // 2. 取得した日付と、フォームから送られてきた時刻を結合して、新しい日時(Carbonオブジェクト)を作成
        //    (タイムゾーンを日本時間に指定)
        $editedStartTime = Carbon::parse($validated['start_time'], 'JST')->setDateFrom($originalDate);
        $editedEndTime   = Carbon::parse($validated['end_time'],   'JST')->setDateFrom($originalDate);

        // 3. もし終了時刻が開始時刻より前なら、終了日の日付を1日進める
        if ($editedEndTime->lt($editedStartTime)) {
            $editedEndTime->addDay();
        }

        // 4. データベースを更新
        $workLog->update([
            'edited_start_time'  => $editedStartTime,
            'edited_end_time'    => $editedEndTime,
            'is_manually_edited' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => '作業時間を更新しました。',
            'log' => $this->formatWorkLogForResponse($workLog->fresh())
        ]);
    }

    /**
     * 手動更新をリセットする
     */
    public function resetTime(WorkLog $workLog)
    {
        $this->authorize('update', $workLog);

        $workLog->update([
            'edited_start_time'  => null,
            'edited_end_time'    => null,
            'is_manually_edited' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => '作業時間をリセットしました。',
            'log' => $this->formatWorkLogForResponse($workLog->fresh())
        ]);
    }

    /**
     * フロントエンド返却用にデータを整形するヘルパーメソッド
     */
    private function formatWorkLogForResponse(WorkLog $log): array
    {
        $duration = $log->effective_duration;

        return [
            'id' => $log->id,
            'memo' => $log->memo,
            'is_manually_edited' => $log->is_manually_edited,
            'display_start_time' => optional($log->display_start_time)->toIso8601String(),
            'display_end_time' => optional($log->display_end_time)->toIso8601String(),
            // 表示用にフォーマット済みの文字列も生成して渡す
            'display_start_time_formatted' => optional($log->display_start_time)->format('H:i'),
            'display_end_time_formatted' => optional($log->display_end_time)->format('H:i'),
            'effective_duration_formatted' => $duration > 0 ? gmdate('H:i:s', $duration) : '00:00:00',
        ];
    }

    /**
     *
     * @param \Illuminate\Support\Collection $items
     * @return \Illuminate\Support\Collection
     */
    private function sortTimelineItems(Collection $items): Collection
    {
        return $items->sort(function ($a, $b) {
            // 1. まず時刻で比較する
            $timestampComparison = $a->timestamp <=> $b->timestamp;

            // 2. 時刻が同じ場合のみ、ログの種類で比較する
            if ($timestampComparison === 0) {
                // 'attendance_log' が 'work_log' より先に来るように文字列で比較
                return $a->type <=> $b->type;
            }

            return $timestampComparison;
        })->values(); // sort後にキーをリセットする
    }
}

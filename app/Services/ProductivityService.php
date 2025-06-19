<?php
// app/Services/ProductivityService.php

namespace App\Services;

use App\Models\User;
use App\Models\WorkLog;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductivityService
{
    /**
     * 全てのアクティブユーザーの生産性サマリーを取得する
     * @return \Illuminate\Support\Collection
     */
    public function getSummariesForAllActiveUsers()
    {
        // ユーザー一覧は毎回取得するが、各ユーザーの計算結果はキャッシュを利用する
        $users = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();

        return $users->map(function ($user) {
            return (object)[
                'user' => $user,
                // キャッシュキーに今日の日付を含めることで、日付が変わるとキャッシュがクリアされる
                'yesterday' => Cache::remember('productivity_yesterday_' . $user->id . '_' . now()->subDay()->format('Ymd'), 600, fn() => $this->calculateSummaryForUser($user, now()->subDay()->startOfDay(), now()->subDay()->endOfDay())),
                'month' => Cache::remember('productivity_month_' . $user->id . '_' . now()->format('Ym'), 600, fn() => $this->calculateSummaryForUser($user, now()->startOfMonth(), now()->endOfMonth())),
            ];
        });
    }

    /**
     * 特定のユーザーと期間のサマリーを計算する
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return object
     */
    public function calculateSummaryForUser(User $user, Carbon $startDate, Carbon $endDate): object
    {
        // 1. 総作業時間 (WorkLogの合計)
        $totalWorkLogSeconds = WorkLog::where('user_id', $user->id)
            ->where('status', 'stopped')
            ->whereNotNull('end_time') // end_timeがNULLのレコードは計算から除外
            ->whereBetween('start_time', [$startDate, $endDate])
            ->sum(DB::raw('TIMESTAMPDIFF(SECOND, start_time, end_time)'));

        // 2. 勤怠ログから各種時間を計算
        $attendanceLogs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp')
            ->get();

        // 休憩時間と中抜け時間の合計
        $totalBreakSeconds = $this->calculateTimeDifference($attendanceLogs, 'break_start', 'break_end')
            + $this->calculateTimeDifference($attendanceLogs, 'away_start', 'away_end');

        // 総拘束時間の計算 (日毎の出勤-退勤時間の合計)
        $logsByDay = $attendanceLogs->groupBy(fn($log) => $log->timestamp->format('Y-m-d'));
        $totalAttendanceSeconds = 0;
        foreach ($logsByDay as $dayLogs) {
            $firstClockIn = $dayLogs->firstWhere('type', 'clock_in');
            $lastClockOut = $dayLogs->last(fn($item) => $item->type === 'clock_out');
            if ($firstClockIn && $lastClockOut) {
                $totalAttendanceSeconds += $firstClockIn->timestamp->diffInSeconds($lastClockOut->timestamp);
            }
        }

        $productiveSeconds = $totalWorkLogSeconds + $totalBreakSeconds;
        $unaccountedSeconds = max(0, $totalAttendanceSeconds - ($totalWorkLogSeconds + $totalBreakSeconds));

        return (object)[
            'totalAttendanceSeconds' => $totalAttendanceSeconds,
            'totalWorkLogSeconds'    => $totalWorkLogSeconds,    // 作業時間を個別に追加
            'totalBreakSeconds'      => $totalBreakSeconds,      // 休憩時間を個別に追加
            'unaccountedSeconds'     => $unaccountedSeconds,      // 空き時間を個別に追加
            // 各要素のパーセンテージを計算
            'workLogPercentage'      => $totalAttendanceSeconds > 0 ? ($totalWorkLogSeconds / $totalAttendanceSeconds) * 100 : 0,
            'breakPercentage'        => $totalAttendanceSeconds > 0 ? ($totalBreakSeconds / $totalAttendanceSeconds) * 100 : 0,
            'unaccountedPercentage'  => $totalAttendanceSeconds > 0 ? ($unaccountedSeconds / $totalAttendanceSeconds) * 100 : 0,
        ];
    }

    private function calculateTimeDifference($logs, $startType, $endType): int
    {
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

    /**
     * ▼▼▼【ここから追加】ログイン中のユーザー専用のサマリー取得メソッド ▼▼▼
     *
     * @return object|null
     */
    public function getSummaryForCurrentUser(): ?object
    {
        if (!Auth::check()) {
            return null;
        }
        $user = Auth::user();

        return (object)[
            'user' => $user,
            'yesterday' => Cache::remember('productivity_yesterday_' . $user->id . '_' . now()->subDay()->format('Ymd'), 600, fn() => $this->calculateSummaryForUser($user, now()->subDay()->startOfDay(), now()->subDay()->endOfDay())),
            'month' => Cache::remember('productivity_month_' . $user->id . '_' . now()->format('Ym'), 600, fn() => $this->calculateSummaryForUser($user, now()->startOfMonth(), now()->endOfMonth())),
        ];
    }
}

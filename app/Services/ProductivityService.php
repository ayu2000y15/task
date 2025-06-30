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
        // (変更なし)
        $users = User::where('status', User::STATUS_ACTIVE)
            ->where('show_productivity', true) // ★ 追加
            ->orderBy('name')->get();

        return $users->map(function ($user) {
            return (object)[
                'user' => $user,
                'yesterday' => Cache::remember('productivity_yesterday_' . $user->id . '_' . now()->subDay()->format('Ymd'), 600, fn() => $this->calculateSummaryForUser($user, now()->subDay()->startOfDay(), now()->subDay()->endOfDay())),
                'month' => Cache::remember('productivity_month_' . $user->id . '_' . now()->format('Ym'), 600, fn() => $this->calculateSummaryForUser($user, now()->startOfMonth(), now()->endOfMonth())),
            ];
        });
    }

    /**
     * ▼▼▼【新規追加】作業ログの重複を考慮して総作業時間を計算するメソッド ▼▼▼
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return integer
     */
    private function calculateTotalWorkLogSeconds(User $user, Carbon $startDate, Carbon $endDate): int
    {
        // 1. 対象期間と重なる作業ログを全て取得し、開始時間でソート
        $workLogs = WorkLog::where('user_id', $user->id)
            ->where('status', 'stopped')
            ->whereNotNull('end_time')
            ->where('start_time', '<=', $endDate)
            ->where('end_time', '>=', $startDate)
            ->orderBy('start_time')
            ->get();

        if ($workLogs->isEmpty()) {
            return 0;
        }

        // 2. 各ログを対象期間内に収まるように調整し、時間帯のリストを作成
        $intervals = [];
        foreach ($workLogs as $log) {
            $logStart = new Carbon($log->start_time);
            $logEnd = new Carbon($log->end_time);

            // 期間外にはみ出している部分を切り取る
            $effectiveStart = $logStart->max($startDate);
            $effectiveEnd = $logEnd->min($endDate);

            if ($effectiveStart < $effectiveEnd) {
                $intervals[] = ['start' => $effectiveStart, 'end' => $effectiveEnd];
            }
        }

        if (empty($intervals)) {
            return 0;
        }

        // 3. 時間の重複を解消（マージ処理）
        $merged = [];
        // $intervalsはソート済みなので、最初の要素を基準にする
        $currentStart = $intervals[0]['start'];
        $currentEnd = $intervals[0]['end'];

        for ($i = 1; $i < count($intervals); $i++) {
            $nextStart = $intervals[$i]['start'];
            $nextEnd = $intervals[$i]['end'];

            if ($nextStart < $currentEnd) {
                // 次の期間が現在の期間と重複または連続している場合、終了時間を延長
                $currentEnd = $currentEnd->max($nextEnd);
            } else {
                // 重複していない場合、現在の期間をリストに追加し、次の期間を新たな基準にする
                $merged[] = ['start' => $currentStart, 'end' => $currentEnd];
                $currentStart = $nextStart;
                $currentEnd = $nextEnd;
            }
        }
        // ループ終了後、最後の期間をリストに追加
        $merged[] = ['start' => $currentStart, 'end' => $currentEnd];

        // 4. マージされた各時間帯の長さを合計する
        $totalSeconds = 0;
        foreach ($merged as $interval) {
            $totalSeconds += $interval['start']->diffInSeconds($interval['end']);
        }

        return $totalSeconds;
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
        // 1. 総作業時間 (WorkLogの合計) - ▼▼▼【修正】新しい計算メソッドを呼び出す
        $totalWorkLogSeconds = $this->calculateTotalWorkLogSeconds($user, $startDate, $endDate);

        // 2. 勤怠ログを取得
        $attendanceLogs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp')
            ->get();

        // 3. 総勤務時間の計算 (変更なし)
        $totalAttendanceSeconds = $this->calculateTimeDifference($attendanceLogs, 'clock_in', 'clock_out');

        // 4. 休憩時間と中抜け時間の合計 (変更なし)
        $totalBreakSeconds = $this->calculateTimeDifference($attendanceLogs, 'break_start', 'break_end')
            + $this->calculateTimeDifference($attendanceLogs, 'away_start', 'away_end');

        // 5. 空き時間（その他）の計算 (変更なし)
        $unaccountedSeconds = max(0, $totalAttendanceSeconds - $totalWorkLogSeconds - $totalBreakSeconds);

        return (object)[
            'totalAttendanceSeconds' => $totalAttendanceSeconds,
            'totalWorkLogSeconds'    => $totalWorkLogSeconds,
            'totalBreakSeconds'      => $totalBreakSeconds,
            'unaccountedSeconds'     => $unaccountedSeconds,
            'workLogPercentage'      => $totalAttendanceSeconds > 0 ? ($totalWorkLogSeconds / $totalAttendanceSeconds) * 100 : 0,
            'breakPercentage'        => $totalAttendanceSeconds > 0 ? ($totalBreakSeconds / $totalAttendanceSeconds) * 100 : 0,
            'unaccountedPercentage'  => $totalAttendanceSeconds > 0 ? ($unaccountedSeconds / $totalAttendanceSeconds) * 100 : 0,
        ];
    }

    private function calculateTimeDifference($logs, $startType, $endType): int
    {
        // (変更なし)
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
     * ログイン中のユーザー専用のサマリー取得メソッド
     * @return object|null
     */
    public function getSummaryForCurrentUser(): ?object
    {
        // (変更なし)
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

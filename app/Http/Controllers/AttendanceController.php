<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceLog;
use App\Models\WorkLog; // WorkLogモデルを追加
use App\Models\User; // Userモデルを追加
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // DBファサードを追加
use Illuminate\Support\Facades\Log; // Logファサードを追加

class AttendanceController extends Controller
{
    /**
     * 勤怠を打刻する
     */
    public function clock(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:clock_in,clock_out,break_start,break_end,away_start,away_end'],
            'memo' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $type = $request->input('type');
        $now = Carbon::now();

        // ▼▼▼ 状態チェックロジックはそのまま活かします ▼▼▼
        $currentStatus = $user->getCurrentAttendanceStatus();

        $allowedActions = [
            'clocked_out' => ['clock_in'],
            'working'     => ['clock_out', 'break_start', 'away_start'],
            'on_break'    => ['break_end'],
            'on_away'     => ['away_end'],
        ];

        if (!isset($allowedActions[$currentStatus]) || !in_array($type, $allowedActions[$currentStatus])) {
            $currentStatusJapanese = ['clocked_out' => '未出勤', 'working' => '出勤中', 'on_break' => '休憩中', 'on_away' => '中抜け中'][$currentStatus] ?? '不明な状態';
            $requestedActionJapanese = $this->getJapaneseActionName($type);
            return response()->json(['success' => false, 'message' => "現在のステータスは「{$currentStatusJapanese}」です。「{$requestedActionJapanese}」はできません。画面を更新してから再度ボタンを押してください。"], 409);
        }

        if (in_array($type, ['clock_out', 'break_start', 'away_start']) && $user->hasActiveWorkLog()) {
            return response()->json(['success' => false, 'message' => "実行中の作業があります。この操作を続ける前に、すべての作業を停止または完了してください。\n実行中の作業はホームから確認できます。"], 409);
        }
        // ▲▲▲ 状態チェックロジックここまで ▲▲▲

        DB::beginTransaction();
        try {
            // ▼▼▼ 日跨ぎ処理ここから ▼▼▼
            $lastAttendanceLog = AttendanceLog::where('user_id', $user->id)
                ->orderByDesc('timestamp')
                ->first();

            // 最後のログが昨日以前の場合、日跨ぎ処理を実行
            if ($lastAttendanceLog && $lastAttendanceLog->timestamp->isBefore($now->copy()->startOfDay())) {
                $this->handleOvernightShift($user, $lastAttendanceLog);
            }
            // ▲▲▲ 日跨ぎ処理ここまで ▲▲▲

            // 本来の打刻を記録
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => $type,
                'timestamp' => $now,
                'memo' => $request->input('memo'),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Clocking error for user ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'サーバーエラーが発生しました。'], 500);
        }


        // 状態を更新するためにキャッシュをクリア
        Cache::forget('attendance_status_' . $user->id);

        return response()->json([
            'success' => true,
            'message' => $this->getJapaneseActionName($type) . 'しました。',
            'new_status' => $user->fresh()->getCurrentAttendanceStatus(),
        ]);
    }

    /**
     * 日付をまたいだ勤務と作業ログを分割するプライベートメソッド
     */
    private function handleOvernightShift(User $user, AttendanceLog $lastLog)
    {
        $lastLogTimestamp = $lastLog->timestamp;
        $endOfLastDay = $lastLogTimestamp->copy()->endOfDay(); // 前日の23:59:59
        $startOfNextDay = $endOfLastDay->copy()->addSecond(); // 翌日の00:00:00

        // 最後のログが "出勤" または "復帰" 系の場合のみ分割処理を行う
        if (in_array($lastLog->type, ['clock_in', 'break_end', 'away_end'])) {
            // 1. 勤怠ログの分割
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => 'clock_out',
                'timestamp' => $endOfLastDay,
                'memo' => '日跨ぎ自動処理',
            ]);
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => 'clock_in',
                'timestamp' => $startOfNextDay,
                'memo' => '日跨ぎ自動処理',
            ]);

            // 2. 進行中の作業ログの分割
            $activeWorkLog = WorkLog::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('start_time', '<=', $endOfLastDay)
                ->first();

            if ($activeWorkLog) {
                // 既存のログを停止
                $activeWorkLog->update([
                    'end_time' => $endOfLastDay,
                    'status' => 'stopped',
                    'effective_duration' => $endOfLastDay->diffInSeconds($activeWorkLog->start_time),
                ]);

                // 翌日分として新しいログを開始
                WorkLog::create([
                    'user_id' => $user->id,
                    'task_id' => $activeWorkLog->task_id,
                    'start_time' => $startOfNextDay,
                    'end_time' => null,
                    'status' => 'active',
                    'memo' => '（日跨ぎ自動継続）',
                ]);
            }
        }
    }

    private function getJapaneseActionName(string $type): string
    {
        // (このメソッドは変更なし)
        return [
            'clock_in' => '出勤',
            'clock_out' => '退勤',
            'break_start' => '休憩開始',
            'break_end' => '休憩終了',
            'away_start' => '中抜け開始',
            'away_end' => '中抜け終了',
        ][$type] ?? '打刻';
    }

    /**
     * 自分の勤怠履歴を表示する
     */
    public function index(Request $request)
    {
        // (このメソッドは変更なし)
        $user = Auth::user();
        $month = $request->input('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = now()->startOfMonth();
        }
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereYear('timestamp', $currentMonth->year)
            ->whereMonth('timestamp', $currentMonth->month)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy(function ($log) {
                return $log->timestamp->format('Y-m-d');
            });
        return view('my-attendance.index', [
            'logsByDate' => $logs,
            'currentMonth' => $currentMonth,
        ]);
    }
}

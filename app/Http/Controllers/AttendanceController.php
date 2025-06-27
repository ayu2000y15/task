<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
        $currentStatus = $user->getCurrentAttendanceStatus(); // 現在のステータスを先に取得

        // ▼▼▼【ここから変更】状態の不整合をチェックするロジックを追加 ▼▼▼
        $allowedActions = [
            'clocked_out' => ['clock_in'],
            'working'     => ['clock_out', 'break_start', 'away_start'],
            'on_break'    => ['break_end'],
            'on_away'     => ['away_end'],
        ];

        // 現在のステータスから許可されていないアクションが要求された場合
        if (!isset($allowedActions[$currentStatus]) || !in_array($type, $allowedActions[$currentStatus])) {
            // 人が読んで分かりやすいように、ステータスとアクションを日本語に変換
            $currentStatusJapanese = [
                'clocked_out' => '未出勤',
                'working'     => '出勤中',
                'on_break'    => '休憩中',
                'on_away'     => '中抜け中',
            ][$currentStatus] ?? '不明な状態';

            $requestedActionJapanese = $this->getJapaneseActionName($type);

            return response()->json([
                'success' => false,
                'message' => "現在の勤怠状態（{$currentStatusJapanese}）と要求された操作（{$requestedActionJapanese}）が一致しません。\n画面を更新してから再度ボタンを押してください。"
            ], 409); // 409 Conflict: リソースの現在の状態と競合
        }
        // ▲▲▲【変更ここまで】▲▲▲

        if (in_array($type, ['clock_out', 'break_start', 'away_start'])) {
            if ($user->hasActiveWorkLog()) {
                return response()->json([
                    'success' => false,
                    // メッセージをより汎用的に変更
                    'message' => "実行中の作業があります。この操作を続ける前に、すべての作業を停止または完了してください。\n実行中の作業はホームから確認できます。"
                ], 409); // 409 Conflict
            }
        }

        AttendanceLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'timestamp' => Carbon::now(),
            'memo' => $request->input('memo'),
        ]);

        // 状態を更新するためにキャッシュをクリア
        Cache::forget('attendance_status_' . $user->id);

        return response()->json([
            'success' => true,
            'message' => $this->getJapaneseActionName($type) . 'しました。',
            'new_status' => $user->fresh()->getCurrentAttendanceStatus(),
        ]);
    }

    private function getJapaneseActionName(string $type): string
    {
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
        $user = Auth::user();

        // 表示する月を取得 (デフォルトは今月)
        $month = $request->input('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = now()->startOfMonth();
        }

        // その月の勤怠ログを取得
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereYear('timestamp', $currentMonth->year)
            ->whereMonth('timestamp', $currentMonth->month)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy(function ($log) {
                return $log->timestamp->format('Y-m-d');
            });

        // ここで日毎の合計勤務時間などを計算するロジックを追加できます

        return view('my-attendance.index', [
            'logsByDate' => $logs,
            'currentMonth' => $currentMonth,
        ]);
    }
}

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

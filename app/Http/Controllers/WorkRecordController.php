<?php
// app/Http/Controllers/WorkRecordController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkLog;
use Carbon\Carbon;
use App\Models\User;

class WorkRecordController extends Controller
{
    /**
     * 作業実績一覧を表示
     */
    public function index(Request $request)
    {
        // 自身の作業実績を閲覧する権限があるかチェック
        $this->authorize('viewOwn', WorkLog::class);

        // ログインしているユーザーを取得
        $user = Auth::user();

        // リクエストから期間を取得（デフォルトは 'today'）
        $period = $request->input('period', 'today');

        // 期間に応じて日付範囲を設定
        $query = WorkLog::where('user_id', $user->id);

        switch ($period) {
            case 'week':
                $query->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'today':
            default:
                $query->whereBetween('start_time', [now()->startOfDay(), now()->endOfDay()]);
                break;
        }

        // ログインユーザーの作業ログを取得
        $workLogs = $query->with('task.project') // N+1問題対策
            ->orderBy('start_time', 'desc')
            ->get();

        // 合計作業時間（秒）を計算
        // 'effective_duration'は作業ログの有効な期間を秒で持つカラムを想定しています
        $totalSeconds = $workLogs->sum('effective_duration');

        // 統一されたデータをビューに渡す
        return view('work-records.index', [
            'workLogs' => $workLogs,
            'totalSeconds' => $totalSeconds,
            'period' => $period,
        ]);
    }

    /**
     * クエリに期間フィルターを適用するヘルパーメソッド
     */
    private function applyPeriodFilter($query, $period, $request)
    {
        switch ($period) {
            case 'week':
                $query->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('start_time', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                break;
            case 'today':
            default:
                $query->whereDate('start_time', Carbon::today());
                break;
        }

        if ($request->has('date')) {
            $query->whereDate('start_time', Carbon::parse($request->date));
        }
    }
}

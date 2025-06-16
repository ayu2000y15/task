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
        $user = Auth::user();
        $period = $request->input('period', 'today');

        // 共有アカウントの場合
        if ($user->status === User::STATUS_SHARED) {
            $query = WorkLog::where('status', 'stopped')->with('user', 'task.project');

            // 期間フィルタリング
            $this->applyPeriodFilter($query, $period, $request);

            $allLogs = $query->get();

            // ユーザー毎にログと合計時間を集計
            $usersWithLogs = $allLogs->groupBy('user_id')->map(function ($logs, $userId) {
                $firstLog = $logs->first();
                if (!$firstLog || !$firstLog->user) {
                    return null; // ユーザー情報が取得できない場合は除外
                }
                return (object) [
                    'user' => $firstLog->user,
                    'logs' => $logs->sortByDesc('start_time'),
                    'totalSeconds' => $logs->sum('effective_duration'),
                ];
            })->filter()->sortBy(function ($userReport) {
                return $userReport->user->name; // ユーザー名でソート
            });

            $overallTotalSeconds = $allLogs->sum('effective_duration');

            return view('work-records.index', compact('usersWithLogs', 'overallTotalSeconds', 'period'));
        }

        // 通常アカウントの場合
        $query = WorkLog::where('user_id', $user->id)->where('status', 'stopped')->with('task.project');

        // 期間フィルタリング
        $this->applyPeriodFilter($query, $period, $request);

        $workLogs = $query->orderBy('start_time', 'desc')->get();
        $totalSeconds = $workLogs->sum('effective_duration');

        return view('work-records.index', compact('workLogs', 'totalSeconds', 'period'));
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

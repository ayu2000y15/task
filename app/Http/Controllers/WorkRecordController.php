<?php
// app/Http/Controllers/WorkRecordController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkLog;
use Carbon\Carbon;

class WorkRecordController extends Controller
{
    /**
     * 自分の作業実績一覧を表示
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = WorkLog::where('user_id', $user->id)->where('status', 'stopped')->with('task.project');

        // 期間フィルタリング
        $period = $request->input('period', 'today');
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

        $workLogs = $query->orderBy('start_time', 'desc')->get();

        // 休憩を除いた合計時間
        $totalSeconds = $workLogs->sum('effective_duration');

        return view('work-records.index', compact('workLogs', 'totalSeconds', 'period'));
    }
}

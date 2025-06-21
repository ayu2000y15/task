<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\WorkShift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Models\User; // Userモデルをインポート
use App\Models\DefaultShiftPattern; // DefaultShiftPatternモデルをインポート

class ScheduleController extends Controller
{
    /**
     * 全員の月間スケジュールカレンダーを表示します。
     */
    public function calendar(Request $request)
    {
        $this->authorize('viewAllSchedules', \App\Models\User::class);

        $month = $request->input('month', now()->format('Y-m'));
        $targetMonth = Carbon::parse($month)->startOfMonth();
        $startDate = $targetMonth->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $endDate = $targetMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        // (祝日とWorkShiftの取得ロジックは変更なし)
        $publicHolidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()->keyBy(fn($h) => $h->date->format('Y-m-d'));
        $workShifts = WorkShift::with('user:id,name')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get();

        // ▼▼▼【ここから追加】▼▼▼
        // 全アクティブユーザーと、そのデフォルトシフトパターンを取得
        $activeUsers = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();
        $allDefaultPatterns = DefaultShiftPattern::whereIn('user_id', $activeUsers->pluck('id'))
            ->get()
            ->groupBy('user_id');
        // ▲▲▲【追加ここまで】▲▲▲

        // (カレンダーデータ生成ロジックは変更なし)
        $calendarData = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $calendarData[$dateString] = [
                'date' => $date,
                'is_current_month' => $date->isSameMonth($targetMonth),
                'public_holiday' => $publicHolidays->get($dateString),
                'schedules' => $workShifts->where('date', $date)->values(),
            ];
        }

        return view('admin.schedule.calendar', [
            'targetMonth' => $targetMonth,
            'calendarData' => $calendarData,
            'activeUsers' => $activeUsers,             // ← ビューに渡す
            'allDefaultPatterns' => $allDefaultPatterns, // ← ビューに渡す
        ]);
    }
}

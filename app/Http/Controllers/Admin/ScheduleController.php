<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DefaultShiftPattern;
use App\Models\WorkShift;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ScheduleController extends Controller
{
    /**
     * 全ユーザーの月間スケジュールカレンダーの「器」を表示します。
     * イベントデータはAPI経由で非同期に読み込まれます。
     */
    public function showCalendar(Request $request)
    {
        // $this->authorize('viewAllSchedules', User::class);

        // デフォルトシフトパターン表示のためにユーザー情報を取得
        $users = User::where('status', 'active')->orderBy('name')->get();
        $allDefaultPatterns = DefaultShiftPattern::whereIn('user_id', $users->pluck('id'))
            ->get()
            ->groupBy('user_id');

        return view('admin.schedule.calendar', [
            'users' => $users,
            'allDefaultPatterns' => $allDefaultPatterns,
        ]);
    }

    /**
     * FullCalendarからのリクエストに応じて、指定された期間のイベントデータをJSONで返します。
     */
    public function fetchEvents(Request $request)
    {
        // $this->authorize('viewAllSchedules', User::class);

        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();

        // 必要なデータを取得
        $userIds = User::where('status', 'active')->pluck('id');
        $allWorkShifts = WorkShift::with('user')
            ->whereIn('user_id', $userIds)
            ->whereBetween('date', [$start, $end])
            ->get();
        $publicHolidays = Holiday::whereBetween('date', [$start, $end])->get();

        // イベント配列を生成
        $events = [];

        // 祝日をイベントとして追加
        foreach ($publicHolidays as $holiday) {
            $events[] = [
                'id' => 'holiday_' . $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date->format('Y-m-d'),
                'allDay' => true,
                'color' => '#ef4444', // ドットの色を赤に指定
                'classNames' => ['holiday-event'], // スタイルを適用するためのクラス名
                'extendedProps' => ['type' => 'holiday']
            ];
        }

        // 各ユーザーのシフト・休日をイベントとして追加
        foreach ($allWorkShifts as $shift) {
            if ($publicHolidays->contains('date', $shift->date) && $shift->type !== 'work') {
                continue;
            }

            if ($shift->user) {
                $eventData = [
                    'id' => 'shift_' . $shift->id,
                    'start' => $shift->date->format('Y-m-d'),
                    'allDay' => true,
                    'extendedProps' => [
                        'user_name' => $shift->user->name,
                        'notes' => $shift->notes,
                        'location' => $shift->location,
                        'type' => $shift->type,
                    ]
                ];

                switch ($shift->type) {
                    case 'work':
                        $startTime = Carbon::parse($shift->start_time)->format('H:i');
                        $endTime = Carbon::parse($shift->end_time)->format('H:i');
                        $eventData['title'] = $shift->user->name . ": " . $startTime . " - " . $endTime;
                        if ($shift->location === 'remote') {
                            $eventData['color'] = '#3b82f6'; // 在宅用の色 (濃い青)
                        } else {
                            $eventData['color'] = '#93c5fd'; // 出勤用の色 (薄い青)
                        }
                        break;
                    case 'location_only':
                        $eventData['title'] = $shift->user->name;
                        $eventData['color'] = '#a5b4fc'; // 紫系の色
                        $eventData['textColor'] = '#312e81';
                        break;
                    case 'full_day_off':
                        $eventData['title'] = $shift->user->name . ": " . ($shift->name ?? '全休');
                        $eventData['color'] = '#fca5a5'; // 赤系
                        break;
                    case 'am_off':
                        $eventData['title'] = $shift->user->name . ": " . ($shift->name ?? '午前休');
                        $eventData['color'] = '#fde68a'; // 黄色系
                        $eventData['textColor'] = '#713f12';
                        break;
                    case 'pm_off':
                        $eventData['title'] = $shift->user->name . ": " . ($shift->name ?? '午後休');
                        $eventData['color'] = '#bbf7d0'; // 緑系
                        $eventData['textColor'] = '#14532d';
                        break;
                }
                $events[] = $eventData;
            }
        }

        return response()->json($events);
    }
}

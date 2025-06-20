<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DefaultShiftPattern;
use App\Models\WorkShift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Holiday;
use App\Models\TransportationExpense;

class ShiftController extends Controller
{
    // デフォルトシフトパターンの編集画面
    public function editDefault()
    {
        $user = Auth::user();
        $patterns = DefaultShiftPattern::where('user_id', $user->id)
            ->get()
            ->keyBy('day_of_week');

        $days = ['日', '月', '火', '水', '木', '金', '土'];

        return view('shifts.default_edit', compact('patterns', 'days'));
    }

    // デフォルトシフトパターンの更新処理
    public function updateDefault(Request $request)
    {
        $user = Auth::user();
        $validatedTransportation = $request->validate([
            'default_transportation_departure' => 'nullable|string|max:255',
            'default_transportation_destination' => 'nullable|string|max:255',
            'default_transportation_amount' => 'nullable|integer|min:0',
        ]);

        // 空文字で送信された場合にnullをセットする
        foreach ($validatedTransportation as $key => $value) {
            if ($value === '') {
                $validatedTransportation[$key] = null;
            }
        }
        $user->update($validatedTransportation);

        $days = $request->input('days', []);

        foreach ($days as $dayOfWeek => $data) {
            DefaultShiftPattern::updateOrCreate(
                ['user_id' => $user->id, 'day_of_week' => $dayOfWeek],
                [
                    'is_workday' => isset($data['is_workday']),
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'break_minutes' => $data['break_minutes'] ?? 60,
                    'location' => $data['location'] ?? 'office', // ★追加
                ]
            );
        }

        return redirect()->route('schedule.monthly')->with('success', 'デフォルトのシフトパターンを更新しました。');
    }

    /**
     * 月間スケジュール管理ページを表示します。
     */
    public function monthlySchedule(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', now()->format('Y-m'));
        $targetMonth = Carbon::parse($month)->startOfMonth();

        // 1. デフォルトパターンを取得
        $defaultPatterns = DefaultShiftPattern::where('user_id', $user->id)->get()->keyBy('day_of_week');

        // 2. その月の個別設定（休日や時間変更）を取得
        $workShifts = WorkShift::where('user_id', $user->id)
            ->whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // 3. 対象月の祝日を取得
        $publicHolidays = Holiday::whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->get()
            ->keyBy(fn($holiday) => $holiday->date->format('Y-m-d'));

        // 交通費
        $dailyExpenses = TransportationExpense::where('user_id', $user->id)
            ->whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->get()
            ->groupBy(fn($item) => $item->date->format('Y-m-d'))
            ->mapWithKeys(function ($group, $date) {
                return [$date => $group->sum('amount')];
            });
        // 4. 月の全日付を生成し、デフォルトと個別設定をマージ
        $period = CarbonPeriod::create($targetMonth, $targetMonth->copy()->endOfMonth());
        $days = [];
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayOfWeek = $date->dayOfWeek;

            $default = $defaultPatterns[$dayOfWeek] ?? null;
            $override = $workShifts->get($dateString); // ユーザーによる個別設定
            $public_holiday = $publicHolidays->get($dateString);

            // もし祝日で、かつユーザーによる個別設定がない場合
            if ($public_holiday && !$override) {
                // ビューで表示するための「全休」データを動的に作成する
                $override = new WorkShift([
                    'type' => 'full_day_off',
                    'name' => $public_holiday->name,
                    'notes' => '祝日のため自動設定',
                ]);
            }

            $days[] = [
                'date' => $date,
                'default' => $default,
                'override' => $override, // 個別設定 or 自動設定された祝日休日
                'public_holiday' => $public_holiday,
            ];
        }

        return view('schedule.monthly', [
            'days' => $days,
            'targetMonth' => $targetMonth,
            'publicHolidays' => $publicHolidays,
            'dailyExpenses' => $dailyExpenses,
        ]);
    }

    /**
     * 特定の日のスケジュールを更新またはクリアします。
     */
    public function updateOrClearDay(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'type' => 'required|string|in:work,full_day_off,am_off,pm_off,clear,location_only',
            'name' => 'nullable|string|max:255',
            'start_time' => 'nullable|required_if:type,work|date_format:H:i',
            'end_time' => 'nullable|required_if:type,work|date_format:H:i|after:start_time',
            'location' => 'nullable|string|in:office,remote',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $date = $validated['date'];

        // 「クリア」が選択された場合はレコードを削除
        if ($validated['type'] === 'clear') {
            WorkShift::where('user_id', $user->id)->where('date', $date)->delete();
            return response()->json(['success' => true, 'message' => '設定をクリアしました。']);
        }

        // それ以外は作成または更新
        $dataToUpdate = [
            'type' => $validated['type'],
            'name' => $validated['name'] ?? null,
            'start_time' => ($validated['type'] === 'work') ? $validated['start_time'] : null,
            'end_time' => ($validated['type'] === 'work') ? $validated['end_time'] : null,
            'location' => in_array($validated['type'], ['work', 'location_only']) ? ($validated['location'] ?? 'office') : null,
            'notes' => $validated['notes'] ?? null,

        ];

        WorkShift::updateOrCreate(
            ['user_id' => $user->id, 'date' => $date],
            $dataToUpdate
        );

        return response()->json(['success' => true, 'message' => 'スケジュールを更新しました。']);
    }
}

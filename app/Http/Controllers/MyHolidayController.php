<?php

namespace App\Http\Controllers;

use App\Models\UserHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MyHolidayController extends Controller
{
    /**
     * ログインユーザー自身の休日登録ページを表示します。
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $myHolidays = UserHoliday::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('my_holidays.index', compact('myHolidays'));
    }

    /**
     * ログインユーザーの新しい休日を登録します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'period_type' => ['required', Rule::in(['full', 'am', 'pm'])],
        ], [
            'date.after_or_equal' => '本日より前の日付は登録できません。',
        ]);

        // 重複チェック
        $exists = UserHoliday::where('user_id', Auth::id())
            ->where('date', $validated['date'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'その日付には既に休日が登録されています。')->withInput();
        }

        // ログインユーザーの休日として登録
        Auth::user()->holidays()->create($validated);

        return redirect()->route('my-holidays.index')->with('success', '休日を登録しました。');
    }

    /**
     * ▼▼▼【ここからメソッド全体を追加】▼▼▼
     * 休日編集ページを表示します。
     */
    public function edit(UserHoliday $userHoliday)
    {
        // 他のユーザーの休日を編集できないように権限をチェック
        if ($userHoliday->user_id !== Auth::id()) {
            abort(403);
        }
        return view('my_holidays.edit', compact('userHoliday'));
    }

    /**
     * 休日を更新します。
     */
    public function update(Request $request, UserHoliday $userHoliday)
    {
        if ($userHoliday->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'period_type' => ['required', Rule::in(['full', 'am', 'pm'])],
        ]);

        $userHoliday->update($validated);

        return redirect()->route('my-holidays.index')->with('success', '休日を更新しました。');
    }

    /**
     * 自身の休日を削除します。
     *
     * @param  \App\Models\UserHoliday  $userHoliday
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(UserHoliday $userHoliday)
    {
        // 他のユーザーの休日を削除できないように権限をチェック
        if ($userHoliday->user_id !== Auth::id()) {
            abort(403);
        }

        $userHoliday->delete();

        return redirect()->route('my-holidays.index')->with('success', '休日を削除しました。');
    }
}

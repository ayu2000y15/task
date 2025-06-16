<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserHoliday;
use App\Models\User;
use Illuminate\Http\Request;

class UserHolidayController extends Controller
{
    /**
     * 全員の休日一覧（絞り込み機能付き）を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 絞り込みフォームのユーザー選択肢用
        $users = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();

        $query = UserHoliday::with('user');

        // 絞り込み条件の適用
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // ▼▼▼【ここから追加】ソート処理 ▼▼▼
        $sort = $request->input('sort', 'date'); // デフォルトは日付順
        $direction = $request->input('direction', 'desc'); // デフォルトは降順

        // 関連テーブルのソートに対応
        if ($sort === 'user') {
            $query->join('users', 'user_holidays.user_id', '=', 'users.id')
                ->orderBy('users.name', $direction)
                ->select('user_holidays.*'); // テーブル名が重複しないようにselectを指定
        } else {
            // 'date' or 'name'
            $query->orderBy($sort, $direction);
        }
        // ▲▲▲【ここまで追加】▲▲▲

        $holidays = $query->paginate(20)->withQueryString();

        return view('admin.holidays.index', [
            'users' => $users,
            'holidays' => $holidays,
            'filters' => $request->all(),
            'sort' => $sort,                 // ▼▼▼【追加】ソート情報をViewに渡す
            'direction' => $direction,       // ▼▼▼【追加】
        ]);
    }

    /**
     * 休日を削除します。（管理者は削除できる想定で残します）
     *
     * @param  \App\Models\UserHoliday  $userHoliday
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(UserHoliday $userHoliday)
    {
        $userHoliday->delete();

        return redirect()->route('admin.holidays.index')->with('success', '休日を削除しました。');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransportationExpense;
use App\Models\User;
use App\Models\Project;
use Carbon\Carbon;

class TransportationExpenseController extends Controller
{
    /**
     * 交通費一覧をフィルターして表示します。
     */
    public function index(Request $request)
    {
        // ここで交通費一覧を閲覧する権限をチェックすることを推奨します
        // $this->authorize('viewAny', TransportationExpense::class);

        $filters = [
            'month' => $request->input('month', now()->format('Y-m')),
            'user_id' => $request->input('user_id', ''),
            'project_id' => $request->input('project_id', ''),
        ];

        $targetMonth = Carbon::parse($filters['month']);

        $query = TransportationExpense::with(['user', 'project'])
            ->whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        } elseif ($request->input('project_id') === 'none') {
            // 「その他（案件なし）」が選択された場合
            $query->whereNull('project_id');
        }


        $expenses = $query->orderBy('date', 'desc')->get();
        $totalAmount = $expenses->sum('amount');

        // フィルター用の選択肢を取得
        $users = User::where('status', 'active')->orderBy('name')->get();
        $projects = Project::orderBy('title')->get();


        return view('admin.transportation_expenses.index', [
            'expenses' => $expenses,
            'totalAmount' => $totalAmount,
            'filters' => $filters,
            'users' => $users,
            'projects' => $projects,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cost;
use App\Models\Project;
use App\Models\TransportationExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransportationExpenseController extends Controller
{
    public function index()
    {
        $expenses = TransportationExpense::where('user_id', Auth::id())
            ->with('project')
            ->orderBy('date', 'desc')
            ->paginate(15);
        return view('transportation_expenses.index', compact('expenses'));
    }

    public function create()
    {
        // ユーザーが関わる進行中の案件を取得（ロジックは要件に合わせて調整）
        $projects = Project::where('status', 'in_progress')->orderBy('title')->get();
        return view('transportation_expenses.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
            'departure' => 'nullable|string|max:255',
            'destination' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $validated['user_id'] = $user->id;

        DB::transaction(function () use ($validated, $user) {
            $expense = TransportationExpense::create($validated);

            // 案件が選択されている場合、Costも作成
            if ($expense->project_id) {
                $cost = $expense->project->costs()->create([
                    // ★★★【ご指定のフォーマットに変更】★★★
                    'item_description' => "[ {$expense->destination} ] {$user->name}",
                    'amount' => $expense->amount,
                    'type' => '交通費',
                    'cost_date' => $expense->date,
                    'notes' => $expense->notes,
                ]);
                // 作成したコストのIDを保存
                $expense->cost_id = $cost->id;
                $expense->save();
            }
        });

        return redirect()->route('transportation-expenses.index')->with('success', '交通費を登録しました。');
    }

    /**
     * ▼▼▼【ここからメソッド全体を追加】▼▼▼
     * 交通費の編集画面を表示します。
     */
    public function edit(TransportationExpense $transportationExpense)
    {
        // 権限チェック
        if ($transportationExpense->user_id !== Auth::id()) {
            abort(403);
        }

        $projects = Project::whereIn('status', ['in_progress', 'not_started'])
            ->orWhere('id', $transportationExpense->project_id) // 現在紐付いている案件が完了済みでも選択肢に表示
            ->orderBy('title')
            ->get();

        return view('transportation_expenses.edit', [
            'expense' => $transportationExpense,
            'projects' => $projects,
        ]);
    }

    /**
     * 交通費の情報を更新します。
     */
    public function update(Request $request, TransportationExpense $transportationExpense)
    {
        // 権限チェック
        if ($transportationExpense->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
            'departure' => 'nullable|string|max:255',
            'destination' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($transportationExpense, $validated, $user) {
            // 以前のコストがあれば削除
            if ($transportationExpense->cost_id) {
                Cost::find($transportationExpense->cost_id)?->delete();
            }

            // 交通費レコードを更新
            $validated['cost_id'] = null; // cost_idを一旦リセット
            $transportationExpense->update($validated);

            // 新しく案件が選択されている場合、Costを再作成
            if ($transportationExpense->project_id) {
                // Projectモデルを再読み込みしてリレーションを正しく取得
                $project = Project::find($transportationExpense->project_id);
                $cost = $project->costs()->create([
                    // ★★★【ご指定のフォーマットに変更】★★★
                    'item_description' => "[ {$transportationExpense->departure} → {$transportationExpense->destination} ] {$user->name}",
                    'amount' => $transportationExpense->amount,
                    'type' => '交通費',
                    'cost_date' => $transportationExpense->date,
                    'notes' => $transportationExpense->notes,
                ]);
                // 作成したコストのIDを保存
                $transportationExpense->cost_id = $cost->id;
                $transportationExpense->save();
            }
        });

        return redirect()->route('transportation-expenses.index')->with('success', '交通費を更新しました。');
    }

    /**
     * 交通費を削除します。
     */
    public function destroy(TransportationExpense $transportationExpense)
    {
        // 権限チェック
        if ($transportationExpense->user_id !== Auth::id()) {
            abort(403);
        }

        DB::transaction(function () use ($transportationExpense) {
            // 関連するコストも削除
            if ($transportationExpense->cost_id) {
                Cost::find($transportationExpense->cost_id)?->delete();
            }
            $transportationExpense->delete();
        });

        return redirect()->route('transportation-expenses.index')->with('success', '交通費を削除しました。');
    }
}

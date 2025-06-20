<?php

namespace App\Http\Controllers;

use App\Models\Cost;
use App\Models\Project;
use App\Models\TransportationExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DefaultShiftPattern;
use App\Models\WorkShift;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TransportationExpenseController extends Controller
{
    public function index(Request $request)
    {
        $targetMonth = Carbon::parse($request->input('month', 'today'))->startOfMonth();

        // 指定された月の交通費を、利用日の昇順で取得
        $expenses = TransportationExpense::where('user_id', Auth::id())
            ->with('project')
            ->whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->orderBy('date', 'asc') // asc (昇順) に変更
            ->get(); // ページネーションを解除し、全件取得

        return view('transportation_expenses.index', [
            'expenses' => $expenses,
            'targetMonth' => $targetMonth, // ビューに月情報を渡す
        ]);
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

    /**
     * 指定された月の出勤日に基づいて、交通費を一括で登録します。
     */
    public function batchStore(Request $request)
    {
        $validated = $request->validate(['month' => 'required|date_format:Y-m']);
        $user = Auth::user();
        $targetMonth = Carbon::parse($validated['month']);

        // 1. デフォルト交通費が設定されているかチェック
        if (!$user->default_transportation_destination || !isset($user->default_transportation_amount)) {
            return back()->with('error', 'デフォルトの交通費（到着地と金額）が設定されていません。先にデフォルト設定画面で登録してください。');
        }

        // 2. 対象月の「出勤日（在宅以外）」リストを作成
        // (このロジックは変更ありません)
        $officeWorkDays = [];
        // ... (出勤日を特定するロジック)
        $defaultPatterns = DefaultShiftPattern::where('user_id', $user->id)->get()->keyBy('day_of_week');
        $workShifts = WorkShift::where('user_id', $user->id)
            ->whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));
        $publicHolidays = Holiday::whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->get()
            ->keyBy(fn($holiday) => $holiday->date->format('Y-m-d'));
        $period = CarbonPeriod::create($targetMonth->copy()->startOfMonth(), $targetMonth->copy()->endOfMonth());
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayOfWeek = $date->dayOfWeek;
            $override = $workShifts->get($dateString);
            $default = $defaultPatterns[$dayOfWeek] ?? null;
            $public_holiday = $publicHolidays->get($dateString);
            $isOfficeWorkDay = false;
            if ($override) {
                if (($override->location ?? 'office') !== 'remote' && $override->type !== 'full_day_off') {
                    $isOfficeWorkDay = true;
                }
            } else {
                if ($default && $default->is_workday && !$public_holiday && $default->location !== 'remote') {
                    $isOfficeWorkDay = true;
                }
            }
            if ($isOfficeWorkDay) {
                $officeWorkDays[] = $dateString;
            }
        }


        if (empty($officeWorkDays)) {
            return redirect()->route('transportation-expenses.index')->with('info', '交通費を登録する対象の出勤日がありませんでした。');
        }


        // ▼▼▼【ここからロジックを全面的に変更】▼▼▼

        // 3. 登録対象となる交通費のデータを作成
        $newExpensesData = [];
        foreach ($officeWorkDays as $date) {
            $newExpensesData[] = [
                'user_id' => $user->id,
                'date' => $date,
                'departure' => $user->default_transportation_departure,
                'destination' => $user->default_transportation_destination,
                'amount' => $user->default_transportation_amount,
                'notes' => '（往復分）シフト登録から一括登録',
                'project_id' => null,
                'cost_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 4. トランザクション内で、既存データを削除してから新規データを登録する
        DB::transaction(function () use ($user, $officeWorkDays, $newExpensesData) {
            // 4-1. 上書き対象となる既存の交通費を取得
            $expensesToDelete = TransportationExpense::where('user_id', $user->id)
                ->whereIn('date', $officeWorkDays)
                ->get();

            if ($expensesToDelete->isNotEmpty()) {
                // 4-2. 関連するコストのIDを取得して削除
                $costIdsToDelete = $expensesToDelete->pluck('cost_id')->filter();
                if ($costIdsToDelete->isNotEmpty()) {
                    Cost::whereIn('id', $costIdsToDelete)->delete();
                }

                // 4-3. 既存の交通費を削除
                TransportationExpense::whereIn('id', $expensesToDelete->pluck('id'))->delete();
            }

            // 4-4. 新しい交通費データを一括で登録
            TransportationExpense::insert($newExpensesData);
        });

        // 5. 完了メッセージを返却
        return redirect()->route('transportation-expenses.index')
            ->with('success', count($newExpensesData) . '日分の交通費を登録（上書き）しました。');
        // ▲▲▲【ここまで】▲▲▲
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Request as TaskRequest;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * 自分に割り当てられた依頼の一覧を表示します。
     */
    public function index()
    {
        $user = Auth::user();
        $commonQuery = fn($query) => $query->with(['requester', 'items.completedBy', 'assignees'])->latest();

        // 自分に割り当てられた依頼（他人から）
        $assignedRequests = $user->assignedRequests()
            ->where('requester_id', '!=', $user->id) // ★ 自分自身からの依頼は除外
            ->where($commonQuery)
            ->get();
        [$pendingAssigned, $completedAssigned] = $assignedRequests->partition(fn($req) => is_null($req->completed_at));

        // 自分が作成した依頼（他人へ）
        $createdRequests = $user->createdRequests()
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', '!=', $user->id); // ★ 担当者に自分しかいない場合は除外
            })
            ->where($commonQuery)
            ->get();
        [$pendingCreated, $completedCreated] = $createdRequests->partition(fn($req) => is_null($req->completed_at));

        // 自分用の依頼（自分で作成し、自分に割り当てたもの）
        $personalRequests = $user->createdRequests()
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', $user->id); // ★ 担当者に自分がいる
            })
            ->where($commonQuery)
            ->get();
        [$pendingPersonal, $completedPersonal] = $personalRequests->partition(fn($req) => is_null($req->completed_at));


        return view('requests.index', compact(
            'pendingAssigned',
            'completedAssigned',
            'pendingCreated',
            'completedCreated',
            'pendingPersonal',
            'completedPersonal' // ★ Viewに渡す変数を追加
        ));
    }

    /**
     * 新規依頼作成フォームを表示します。（変更なし）
     */
    public function create()
    {
        $assigneeCandidates = User::where('status', User::STATUS_ACTIVE)
            // ->where('id', '!=', Auth::id()) // この行を削除またはコメントアウト
            ->orderBy('name')
            ->get();

        return view('requests.create', compact('assigneeCandidates'));
    }

    /**
     * 新しい依頼をデータベースに保存します。（変更なし）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'assignees' => 'required|array|min:1',
            'assignees.*' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:1000',
        ]);
        try {
            DB::transaction(function () use ($validated) {
                $taskRequest = TaskRequest::create(['requester_id' => Auth::id(), 'title' => $validated['title'], 'notes' => $validated['notes']]);
                $taskRequest->assignees()->sync($validated['assignees']);
                foreach ($validated['items'] as $index => $content) {
                    $taskRequest->items()->create(['content' => $content, 'order' => $index + 1]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '依頼の作成に失敗しました。' . $e->getMessage())->withInput();
        }
        return redirect()->route('requests.index')->with('success', '作業依頼を作成しました。');
    }

    /**
     * 依頼のチェックリスト項目を更新（完了/未完了）します。
     */
    public function updateItem(Request $request, RequestItem $item)
    {
        // ▼▼▼【ここを実装】▼▼▼
        // 依頼の担当者でなければ操作させない
        $this->authorize('update', $item);

        $validated = $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        DB::transaction(function () use ($item, $validated) {
            $item->update([
                'is_completed' => $validated['is_completed'],
                'completed_at' => $validated['is_completed'] ? now() : null,
                'completed_by' => $validated['is_completed'] ? Auth::id() : null,
            ]);

            // 親依頼の全項目が完了したかチェック
            $parentRequest = $item->request;
            if ($parentRequest->items()->where('is_completed', false)->doesntExist()) {
                // 全て完了していたら、親依頼の完了日時を更新
                $parentRequest->update(['completed_at' => now()]);
            } else {
                // 1つでも未完了があれば、親依頼の完了日時をクリア
                $parentRequest->update(['completed_at' => null]);
            }
        });

        return response()->json(['success' => true, 'item' => $item->fresh('completedBy')]);
    }

    /**
     * 項目を「今日のやること」リストへ追加/削除します。
     */
    public function setMyDay(Request $request, RequestItem $item)
    {
        // 担当者でなければ操作不可
        $this->authorize('update', $item);

        $validated = $request->validate([
            // dateはnullableなので、空で送られてきたら解除、日付があれば設定
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $item->update([
            'my_day_date' => $validated['date'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => '計画日を設定しました。'
        ]);
    }

    /**
     * 依頼編集フォームを表示します。
     */
    public function edit(TaskRequest $request)
    {
        $this->authorize('update', $request);

        $assigneeCandidates = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();

        // 選択済みの担当者IDの配列を取得
        $selectedAssignees = $request->assignees->pluck('id')->all();

        return view('requests.edit', compact('request', 'assigneeCandidates', 'selectedAssignees'));
    }

    /**
     * 依頼を更新します。
     * @param \Illuminate\Http\Request $httpRequest
     * @param \App\Models\Request $request // ★ 変数名を $taskRequest から $request に変更
     */
    public function update(Request $httpRequest, TaskRequest $request)
    {
        $this->authorize('update', $request); // ★ $taskRequest を $request に変更

        $validated = $httpRequest->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'assignees' => 'required|array|min:1',
            'assignees.*' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) { // ★ $taskRequest を $request に変更
                // 1. 依頼の親レコードを更新
                $request->update([
                    'title' => $validated['title'],
                    'notes' => $validated['notes'],
                ]);

                // 2. 担当者を更新
                $request->assignees()->sync($validated['assignees']);

                // 3. チェックリスト項目を更新
                $request->items()->delete();
                foreach ($validated['items'] as $index => $content) {
                    $request->items()->create([
                        'content' => $content,
                        'order' => $index + 1,
                    ]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '依頼の更新に失敗しました。' . $e->getMessage())->withInput();
        }

        return redirect()->route('requests.index')->with('success', '作業依頼を更新しました。');
    }

    /**
     * 指定された依頼を削除します。
     * @param \App\Models\Request $request // ★ 変数名を $taskRequest から $request に変更
     */
    public function destroy(TaskRequest $request)
    {
        $this->authorize('delete', $request); // ★ $taskRequest を $request に変更

        $request->delete(); // ★ $taskRequest を $request に変更

        return redirect()->route('requests.index')->with('success', '依頼を削除しました。');
    }
}

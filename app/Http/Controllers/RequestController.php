<?php

namespace App\Http\Controllers;

use App\Models\Request as TaskRequest;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Project;
use App\Models\RequestCategory;


class RequestController extends Controller
{
    /**
     * 自分に割り当てられた依頼の一覧を表示します。
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $filterCategoryId = $request->input('category_id');
        $filterDate = $request->input('date');

        // クエリ共通部分
        $commonQuery = function ($query) use ($filterCategoryId, $filterDate) {
            $query->with(['requester', 'items.completedBy', 'assignees', 'project', 'category'])
                ->latest();

            if ($filterCategoryId) {
                $query->where('request_category_id', $filterCategoryId);
            }

            if ($filterDate) {
                $query->where(function ($q) use ($filterDate) {
                    $q->whereHas('items', function ($itemQuery) use ($filterDate) {
                        $itemQuery->where(function ($subQ) use ($filterDate) {
                            $subQ->whereDate('start_at', '<=', $filterDate)
                                ->whereDate('end_at', '>=', $filterDate);
                        });
                    })
                        ->orWhere(function ($subQ) use ($filterDate) {
                            $subQ->whereDate('start_at', '<=', $filterDate)
                                ->whereDate('end_at', '>=', $filterDate);
                        });
                });
            }
        };

        // ▼▼▼【ここから修正】リアルタイムで完了状態を判定するクロージャを定義 ▼▼▼
        $isComplete = function (TaskRequest $req) {
            // 1. DB上で既に完了になっている場合
            if (!is_null($req->completed_at)) {
                return true;
            }

            // 2. チェックリストがなく、終了日時を過ぎている場合 (リアルタイム判定)
            if ($req->items->isEmpty() && !is_null($req->end_at) && $req->end_at->isPast()) {
                return true;
            }

            // 上記以外は未完了
            return false;
        };
        // ▲▲▲【修正ここまで】▲▲▲

        // 自分に割り当てられた依頼（他人から）
        $assignedRequests = $user->assignedRequests()
            ->where('requester_id', '!=', $user->id)
            ->where($commonQuery)
            ->get();
        // ▼▼▼【修正】新しい判定ロジックで振り分ける ▼▼▼
        [$completedAssigned, $pendingAssigned] = $assignedRequests->partition($isComplete);

        // 自分が作成した依頼（他人へ）
        $createdRequests = $user->createdRequests()
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', '!=', $user->id);
            })
            ->where($commonQuery)
            ->get();
        // ▼▼▼【修正】新しい判定ロジックで振り分ける ▼▼▼
        [$completedCreated, $pendingCreated] = $createdRequests->partition($isComplete);

        // 自分用の依頼
        $personalRequests = $user->createdRequests()
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->where($commonQuery)
            ->get();
        // ▼▼▼【修正】新しい判定ロジックで振り分ける ▼▼▼
        [$completedPersonal, $pendingPersonal] = $personalRequests->partition($isComplete);


        // フィルター用のカテゴリ一覧
        $categories = \App\Models\RequestCategory::orderBy('name')->get();

        return view('requests.index', compact(
            'pendingAssigned',
            'completedAssigned',
            'pendingCreated',
            'completedCreated',
            'pendingPersonal',
            'completedPersonal',
            'categories' // フィルター用
        ));
    }

    /**
     * 新規依頼作成フォームを表示します。（変更なし）
     */
    public function create()
    {
        $assigneeCandidates = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();
        $projects = Project::orderBy('title')->get(); // ★ 追加
        $categories = RequestCategory::orderBy('name')->get(); // ★ 追加

        return view('requests.create', compact('assigneeCandidates', 'projects', 'categories'));
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
            'project_id' => 'nullable|exists:projects,id',
            'request_category_id' => 'required|exists:request_categories,id',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'items' => 'nullable|array|max:15', // 必須(min:1)を解除
            'items.*.content' => 'required_with:items|string|max:1000', // itemsが存在する場合のみ必須
            'items.*.due_date' => 'nullable|date',
        ]);
        try {
            DB::transaction(function () use ($validated, $request) {
                $taskRequest = TaskRequest::create([
                    'requester_id' => Auth::id(),
                    'title' => $validated['title'],
                    'notes' => $validated['notes'],
                    'project_id' => $validated['project_id'],
                    'request_category_id' => $validated['request_category_id'],
                    'start_at' => $validated['start_at'] ?? null,
                    'end_at' => $validated['end_at'] ?? null,
                ]);

                $taskRequest->assignees()->sync($validated['assignees']);
                if (!empty($validated['items'])) {
                    foreach ($validated['items'] as $index => $itemData) {
                        $taskRequest->items()->create([
                            'content' => $itemData['content'],
                            'due_date' => $itemData['due_date'] ?? null,
                            'order' => $index + 1
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '依頼の作成に失敗しました。' . $e->getMessage())->withInput();
        }
        return redirect()->route('requests.index')->with('success', '予定・依頼を作成しました。');
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
     * 依頼項目の終了予定日時を更新します。
     */
    public function updateItemDueDate(Request $request, RequestItem $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'due_date' => 'nullable|date',
        ]);

        $item->update([
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => '終了予定日時を更新しました。']);
    }

    /**
     * 依頼編集フォームを表示します。
     */
    public function edit(TaskRequest $request)
    {
        $this->authorize('update', $request);

        $assigneeCandidates = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();
        $selectedAssignees = $request->assignees->pluck('id')->all();
        $projects = Project::orderBy('title')->get();
        $categories = RequestCategory::orderBy('name')->get();

        return view('requests.edit', compact('request', 'assigneeCandidates', 'selectedAssignees', 'projects', 'categories'));
    }

    /**
     * 依頼を更新します。
     * @param \Illuminate\Http\Request $httpRequest
     * @param \App\Models\Request $request // ★ 変数名を $taskRequest から $request に変更
     */
    public function update(Request $httpRequest, TaskRequest $request)
    {
        $this->authorize('update', $request);

        // このリクエストに属するアイテムIDであることを確認するバリデーションルール
        $itemExistsRule = Rule::exists('request_items', 'id')->where(function ($query) use ($request) {
            return $query->where('request_id', $request->id);
        });

        $validated = $httpRequest->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'assignees' => 'required|array|min:1',
            'assignees.*' => 'required|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'request_category_id' => 'required|exists:request_categories,id',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'items' => 'nullable|array|max:15', // 必須(min:1)を解除
            'items.*.id' => ['nullable', 'integer', $itemExistsRule],
            'items.*.content' => 'required_with:items|string|max:1000', // itemsが存在する場合のみ必須
            'items.*.due_date' => 'nullable|date',
        ], [
            'title.required' => '件名は必ず入力してください。',
            'assignees.required' => '担当者は必ず選択してください。',
            'items.required' => 'チェックリスト項目は最低1つ必要です。',
            'items.*.content.required' => 'チェックリストの項目内容は必ず入力してください。',
            'items.*.id.exists' => '指定された項目は、この依頼に属していません。',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // 1. 依頼の親レコードを更新
                $request->update([
                    'title' => $validated['title'],
                    'notes' => $validated['notes'],
                    'project_id' => $validated['project_id'],
                    'request_category_id' => $validated['request_category_id'],
                    'start_at' => $validated['start_at'] ?? null,
                    'end_at' => $validated['end_at'] ?? null,
                ]);

                // 2. 担当者を更新
                $request->assignees()->sync($validated['assignees']);

                // 3. チェックリスト項目を差分更新
                $submittedItemIds = [];

                if (!empty($validated['items'])) {
                    foreach ($validated['items'] as $index => $itemData) {
                        $item = $request->items()->updateOrCreate(
                            ['id' => $itemData['id'] ?? null],
                            [
                                'content' => $itemData['content'],
                                'order' => $index + 1,
                                'due_date' => $itemData['due_date'] ?? null,
                            ]
                        );
                        $submittedItemIds[] = $item->id;
                    }
                }

                // フォームから送信されなかったアイテム（=削除されたアイテム）をDBから削除
                $request->items()->whereNotIn('id', $submittedItemIds)->delete();

                // 削除や更新の結果、全項目が完了になったか再チェック
                if ($request->items()->where('is_completed', false)->doesntExist() && $request->items()->count() > 0) {
                    $request->update(['completed_at' => now()]);
                } else {
                    $request->update(['completed_at' => null]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '依頼の更新に失敗しました。' . $e->getMessage())->withInput();
        }

        return redirect()->route('requests.index')->with('success', '予定・依頼を更新しました。');
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

    /**
     * 依頼項目の開始日時を更新します。
     */
    public function updateItemStartAt(Request $request, RequestItem $item)
    {
        $this->authorize('update', $item);

        $parentRequest = $item->request;
        $rules = [
            'start_at' => [
                'nullable',
                'date',
                // 項目の終了日時より前であること
                $item->end_at ? 'before_or_equal:' . $item->end_at->toDateTimeString() : '',
                // 親依頼の開始日時以降であること
                $parentRequest->start_at ? 'after_or_equal:' . $parentRequest->start_at->toDateTimeString() : '',
                // 親依頼の終了日時より前であること
                $parentRequest->end_at ? 'before_or_equal:' . $parentRequest->end_at->toDateTimeString() : '',
            ],
        ];

        $validated = $request->validate($rules, [
            'start_at.after_or_equal' => '開始日時は、依頼全体の開始日時より後に設定してください。',
            'start_at.before_or_equal' => '開始日時は、依頼全体の終了日時、または項目の終了日時より前に設定してください。',
        ]);

        $item->update(['start_at' => $validated['start_at'] ?? null]);
        return response()->json(['success' => true]);
    }

    /**
     * 依頼項目の終了日時を更新します。
     */
    public function updateItemEndAt(Request $request, RequestItem $item)
    {
        $this->authorize('update', $item);

        $parentRequest = $item->request;
        $rules = [
            'end_at' => [
                'nullable',
                'date',
                // 項目の開始日時以降であること
                $item->start_at ? 'after_or_equal:' . $item->start_at->toDateTimeString() : '',
                // 親依頼の開始日時以降であること
                $parentRequest->start_at ? 'after_or_equal:' . $parentRequest->start_at->toDateTimeString() : '',
                // 親依頼の終了日時より前であること
                $parentRequest->end_at ? 'before_or_equal:' . $parentRequest->end_at->toDateTimeString() : '',
            ],
        ];

        $validated = $request->validate($rules, [
            'end_at.after_or_equal' => '終了日時は、依頼全体の開始日時、または項目の開始日時より後に設定してください。',
            'end_at.before_or_equal' => '終了日時は、依頼全体の終了日時より前に設定してください。',
        ]);

        $item->update(['end_at' => $validated['end_at'] ?? null]);
        return response()->json(['success' => true]);
    }

    /**
     * 依頼のチェックリスト項目の並び順を更新します。
     */
    public function updateItemOrder(Request $httpRequest, \App\Models\Request $request)
    {
        $this->authorize('update', $request);
        $validated = $httpRequest->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'required|string|exists:request_items,id',
        ]);
        try {
            DB::transaction(function () use ($validated, $request) {
                foreach ($validated['item_ids'] as $index => $itemId) {
                    $request->items()->where('id', $itemId)->update(['order' => $index + 1]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '予定・依頼の並び順の更新中にエラーが発生しました。'], 500);
        }
        return response()->json(['success' => true, 'message' => '予定・依頼の並び順を更新しました。']);
    }
}

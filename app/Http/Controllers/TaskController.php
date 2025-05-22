<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * タスク一覧を表示
     */
    public function index(Request $request)
    {
        // フィルターの初期化（due_dateを追加）
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'assignee' => $request->input('assignee', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
            'due_date' => $request->input('due_date', ''), // この行を追加
        ];

        // 以下は既存のコード
        $query = Task::query()->with('project');

        // フィルター適用
        if ($filters['project_id']) {
            $query->where('project_id', $filters['project_id']);
        }

        if ($filters['assignee']) {
            $query->where('assignee', $filters['assignee']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search']) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // 期限フィルターの追加
        if ($filters['due_date']) {
            $now = Carbon::now()->startOfDay();

            switch ($filters['due_date']) {
                case 'overdue':
                    $query->where('end_date', '<', $now)
                        ->whereNotIn('status', ['completed', 'cancelled']);
                    break;
                case 'today':
                    $query->whereDate('end_date', $now);
                    break;
                case 'tomorrow':
                    $query->whereDate('end_date', $now->copy()->addDay());
                    break;
                case 'this_week':
                    $query->whereBetween('end_date', [
                        $now->copy()->addDay(),
                        $now->copy()->endOfWeek()
                    ]);
                    break;
                case 'next_week':
                    $query->whereBetween('end_date', [
                        $now->copy()->startOfWeek()->addWeek(),
                        $now->copy()->endOfWeek()->addWeek()
                    ]);
                    break;
            }
        }

        $tasks = $query->orderBy('end_date', 'asc')->get();

        // 残りのコードは変更なし
        $projects = Project::all();
        $assignees = Task::distinct('assignee')->whereNotNull('assignee')->pluck('assignee');
        $statusOptions = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留中',
            'cancelled' => 'キャンセル',
        ];

        return view('tasks.index', compact('tasks', 'projects', 'assignees', 'statusOptions', 'filters'));
    }

    /**
     * 新規タスク作成フォームを表示
     */
    public function create(Request $request, Project $project)
    {
        $parentTask = null;

        if ($request->has('parent')) {
            $parentTask = Task::findOrFail($request->parent);
        }

        return view('tasks.create', compact('project', 'parentTask'));
    }

    /**
     * 新規タスクを保存
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'duration' => 'nullable|integer|min:1',
            'is_milestone_or_folder' => 'required|in:milestone,folder,task',
            'color' => 'nullable|string|max:7',
        ]);

        // 工数が指定されている場合は、開始日から工数分の日数を加算して終了日を計算
        if (!empty($validated['duration'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $startDate->copy()->addDays($validated['duration'] - 1); // 開始日を含むため-1
            $validated['end_date'] = $endDate->format('Y-m-d');
        } else {
            // 開始日と終了日からタスクの期間（日数）を計算
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $validated['duration'] = $endDate->diffInDays($startDate) + 1; // 開始日を含むため+1
        }

        $task = new Task([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration' => $validated['duration'],
            'assignee' => $validated['assignee'] ?? null,
            'parent_id' => $request->input('parent_id'), // 修正: $validated['parent_id'] → $request->input('parent_id')
            'is_milestone' => $request->input('is_milestone_or_folder') === 'milestone',
            'is_folder' => $request->input('is_milestone_or_folder') === 'folder',
            'color' => $validated['color'] ?? '#007bff',
            'progress' => 0,
            'status' => 'not_started',
        ]);

        $project->tasks()->save($task);

        return redirect()->route('gantt.index', ['project_id' => $project->id])
            ->with('success', 'タスクが作成されました。');
    }

    /**
     * タスク編集フォームを表示
     */
    public function edit(Project $project, Task $task)
    {
        return view('tasks.edit', compact('project', 'task'));
    }

    /**
     * タスクを更新
     */
    public function update(Request $request, Project $project, Task $task)
    {
        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => 'required|in:not_started,in_progress,completed,on_hold,cancelled',
            'color' => 'nullable|string|max:20',
            'is_milestone_or_folder' => 'required|in:milestone,folder,task',
        ]);

        // チェックボックスの値を正しく処理
        $task->is_milestone = $request->input('is_milestone_or_folder') === 'milestone';
        $task->is_folder = $request->input('is_milestone_or_folder') === 'folder';

        // その他のフィールドを更新
        $task->name = $validated['name'];
        $task->description = $validated['description'];
        $task->start_date = $validated['start_date'];
        $task->end_date = $validated['end_date'];
        $task->duration = $validated['duration'];
        $task->assignee = $validated['assignee'];
        $task->parent_id = $validated['parent_id'];
        $task->status = $validated['status'];
        $task->color = $validated['color'];

        $task->save();

        return redirect()->route('projects.tasks.edit', [$project, $task])
            ->with('success', 'タスクが更新されました。');
    }
    /**
     * タスクを削除
     */
    public function destroy(Project $project, Task $task)
    {
        // 子タスクも削除
        $this->deleteTaskAndChildren($task);

        return redirect()->route('gantt.index', ['project_id' => $project->id])
            ->with('success', 'タスクが削除されました。');
    }

    /**
     * タスクとその子タスクを再帰的に削除
     */
    private function deleteTaskAndChildren(Task $task)
    {
        foreach ($task->children as $child) {
            $this->deleteTaskAndChildren($child);
        }

        $task->delete();
    }

    /**
     * タスクの進捗とステータスを更新
     */
    public function updateProgress(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'status' => 'required|string|in:not_started,in_progress,completed,on_hold,cancelled',
        ]);

        $task->update([
            'progress' => $validated['progress'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'タスクの進捗が更新されました。'
        ]);
    }

    /**
     * タスクの位置（日付）を更新
     */
    public function updatePosition(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $task = Task::findOrFail($validated['task_id']);

        // 開始日と終了日からタスクの期間（日数）を計算
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $duration = $endDate->diffInDays($startDate) + 1;

        $task->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration' => $duration,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'タスクの位置が更新されました。'
        ]);
    }

    /**
     * タスクの親タスクを更新
     */
    public function updateParent(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);

        $task = Task::findOrFail($validated['task_id']);

        // 自分自身を親にはできない
        if ($validated['parent_id'] == $task->id) {
            return response()->json([
                'success' => false,
                'message' => '自分自身を親タスクにはできません。'
            ], 422);
        }

        // 循環参照のチェック
        if ($validated['parent_id'] && $this->wouldCreateCycle($task->id, $validated['parent_id'])) {
            return response()->json([
                'success' => false,
                'message' => '循環参照が発生するため、この操作はできません。'
            ], 422);
        }

        $task->update([
            'parent_id' => $validated['parent_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'タスクの親子関係が更新されました。'
        ]);
    }

    /**
     * 循環参照が発生するかチェック
     */
    private function wouldCreateCycle($taskId, $newParentId)
    {
        $currentParentId = $newParentId;

        while ($currentParentId) {
            if ($currentParentId == $taskId) {
                return true;
            }

            $parent = Task::find($currentParentId);
            if (!$parent) {
                break;
            }

            $currentParentId = $parent->parent_id;
        }

        return false;
    }

    /**
     * 担当者を更新する
     */
    public function updateAssignee(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'assignee' => 'nullable|string|max:255',
        ], [
            'assignee.max' => '担当者名は255文字以内で入力してください。',
        ]);

        $task->assignee = $validated['assignee'];
        $task->save();

        return response()->json([
            'success' => true,
            'message' => '担当者が更新されました。'
        ]);
    }
}

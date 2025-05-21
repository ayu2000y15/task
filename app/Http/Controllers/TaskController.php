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
        $filters = [
            'project_id' => $request->input('project_id'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $tasksQuery = Task::query();

        if (!empty($filters['project_id'])) {
            $tasksQuery->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['assignee'])) {
            $tasksQuery->where('assignee', $filters['assignee']);
        }

        if (!empty($filters['status'])) {
            $tasksQuery->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $tasksQuery->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $tasks = $tasksQuery->orderBy('start_date')->get();

        // プロジェクト一覧を取得
        $projects = Project::orderBy('title')->get();

        // 担当者一覧を取得
        $assignees = Task::whereNotNull('assignee')
            ->distinct()
            ->pluck('assignee')
            ->sort()
            ->values();

        // ステータスオプション
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
            'is_milestone' => 'boolean',
            'is_folder' => 'boolean',
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
            'is_milestone' => $request->has('is_milestone'),
            'is_folder' => $request->has('is_folder'),
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'duration' => 'nullable|integer|min:1',
            'is_milestone' => 'boolean',
            'is_folder' => 'boolean',
            'progress' => 'required|integer|min:0|max:100',
            'status' => 'required|string|in:not_started,in_progress,completed,on_hold,cancelled',
            'color' => 'nullable|string|max:7',
        ]);

        // 工数が変更された場合は、開始日から工数分の日数を加算して終了日を再計算
        if (!empty($validated['duration']) && $validated['duration'] != $task->duration) {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $startDate->copy()->addDays($validated['duration'] - 1); // 開始日を含むため-1
            $validated['end_date'] = $endDate->format('Y-m-d');
        } else {
            // 開始日と終了日からタスクの期間（日数）を計算
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $validated['duration'] = $endDate->diffInDays($startDate) + 1; // 開始日を含むため+1
        }

        $task->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration' => $validated['duration'],
            'assignee' => $validated['assignee'] ?? null,
            'parent_id' => $request->input('parent_id'), // 修正: $validated['parent_id'] → $request->input('parent_id')
            'is_milestone' => $request->has('is_milestone'),
            'is_folder' => $request->has('is_folder'),
            'progress' => $validated['progress'],
            'status' => $validated['status'],
            'color' => $validated['color'] ?? $task->color,
        ]);

        return redirect()->route('gantt.index', ['project_id' => $project->id])
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
}

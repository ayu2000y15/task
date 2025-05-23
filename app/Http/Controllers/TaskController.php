<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\TaskFile;
use Carbon\Carbon;
use App\Services\TaskService;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * タスク一覧を表示
     */
    public function index(Request $request, TaskService $taskService)
    {
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'assignee' => $request->input('assignee', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
            'due_date' => $request->input('due_date', ''),
        ];

        $query = $taskService->buildFilteredQuery($filters)->with(['project', 'files', 'children']);
        $tasks = $query->orderBy('end_date', 'asc')->get();

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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'duration' => 'nullable|integer|min:1',
            'is_milestone_or_folder' => 'required|in:milestone,folder,task',
        ]);

        $isFolder = $request->input('is_milestone_or_folder') === 'folder';
        $isMilestone = $request->input('is_milestone_or_folder') === 'milestone';

        $taskData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'assignee' => $validated['assignee'] ?? null,
            'parent_id' => $request->input('parent_id'),
            'is_milestone' => $isMilestone,
            'is_folder' => $isFolder,
            'progress' => 0,
        ];

        if ($isFolder) {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['status'] = null;
            $taskData['progress'] = null;
        } elseif ($isMilestone) {
            $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
            if ($startDate) {
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $startDate->copy();
            } else {
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
            }
            $taskData['duration'] = 1;
            $taskData['status'] = $request->input('status', 'not_started');
        } else {
            if (isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                if (!empty($validated['duration'])) {
                    $endDate = $startDate->copy()->addDays($validated['duration'] - 1);
                } elseif (isset($validated['end_date'])) {
                    $endDate = Carbon::parse($validated['end_date']);
                } else {
                    $endDate = $startDate->copy();
                }
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $endDate;
                $taskData['duration'] = $endDate->diffInDays($startDate) + 1;
            } else {
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
                $taskData['duration'] = null;
            }
            $taskData['status'] = $request->input('status', 'not_started');
        }

        $task = new Task($taskData);
        $project->tasks()->save($task);

        return redirect()->route('tasks.index', ['project_id' => $project->id])->with('success', 'タスクが作成されました。');
    }

    /**
     * タスク編集フォームを表示
     */
    public function edit(Project $project, Task $task)
    {
        $files = $task->is_folder ? $task->files()->orderBy('original_name')->get() : collect();
        return view('tasks.edit', compact('project', 'task', 'files'));
    }

    /**
     * タスクを更新
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'duration' => 'nullable|integer|min:1',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => 'nullable|in:not_started,in_progress,completed,on_hold,cancelled',
        ]);

        $isFolder = $task->is_folder;
        $isMilestone = $task->is_milestone;

        $taskData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'assignee' => $validated['assignee'],
            'parent_id' => $validated['parent_id'],
            'is_milestone' => $isMilestone,
            'is_folder' => $isFolder,
        ];

        if ($isFolder) {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['status'] = null;
            $taskData['progress'] = null;
        } elseif ($isMilestone) {
            if (isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $startDate->copy();
            }
            $taskData['duration'] = 1;
            $taskData['status'] = $validated['status'] ?? $task->status;
        } else {
            if (isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                if (!empty($validated['duration'])) {
                    $endDate = $startDate->copy()->addDays($validated['duration'] - 1);
                } elseif (isset($validated['end_date'])) {
                    $endDate = Carbon::parse($validated['end_date']);
                } else {
                    $endDate = $startDate->copy();
                }
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $endDate;
                $taskData['duration'] = $endDate->diffInDays($startDate) + 1;
            }
            $taskData['status'] = $validated['status'] ?? $task->status;
        }

        $task->fill($taskData);
        $task->save();

        return redirect()->route('tasks.index', ['project_id' => $task->project_id])->with('success', 'タスクが更新されました。');
    }

    /**
     * タスクを削除
     */
    public function destroy(Project $project, Task $task)
    {
        $this->deleteTaskAndChildren($task);
        return redirect()->route('tasks.index', ['project_id' => $project->id])->with('success', 'タスクが削除されました。');
    }

    /**
     * タスクとその子タスクを再帰的に削除
     */
    private function deleteTaskAndChildren(Task $task)
    {
        if ($task->is_folder) {
            Storage::disk('local')->deleteDirectory('task_files/' . $task->id);
        }
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

        if ($validated['parent_id'] == $task->id) {
            return response()->json([
                'success' => false,
                'message' => '自分自身を親タスクにはできません。'
            ], 422);
        }

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

    /**
     * ファイルをアップロードする
     */
    public function uploadFiles(Request $request, Project $project, Task $task)
    {
        if (!$task->is_folder) {
            return response()->json(['error' => 'ファイルはフォルダにのみアップロードできます。'], 422);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = 'task_files/' . $task->id;
        $storedName = $file->hashName();

        Storage::disk('local')->putFileAs($path, $file, $storedName);

        $taskFile = $task->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path . '/' . $storedName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json($taskFile, 201);
    }

    /**
     * ファイル一覧を取得する
     */
    public function getFiles(Project $project, Task $task)
    {
        $files = $task->files()->orderBy('original_name')->get();
        return view('tasks.partials.file-list', compact('files'))->render();
    }

    /**
     * ファイルをダウンロードする
     */
    public function downloadFile(Project $project, Task $task, TaskFile $file)
    {
        if ($file->task_id !== $task->id) {
            abort(404);
        }

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    /**
     * ファイルを削除する
     */
    public function deleteFile(Project $project, Task $task, TaskFile $file)
    {
        if ($file->task_id !== $task->id) {
            abort(404);
        }

        Storage::disk('local')->delete($file->path);
        $file->delete();

        return response()->json(['success' => true, 'message' => 'ファイルを削除しました。']);
    }
}

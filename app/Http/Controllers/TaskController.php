<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\TaskFile;
use Carbon\Carbon;
use App\Services\TaskService;
use App\Models\ProcessTemplate;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * 工程一覧を表示
     */
    public function index(Request $request, TaskService $taskService)
    {
        $this->authorize('viewAny', Task::class);

        $filters = [
            'project_id' => $request->input('project_id', ''),
            'character_id' => $request->input('character_id', ''),
            'assignee' => $request->input('assignee', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
            'due_date' => $request->input('due_date', ''),
        ];

        $query = $taskService->buildFilteredQuery($filters)->with(['project', 'files', 'children', 'character']);
        $tasks = $query->orderByRaw('ISNULL(tasks.start_date), tasks.start_date ASC, tasks.name ASC')->get();

        $allProjects = Project::orderBy('title')->get();

        $charactersForFilter = collect();
        if (!empty($filters['project_id'])) {
            $projectWithChars = Project::find($filters['project_id']);
            if ($projectWithChars) {
                $charactersForFilter = $projectWithChars->characters()->orderBy('name')->get();
            }
        }
        $assignees = Task::distinct('assignee')->whereNotNull('assignee')->orderBy('assignee')->pluck('assignee');
        $statusOptions = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留中',
            'cancelled' => 'キャンセル',
        ];

        return view('tasks.index', compact(
            'tasks',
            'allProjects',
            'charactersForFilter',
            'assignees',
            'statusOptions',
            'filters'
        ));
    }

    /**
     * 新規工程作成フォームを表示
     */
    public function create(Request $request, Project $project)
    {
        $this->authorize('create', Task::class);
        $processTemplates = ProcessTemplate::orderBy('name')->get();
        $parentTask = null;
        $project->load('characters');

        if ($request->has('parent')) {
            $parentTask = Task::findOrFail($request->parent);
        }

        return view('tasks.create', compact('project', 'parentTask', 'processTemplates'));
    }

    /**
     * 新規工程を保存
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'character_id' => 'nullable|exists:characters,id',
            'start_date' => 'required_if:is_milestone_or_folder,milestone|required_if:is_milestone_or_folder,task|nullable|date',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'duration' => 'nullable|integer|min:1|required_if:is_milestone_or_folder,task|prohibited_if:is_milestone_or_folder,milestone',
            'is_milestone_or_folder' => 'required|in:milestone,folder,task,todo_task', // todo_task を追加
            'status' => 'required_unless:is_milestone_or_folder,folder|nullable|in:not_started,in_progress,completed,on_hold,cancelled',
            'end_date' => 'nullable|date|after_or_equal:start_date|prohibited_if:is_milestone_or_folder,milestone', // バリデーションとしては残すが、基本的には自動計算 or null
        ]);

        $isFolder = $request->input('is_milestone_or_folder') === 'folder';
        $isMilestone = $request->input('is_milestone_or_folder') === 'milestone';
        $isTodoTask = $request->input('is_milestone_or_folder') === 'todo_task';

        $taskData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'character_id' => (array_key_exists('character_id', $validated) && $validated['character_id'] !== '') ? $validated['character_id'] : null,
            'assignee' => $validated['assignee'] ?? null,
            'parent_id' => $request->input('parent_id'),
            'is_milestone' => $isMilestone, // マイルストーンフラグを設定
            'is_folder' => $isFolder,   // フォルダフラグを設定
            'progress' => 0,
        ];

        if ($isFolder) {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['progress'] = null;
        } elseif ($isMilestone) {
            $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
            if ($startDate) {
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $startDate->copy();
            } else {
                // バリデーションでstart_dateは必須のはずだが念のため
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
            }
            $taskData['duration'] = 1;
            $taskData['status'] = $validated['status'] ?? 'not_started';
        } elseif ($isTodoTask) { // 工程（期限なし）
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['is_milestone'] = false; // 明示的にfalse
            $taskData['is_folder'] = false;    // 明示的にfalse
            $taskData['status'] = $validated['status'] ?? 'not_started';
        } else { // 通常工程 (is_milestone_or_folder === 'task') - 日付あり
            // 'task' が選択された場合、start_date と duration はバリデーションで必須になっている
            $startDate = Carbon::parse($validated['start_date']);
            $duration = $validated['duration'];
            $endDate = $startDate->copy()->addDays($duration - 1);

            $taskData['start_date'] = $startDate;
            $taskData['end_date'] = $endDate;
            $taskData['duration'] = $duration;
            $taskData['status'] = $validated['status'] ?? 'not_started';
        }
        // フォルダの場合、ステータスは常にnull
        if ($isFolder) {
            $taskData['status'] = null;
        }


        $task = new Task($taskData);
        $project->tasks()->save($task);

        return redirect()->route('gantt.index', ['project_id' => $project->id])->with('success', '工程が作成されました。');
    }

    /**
     * 工程編集フォームを表示
     */
    public function edit(Project $project, Task $task)
    {
        $this->authorize('update', $task);
        $project->load('characters');

        $files = $task->is_folder ? $task->files()->orderBy('original_name')->get() : collect();
        return view('tasks.edit', compact('project', 'task', 'files'));
    }

    /**
     * 工程を更新
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'character_id' => 'nullable|exists:characters,id',
            'start_date' => 'required_if:is_milestone_or_folder,milestone|nullable|date', // マイルストーンの場合は必須
            'duration' => 'nullable|integer|min:1|required_with:start_date|prohibited_if:is_milestone_or_folder,milestone', // 開始日があれば工数も必須(マイルストーン除く)
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => 'required_unless:is_milestone_or_folder,folder|nullable|in:not_started,in_progress,completed,on_hold,cancelled',
            'end_date' => 'nullable|date|after_or_equal:start_date|prohibited_if:is_milestone_or_folder,milestone', // 更新時は直接指定されることは少ないが念のため
        ]);

        $isFolder = $task->is_folder; // 編集画面ではタスクタイプは変更不可とする想定
        $isMilestone = $task->is_milestone;

        $taskData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'character_id' => (array_key_exists('character_id', $validated) && $validated['character_id'] !== '') ? $validated['character_id'] : null,
            'assignee' => $validated['assignee'],
            'parent_id' => $validated['parent_id'],
        ];

        if ($isFolder) {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['progress'] = null; // フォルダは進捗なし
        } elseif ($isMilestone) {
            // マイルストーンは開始日のみ更新可能（バリデーションで必須）
            if ($request->filled('start_date')) {
                $startDate = Carbon::parse($validated['start_date']);
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $startDate->copy(); // 終了日も同じ日に
            }
            $taskData['duration'] = 1; // マイルストーンの工数は常に1
            $taskData['status'] = $validated['status'] ?? $task->status;
        } else { // 通常工程
            if ($request->filled('start_date') && $request->filled('duration')) {
                $startDate = Carbon::parse($validated['start_date']);
                $duration = $validated['duration']; // バリデーションで必須
                $endDate = $startDate->copy()->addDays($duration - 1);

                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $endDate;
                $taskData['duration'] = $duration;
            } elseif (!$request->filled('start_date')) { // 開始日がクリアされた場合 = 期限なしタスクへ変更
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
                $taskData['duration'] = null;
            }
            // それ以外（開始日のみ、または工数のみ）の場合は、既存の値を維持するか、エラーとするかはバリデーション次第
            // 現状のバリデーションでは、開始日があれば工数も必須なので、片方だけ入力されることはない想定
            $taskData['status'] = $validated['status'] ?? $task->status;
        }
        if ($isFolder) { // フォルダの場合はステータスは常にnull
            $taskData['status'] = null;
        }
        $task->fill($taskData);
        $task->save();

        return redirect()->route('gantt.index', ['project_id' => $task->project_id])->with('success', '工程が更新されました。');
    }

    /**
     * 工程を削除
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $task);

        $this->deleteTaskAndChildren($task);
        return redirect()->route('gantt.index', ['project_id' => $project->id])->with('success', '工程が削除されました。');
    }

    /**
     * 工程とその子工程を再帰的に削除
     */
    private function deleteTaskAndChildren(Task $task)
    {
        if ($task->is_folder) {
            foreach ($task->files as $file) {
                Storage::disk('local')->delete($file->path);
                $file->delete();
            }
        }
        foreach ($task->children as $child) {
            $this->deleteTaskAndChildren($child);
        }

        $task->delete();
    }

    /**
     * 工程の進捗とステータスを更新
     */
    public function updateProgress(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'progress' => 'sometimes|required|integer|min:0|max:100',
            'status' => 'required|string|in:not_started,in_progress,completed,on_hold,cancelled',
        ]);

        $updateData = ['status' => $validated['status']];
        if (isset($validated['progress'])) {
            $updateData['progress'] = $validated['progress'];
        } elseif ($validated['status'] === 'completed') {
            $updateData['progress'] = 100;
        } elseif (in_array($validated['status'], ['not_started', 'cancelled'])) {
            $updateData['progress'] = 0;
        }
        $task->update($updateData);

        return response()->json([
            'success' => true,
            'message' => '工程の進捗が更新されました。'
        ]);
    }

    /**
     * 工程の位置（日付）を更新 (ガントチャート用)
     */
    public function updatePosition(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $this->authorize('update', $task);

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
            'message' => '工程の位置が更新されました。'
        ]);
    }

    /**
     * 工程の親工程を更新 (ガントチャート用)
     */
    public function updateParent(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $this->authorize('update', $task);

        if ($validated['parent_id'] == $task->id) {
            return response()->json([
                'success' => false,
                'message' => '自分自身を親工程にはできません。'
            ], 422);
        }
        if ($validated['parent_id']) {
            $newParent = Task::find($validated['parent_id']);
            if ($task->isAncestorOf($newParent)) {
                return response()->json([
                    'success' => false,
                    'message' => '循環参照が発生するため、この操作はできません。'
                ], 422);
            }
        }

        $task->update([
            'parent_id' => $validated['parent_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => '工程の親子関係が更新されました。'
        ]);
    }


    /**
     * 担当者を更新する
     */
    public function updateAssignee(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);
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
        $this->authorize('update', $task);
        if (!$task->is_folder) {
            return response()->json(['error' => 'ファイルはフォルダにのみアップロードできます。'], 422);
        }

        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        $file = $request->file('file');
        $path = 'task_files/' . $task->id;
        $storedName = $file->hashName();
        $fullPath = Storage::disk('local')->putFileAs($path, $file, $storedName);

        $taskFile = $task->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $fullPath,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        return response()->json($taskFile, 201);
    }

    /**
     * ファイル一覧を取得する
     */
    public function getFiles(Project $project, Task $task)
    {
        $this->authorize('update', $task);
        $files = $task->files()->orderBy('original_name')->get();
        return view('tasks.partials.file-list', ['files' => $files, 'project' => $project, 'task' => $task])->render();
    }

    /**
     * ファイルをダウンロードする
     */
    public function downloadFile(Project $project, Task $task, TaskFile $file)
    {
        $this->authorize('update', $task);
        if ($file->task_id !== $task->id) {
            abort(404);
        }
        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    /**
     * ファイルをブラウザで表示する (画像プレビュー用)
     */
    public function showFile(Project $project, Task $task, TaskFile $file)
    {
        $this->authorize('update', $task);
        if ($file->task_id !== $task->id) {
            abort(404);
        }
        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        return response()->file(Storage::disk('local')->path($file->path));
    }

    /**
     * ファイルを削除する
     */
    public function deleteFile(Project $project, Task $task, TaskFile $file)
    {
        $this->authorize('update', $task);
        if ($file->task_id !== $task->id) {
            abort(404);
        }

        Storage::disk('local')->delete($file->path);
        $file->delete();

        return response()->json(['success' => true, 'message' => 'ファイルを削除しました。']);
    }

    public function storeFromTemplate(Request $request, Project $project)
    {
        $this->authorize('create', Task::class);
        $validated = $request->validate([
            'process_template_id' => 'required|exists:process_templates,id',
            'template_start_date' => 'required|date',
            'parent_id_for_template' => 'nullable|exists:tasks,id',
            'character_id_for_template' => ['nullable', 'exists:characters,id', function ($attribute, $value, $fail) use ($project) {
                if ($value && !$project->characters()->where('id', $value)->exists()) {
                    $fail('選択されたキャラクターはこの案件に所属していません。');
                }
            }],
        ]);

        $template = ProcessTemplate::with('items')->findOrFail($validated['process_template_id']);
        $currentStartDate = Carbon::parse($validated['template_start_date']);
        $parentTaskIdForTemplate = $validated['parent_id_for_template'] ?? null;
        $characterIdForTemplate = $validated['character_id_for_template'] ?? null;

        foreach ($template->items as $item) {
            $taskData = [
                'name' => $item->name,
                'description' => null,
                'assignee' => null,
                'parent_id' => $parentTaskIdForTemplate,
                'character_id' => $characterIdForTemplate,
                'is_milestone' => false,
                'is_folder' => false,
                'progress' => 0,
                'status' => 'not_started',
                'start_date' => $currentStartDate->copy(),
                'duration' => $item->default_duration ?? 1,
            ];
            $taskData['end_date'] = $currentStartDate->copy()->addDays(($item->default_duration ?? 1) - 1);

            $project->tasks()->create($taskData);
            $currentStartDate = $taskData['end_date']->addDay();
        }
        return redirect()->route('gantt.index', ['project_id' => $project->id])->with('success', 'テンプレートから工程を一括作成しました。');
    }

    /**
     * 工程のメモ（description）を更新
     */
    /**
     * 工程のメモ（description）を更新
     */
    public function updateDescription(Request $request, Project $project, Task $task): JsonResponse
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'description' => 'nullable|string|max:2000',
            ]);

            // 工程が指定されたプロジェクトに属しているかチェック
            if ($task->project_id !== $project->id) {
                return response()->json([
                    'success' => false,
                    'message' => '指定された工程が見つかりません',
                ], 404);
            }

            // 権限チェック（簡単な実装）
            // より詳細な権限チェックが必要な場合は、Policyを使用してください
            // $this->authorize('update', $task);

            // メモを更新
            $task->description = $validated['description'] ?? '';
            $task->save();

            Log::info('Task description updated', [
                'task_id' => $task->id,
                'project_id' => $project->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'メモを更新しました',
                'description' => $task->description,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Task description update failed', [
                'task_id' => $task->id ?? null,
                'project_id' => $project->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'メモの更新に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }
}

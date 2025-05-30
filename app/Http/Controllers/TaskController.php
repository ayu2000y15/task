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
use Illuminate\Http\JsonResponse; // ★ JsonResponse を use

class TaskController extends Controller
{
    // ... (index, create, store, edit, update, destroy, deleteTaskAndChildren, updateProgress, updatePosition, updateParent, updateAssignee は変更なし) ...

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
            'is_milestone_or_folder' => 'required|in:milestone,folder,task,todo_task',
            'status' => 'required_unless:is_milestone_or_folder,folder|nullable|in:not_started,in_progress,completed,on_hold,cancelled',
            'end_date' => 'nullable|date|after_or_equal:start_date|prohibited_if:is_milestone_or_folder,milestone',
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
            'is_milestone' => $isMilestone,
            'is_folder' => $isFolder,
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
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
            }
            $taskData['duration'] = 1;
            $taskData['status'] = $validated['status'] ?? 'not_started';
        } elseif ($isTodoTask) {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['is_milestone'] = false;
            $taskData['is_folder'] = false;
            $taskData['status'] = $validated['status'] ?? 'not_started';
        } else {
            $startDate = Carbon::parse($validated['start_date']);
            $duration = $validated['duration'];
            $endDate = $startDate->copy()->addDays($duration - 1);

            $taskData['start_date'] = $startDate;
            $taskData['end_date'] = $endDate;
            $taskData['duration'] = $duration;
            $taskData['status'] = $validated['status'] ?? 'not_started';
        }
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
            'start_date' => 'required_if:is_milestone_or_folder,milestone|nullable|date',
            'duration' => 'nullable|integer|min:1|required_with:start_date|prohibited_if:is_milestone_or_folder,milestone',
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => 'required_unless:is_milestone_or_folder,folder|nullable|in:not_started,in_progress,completed,on_hold,cancelled',
            'end_date' => 'nullable|date|after_or_equal:start_date|prohibited_if:is_milestone_or_folder,milestone',
        ]);

        $isFolder = $task->is_folder;
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
            $taskData['progress'] = null;
        } elseif ($isMilestone) {
            if ($request->filled('start_date')) {
                $startDate = Carbon::parse($validated['start_date']);
                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $startDate->copy();
            }
            $taskData['duration'] = 1;
            $taskData['status'] = $validated['status'] ?? $task->status;
        } else {
            if ($request->filled('start_date') && $request->filled('duration')) {
                $startDate = Carbon::parse($validated['start_date']);
                $duration = $validated['duration'];
                $endDate = $startDate->copy()->addDays($duration - 1);

                $taskData['start_date'] = $startDate;
                $taskData['end_date'] = $endDate;
                $taskData['duration'] = $duration;
            } elseif (!$request->filled('start_date')) {
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
                $taskData['duration'] = null;
            }
            $taskData['status'] = $validated['status'] ?? $task->status;
        }
        if ($isFolder) {
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
                Storage::disk('local')->delete($file->path); // ★ ログ記録の対象（deleteFileメソッドを呼ぶか、ここで手動ログ）
                // activity()
                //    ->causedBy(auth()->user())
                //    ->performedOn($file) // TaskFileモデルインスタンス
                //    ->event('deleted') // spatie/laravel-activitylog v4以降ではevent()は推奨されない場合がある
                //    ->log("ファイル「{$file->original_name}」が工程フォルダ「{$task->name}」から削除されました (親工程削除による)");
                $file->delete(); // これによりTaskFileモデルのLogsActivityが発火するはず
            }
        }
        foreach ($task->children as $child) {
            $this->deleteTaskAndChildren($child);
        }

        $task->delete(); // これによりTaskモデルのLogsActivityが発火
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
        $task->update($updateData); // これによりTaskモデルのLogsActivityが発火

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

        $task->update([ // これによりTaskモデルのLogsActivityが発火
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

        $task->update([ // これによりTaskモデルのLogsActivityが発火
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
        $task->save(); // これによりTaskモデルのLogsActivityが発火

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
            'file' => 'required|file|max:102400', // 100MB
        ]);

        $file = $request->file('file');
        $path = 'task_files/' . $task->id;
        $storedName = $file->hashName(); // store() or storeAs() will generate a unique name
        $fullPath = Storage::disk('local')->putFileAs($path, $file, $storedName);

        $taskFile = $task->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName, // or $file->hashName() if stored directly
            'path' => $fullPath,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        // ★ ログ記録: ファイルアップロード
        activity()
            ->causedBy(auth()->user())
            ->performedOn($taskFile) // TaskFileモデルを対象とする
            ->withProperties(['task_name' => $task->name, 'project_name' => $project->title])
            ->log("ファイル「{$taskFile->original_name}」が工程フォルダ「{$task->name}」(ID:{$task->id}) にアップロードされました。");

        $files = $task->fresh()->files()->orderBy('original_name')->get();
        $updatedHtml = view('tasks.partials.file-list-tailwind', compact('files', 'project', 'task'))->render();

        return response()->json([
            'success' => true,
            'html' => $updatedHtml
        ]);
    }

    /**
     * ファイル一覧を取得する (ログ対象外)
     */
    public function getFiles(Project $project, Task $task)
    {
        $this->authorize('update', $task); // ファイル一覧表示も更新権限で制御（または専用の閲覧権限）
        $files = $task->files()->orderBy('original_name')->get();
        return view('tasks.partials.file-list-tailwind', ['files' => $files, 'project' => $project, 'task' => $task])->render();
    }

    /**
     * ファイルをダウンロードする
     */
    public function downloadFile(Project $project, Task $task, TaskFile $file)
    {
        $this->authorize('update', $task); // ダウンロード権限は更新権限で代用（または専用権限 tasks.file-download）
        if ($file->task_id !== $task->id) {
            abort(404);
        }
        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        // ★ ログ記録: ファイルダウンロード
        activity()
            ->causedBy(auth()->user())
            ->performedOn($file) // TaskFileモデルを対象とする
            ->withProperties(['task_name' => $task->name, 'project_name' => $project->title])
            ->log("ファイル「{$file->original_name}」が工程フォルダ「{$task->name}」(ID:{$task->id}) からダウンロードされました。");

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    /**
     * ファイルをブラウザで表示する (画像プレビュー用) (ログ対象外とするか、必要なら追加)
     */
    public function showFile(Project $project, Task $task, TaskFile $file)
    {
        $this->authorize('update', $task); // 閲覧権限は更新権限で代用（または専用権限 tasks.file-view）
        if ($file->task_id !== $task->id) {
            abort(404);
        }
        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'ファイルが見つかりません。');
        }
        // ファイル表示自体はログの主要な関心事から外れる場合もあるため、ここではログを記録しない。
        // 必要であれば downloadFile と同様にログを追加可能。

        return response()->file(Storage::disk('local')->path($file->path));
    }

    /**
     * ファイルを削除する
     */
    public function deleteFile(Project $project, Task $task, TaskFile $file)
    {
        $this->authorize('update', $task); // 削除権限は更新権限で代用（または専用権限 tasks.file-delete）
        if ($file->task_id !== $task->id) {
            abort(404);
        }

        $originalFileName = $file->original_name; // 削除前にファイル名を取得

        Storage::disk('local')->delete($file->path);
        $file->delete(); // これによりTaskFileモデルにLogsActivityがあれば発火するが、今回は手動ログで詳細を記録

        // ★ ログ記録: ファイル削除
        activity()
            ->causedBy(auth()->user())
            ->performedOn($task) // 親であるTaskモデルを対象とする (TaskFileは既に削除されているため)
            ->withProperties(['deleted_file_name' => $originalFileName, 'task_name' => $task->name, 'project_name' => $project->title])
            ->log("ファイル「{$originalFileName}」が工程フォルダ「{$task->name}」(ID:{$task->id}) から削除されました。");


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
        $createdTaskNames = []; // ★ ログ用に作成されたタスク名を収集

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

            $createdTask = $project->tasks()->create($taskData); // これによりTaskモデルのLogsActivityが発火
            $createdTaskNames[] = $createdTask->name; // ★ 作成されたタスク名を収集
            $currentStartDate = $taskData['end_date']->addDay();
        }

        // ★ ログ記録: テンプレートからの工程一括作成
        if (count($createdTaskNames) > 0) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($project) // プロジェクトを操作対象とする
                ->withProperties([
                    'template_name' => $template->name,
                    'created_tasks_count' => count($createdTaskNames),
                    'first_task_name' => $createdTaskNames[0] ?? null // 例として最初のタスク名
                ])
                ->log("工程テンプレート「{$template->name}」から {$project->title} に " . count($createdTaskNames) . " 件の工程が一括作成されました。");
        }

        return redirect()->route('gantt.index', ['project_id' => $project->id])->with('success', 'テンプレートから工程を一括作成しました。');
    }

    public function updateDescription(Request $request, Project $project, Task $task): JsonResponse
    {
        try {
            $validated = $request->validate([
                'description' => 'nullable|string|max:2000',
            ]);

            if ($task->project_id !== $project->id) {
                return response()->json([
                    'success' => false,
                    'message' => '指定された工程が見つかりません',
                ], 404);
            }
            // $this->authorize('update', $task); // ポリシーでの権限チェック

            $task->description = $validated['description'] ?? '';
            $task->save(); // これによりTaskモデルのLogsActivityが発火 (descriptionが$fillableに含まれていれば)

            Log::info('Task description updated', [ // これはLaravelの標準ログ
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

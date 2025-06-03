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
use Illuminate\Validation\Rule;

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

        $taskTypeInput = $request->input('is_milestone_or_folder', 'task');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'character_id' => 'nullable|exists:characters,id',
            'start_date' => [
                Rule::requiredIf(fn() => in_array($taskTypeInput, ['milestone', 'task'])),
                'nullable',
                'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s'
            ],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s',
                Rule::requiredIf(fn() => $taskTypeInput === 'task' && !$request->filled('duration_value')),
                Rule::prohibitedIf(fn() => $taskTypeInput === 'milestone'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('start_date') && $request->filled($attribute)) {
                        if (Carbon::parse($value)->lt(Carbon::parse($request->input('start_date')))) {
                            $fail('終了日時は開始日時以降に設定してください。');
                        }
                    }
                },
            ],
            'duration_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn() => $taskTypeInput === 'task' && !$request->filled('end_date')),
            ],
            'duration_unit' => [
                'nullable',
                'string',
                Rule::in(['minutes', 'hours', 'days']),
                Rule::requiredIf(fn() => $taskTypeInput === 'task' && !$request->filled('end_date') && $request->filled('duration_value')),
            ],
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'is_milestone_or_folder' => 'required|in:milestone,folder,task,todo_task',
            'status' => [
                Rule::requiredIf(fn() => !in_array($taskTypeInput, ['folder'])),
                'nullable',
                Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])
            ],
        ], [
            'start_date.required_if' => '開始日時は必須です（工程または重要納期の場合）。',
            'start_date.date_format' => '開始日時は正しい形式で入力してください。',
            'end_date.date_format' => '終了日時は正しい形式で入力してください。',
            'end_date.required_if' => '工程の場合、工数または終了日時のどちらかは必須です。',
            'duration_value.required_if' => '工程の場合、工数または終了日時のどちらかは必須です。',
            'duration_unit.required_if' => '工程で工数を入力する場合、単位は必須です。',
            'status.required_if' => 'ステータスは必須です（フォルダ以外の場合）。',
        ]);

        $isFolder = $taskTypeInput === 'folder';
        $isMilestone = $taskTypeInput === 'milestone';
        $isTodoTask = $taskTypeInput === 'todo_task';

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

        if ($isFolder || $isTodoTask) {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['progress'] = $isFolder ? null : 0;
            $taskData['status'] = $isFolder ? null : ($validated['status'] ?? 'not_started');
        } elseif ($isMilestone) {
            $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
            $taskData['start_date'] = $startDate;
            $taskData['end_date'] = $startDate ? $startDate->copy() : null;
            $taskData['duration'] = 0;
            $taskData['status'] = $validated['status'] ?? 'not_started';
        } else { // 通常のタスク (task)
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $request->filled('end_date') ? Carbon::parse($validated['end_date']) : null;
            $durationValue = $request->filled('duration_value') ? (float)$validated['duration_value'] : null;
            $durationUnit = $request->filled('duration_unit') ? $validated['duration_unit'] : null;

            $taskData['start_date'] = $startDate;
            $taskData['status'] = $validated['status'] ?? 'not_started';

            if ($endDate && $endDate->greaterThanOrEqualTo($startDate)) {
                $taskData['end_date'] = $endDate;
                // 修正: $startDate->diffInMinutes($endDate) を使用
                $taskData['duration'] = $startDate->diffInMinutes($endDate);
            } elseif ($durationValue !== null && $durationUnit !== null) {
                $calculatedDurationInMinutes = 0;
                switch ($durationUnit) {
                    case 'days':
                        $calculatedDurationInMinutes = $durationValue * 24 * 60;
                        break; // 1日24時間
                    case 'hours':
                        $calculatedDurationInMinutes = $durationValue * 60;
                        break;
                    case 'minutes':
                        $calculatedDurationInMinutes = $durationValue;
                        break;
                }
                $taskData['duration'] = $calculatedDurationInMinutes;
                $taskData['end_date'] = $startDate->copy()->addMinutes($calculatedDurationInMinutes);
            } else {
                $taskData['duration'] = 0;
                $taskData['end_date'] = $startDate->copy();
            }
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

        $currentTaskType = 'task';
        if ($task->is_milestone) $currentTaskType = 'milestone';
        elseif ($task->is_folder) $currentTaskType = 'folder';
        elseif (!$task->start_date && !$task->end_date && !$task->is_milestone && !$task->is_folder) $currentTaskType = 'todo_task';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'character_id' => 'nullable|exists:characters,id',
            'start_date' => [
                Rule::requiredIf(fn() => in_array($currentTaskType, ['milestone', 'task'])),
                'nullable',
                'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s'
            ],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s',
                Rule::requiredIf(fn() => $currentTaskType === 'task' && !$request->filled('duration_value')),
                Rule::prohibitedIf(fn() => $currentTaskType === 'milestone'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('start_date') && $request->filled($attribute)) {
                        if (Carbon::parse($value)->lt(Carbon::parse($request->input('start_date')))) {
                            $fail('終了日時は開始日時以降に設定してください。');
                        }
                    }
                },
            ],
            'duration_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn() => $currentTaskType === 'task' && !$request->filled('end_date')),
            ],
            'duration_unit' => [
                'nullable',
                'string',
                Rule::in(['minutes', 'hours', 'days']),
                Rule::requiredIf(fn() => $currentTaskType === 'task' && !$request->filled('end_date') && $request->filled('duration_value')),
            ],
            'assignee' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => [
                Rule::requiredIf(fn() => $currentTaskType !== 'folder'),
                'nullable',
                Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])
            ],
        ], [
            'start_date.required_if' => '開始日時は必須です（工程または重要納期の場合）。',
            'start_date.date_format' => '開始日時は正しい形式で入力してください。',
            'end_date.date_format' => '終了日時は正しい形式で入力してください。',
            'end_date.required_if' => '工程の場合、工数または終了日時のどちらかは必須です。',
            'duration_value.required_if' => '工程の場合、工数または終了日時のどちらかは必須です。',
            'duration_unit.required_if' => '工程で工数を入力する場合、単位は必須です。',
            'status.required_if' => 'ステータスは必須です（フォルダ以外の場合）。',
        ]);


        $taskData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'character_id' => (array_key_exists('character_id', $validated) && $validated['character_id'] !== '') ? $validated['character_id'] : null,
            'assignee' => $validated['assignee'],
            'parent_id' => $validated['parent_id'],
        ];

        if ($currentTaskType === 'folder') {
            $taskData['status'] = null;
        } elseif ($currentTaskType === 'milestone') {
            $startDate = $request->filled('start_date') ? Carbon::parse($validated['start_date']) : $task->start_date;
            $taskData['start_date'] = $startDate;
            $taskData['end_date'] = $startDate ? $startDate->copy() : null;
            $taskData['duration'] = 0;
            $taskData['status'] = $validated['status'] ?? $task->status;
        } elseif ($currentTaskType === 'todo_task') {
            $taskData['start_date'] = null;
            $taskData['end_date'] = null;
            $taskData['duration'] = null;
            $taskData['status'] = $validated['status'] ?? $task->status;
        } else { // 通常のタスク
            $startDate = $request->filled('start_date') ? Carbon::parse($validated['start_date']) : $task->start_date;
            $endDate = $request->filled('end_date') ? Carbon::parse($validated['end_date']) : null;
            $durationValue = $request->filled('duration_value') ? (float)$validated['duration_value'] : null;
            $durationUnit = $request->filled('duration_unit') ? $validated['duration_unit'] : null;

            $taskData['start_date'] = $startDate;
            $taskData['status'] = $validated['status'] ?? $task->status;

            if ($startDate && $endDate && $endDate->greaterThanOrEqualTo($startDate)) {
                $taskData['end_date'] = $endDate;
                // 修正: $startDate->diffInMinutes($endDate) を使用
                $taskData['duration'] = $startDate->diffInMinutes($endDate);
            } elseif ($startDate && $durationValue !== null && $durationUnit !== null) {
                $calculatedDurationInMinutes = 0;
                switch ($durationUnit) {
                    case 'days':
                        $calculatedDurationInMinutes = $durationValue * 24 * 60;
                        break; // 1日24時間
                    case 'hours':
                        $calculatedDurationInMinutes = $durationValue * 60;
                        break;
                    case 'minutes':
                        $calculatedDurationInMinutes = $durationValue;
                        break;
                }
                $taskData['duration'] = $calculatedDurationInMinutes;
                $taskData['end_date'] = $startDate->copy()->addMinutes($calculatedDurationInMinutes);
            } elseif ($startDate) {
                $taskData['duration'] = $task->duration ?? 0;
                $taskData['end_date'] = $startDate->copy()->addMinutes($taskData['duration']);
            } else {
                $taskData['start_date'] = null;
                $taskData['end_date'] = null;
                $taskData['duration'] = null;
            }
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
        Log::info('storeFromTemplate called', $request->all());

        $validated = $request->validate([
            'process_template_id' => 'required|exists:process_templates,id',
            'template_start_date' => 'required|date_format:Y-m-d\TH:i,Y-m-d\TH:i:s,Y-m-d',
            'parent_id_for_template' => 'nullable|exists:tasks,id',
            'character_id_for_template' => ['nullable', 'exists:characters,id', function ($attribute, $value, $fail) use ($project) {
                if ($value && !$project->characters()->where('id', $value)->exists()) {
                    $fail('選択されたキャラクターはこの案件に所属していません。');
                }
            }],
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
        ], [
            'working_hours_start.required' => '稼働開始時刻は必須です。',
            'working_hours_start.date_format' => '稼働開始時刻はHH:MM形式で入力してください。',
            'working_hours_end.required' => '稼働終了時刻は必須です。',
            'working_hours_end.date_format' => '稼働終了時刻はHH:MM形式で入力してください。',
            'working_hours_end.after' => '稼働終了時刻は開始時刻より後に設定してください。',
        ]);
        Log::info('Validation passed for storeFromTemplate');

        $template = ProcessTemplate::with('items')->findOrFail($validated['process_template_id']);

        $carbonWorkDayStartTime = Carbon::parse($validated['working_hours_start']);
        $carbonWorkDayEndTime = Carbon::parse($validated['working_hours_end']);

        // 稼働時間を分で計算 (abs=true を明示的に指定)
        $workableMinutesPerDay = $carbonWorkDayEndTime->diffInMinutes($carbonWorkDayStartTime, true);

        if ($workableMinutesPerDay <= 0 && !($carbonWorkDayStartTime->eq($carbonWorkDayEndTime))) { // 0分稼働は許可しない (ただし開始と終了が同じ場合は0分として扱う)
            Log::error('Workable minutes per day is not positive', [
                'start_time' => $validated['working_hours_start'],
                'end_time' => $validated['working_hours_end'],
                'calculated_minutes' => $workableMinutesPerDay
            ]);
            return redirect()->back()->withErrors(['working_hours_end' => '稼働終了時刻は開始時刻より後に適切に設定し、0分以上の稼働時間を確保してください。'])->withInput();
        }
        Log::info('Workable minutes per day', ['minutes' => $workableMinutesPerDay]);

        $currentTaskProcessingDateTime = Carbon::parse($validated['template_start_date']);
        Log::info('Initial processing date from input', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]);

        // テンプレート適用開始日時に時刻が含まれていない場合、または稼働開始時刻より前の場合、稼働開始時刻に設定
        $parsedInputDate = Carbon::parse($validated['template_start_date']);
        if (strpos($validated['template_start_date'], 'T') === false && strpos($validated['template_start_date'], ' ') === false) {
            $currentTaskProcessingDateTime->setTimeFrom($carbonWorkDayStartTime);
            Log::info('Time not in input, set to working_hours_start', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]);
        } elseif ($parsedInputDate->copy()->setTime($parsedInputDate->hour, $parsedInputDate->minute)
            ->lt($parsedInputDate->copy()->setTimeFrom($carbonWorkDayStartTime))
        ) {
            $currentTaskProcessingDateTime->setTimeFrom($carbonWorkDayStartTime);
            Log::info('Input time before working_hours_start, adjusted', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]);
        }

        // 開始日時が稼働終了時刻より後なら翌日の稼働開始時刻へ
        if ($currentTaskProcessingDateTime->copy()->setTime($currentTaskProcessingDateTime->hour, $currentTaskProcessingDateTime->minute)
            ->gte($currentTaskProcessingDateTime->copy()->setTimeFrom($carbonWorkDayEndTime))
        ) {
            $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
            Log::info('Input time after working_hours_end, moved to next day start', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]);
        }

        // 週末なら月曜へスキップし、稼働開始時刻に設定
        while ($currentTaskProcessingDateTime->isWeekend()) {
            $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
            Log::info('Skipped weekend, new date', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]);
        }
        Log::info('Final initial processing date', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]);


        $parentTaskIdForTemplate = $validated['parent_id_for_template'] ?? null;
        $characterIdForTemplate = $validated['character_id_for_template'] ?? null;
        $createdTaskNames = [];

        foreach ($template->items()->orderBy('order')->get() as $itemIndex => $item) {
            Log::info("Processing template item: {$item->name} (Order: {$item->order})");
            $taskDurationInMinutes = $item->default_duration ?? 0;
            Log::info("Item duration in minutes: {$taskDurationInMinutes}");

            // このタスクの実際の開始日時を、前のタスクの終了処理後の $currentTaskProcessingDateTime からコピー
            $actualTaskStartDate = $currentTaskProcessingDateTime->copy();
            // ただし、actualTaskStartDate が稼働時間外であれば調整
            if ($actualTaskStartDate->copy()->setTime($actualTaskStartDate->hour, $actualTaskStartDate->minute)
                ->lt($actualTaskStartDate->copy()->setTimeFrom($carbonWorkDayStartTime))
            ) {
                $actualTaskStartDate->setTimeFrom($carbonWorkDayStartTime);
            } elseif ($actualTaskStartDate->copy()->setTime($actualTaskStartDate->hour, $actualTaskStartDate->minute)
                ->gte($actualTaskStartDate->copy()->setTimeFrom($carbonWorkDayEndTime))
            ) {
                $actualTaskStartDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
            }
            while ($actualTaskStartDate->isWeekend()) {
                $actualTaskStartDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
            }
            Log::info("Actual start date for item {$item->name}: " . $actualTaskStartDate->format('Y-m-d H:i:s'));


            $remainingDurationForThisTask = $taskDurationInMinutes;
            $taskCalculatedEndDate = $actualTaskStartDate->copy();

            if ($taskDurationInMinutes <= 0) { // 工数0の場合は開始と終了を同じにする
                $taskCalculatedEndDate = $actualTaskStartDate->copy();
                Log::info("Task duration is 0. End date set to start date: " . $taskCalculatedEndDate->format('Y-m-d H:i:s'));
            } else {
                while ($remainingDurationForThisTask > 0) {
                    Log::info("Remaining duration for {$item->name}: {$remainingDurationForThisTask} mins. Current calc end date for loop start: " . $taskCalculatedEndDate->format('Y-m-d H:i:s'));

                    // 現在の日の稼働終了時刻
                    $todayWorkEnd = $taskCalculatedEndDate->copy()->setTimeFrom($carbonWorkDayEndTime);
                    // 現在の日の処理開始時刻から稼働終了時刻までの残り稼働時間
                    $minutesAvailableToday = $taskCalculatedEndDate->diffInMinutes($todayWorkEnd, false); // falseで絶対値でない差分
                    Log::info("Minutes available today ({$taskCalculatedEndDate->format('Y-m-d')} from {$taskCalculatedEndDate->format('H:i')} to {$todayWorkEnd->format('H:i')}): {$minutesAvailableToday}");

                    if ($minutesAvailableToday <= 0) {
                        $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                        while ($taskCalculatedEndDate->isWeekend()) {
                            $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                        }
                        Log::info("No time left today or started after EOD. Moved to next working day start: " . $taskCalculatedEndDate->format('Y-m-d H:i:s'));
                        // ループの先頭に戻って、新しい日付で minutesAvailableToday を再計算
                        continue;
                    }

                    if ($remainingDurationForThisTask <= $minutesAvailableToday) {
                        $taskCalculatedEndDate->addMinutes($remainingDurationForThisTask);
                        $remainingDurationForThisTask = 0;
                        Log::info("Task fits in current day. New calc end date: " . $taskCalculatedEndDate->format('Y-m-d H:i:s'));
                    } else {
                        $taskCalculatedEndDate->addMinutes($minutesAvailableToday);
                        $remainingDurationForThisTask -= $minutesAvailableToday;
                        Log::info("Task does not fit. Remaining duration: {$remainingDurationForThisTask}. End of day reached: " . $taskCalculatedEndDate->format('Y-m-d H:i:s'));

                        // 次の稼働日の開始時刻へ
                        $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                        while ($taskCalculatedEndDate->isWeekend()) {
                            $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                        }
                        Log::info("Moved to next working day start for remaining duration: " . $taskCalculatedEndDate->format('Y-m-d H:i:s'));
                    }
                }
            }

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
                'start_date' => $actualTaskStartDate,
                'duration' => $taskDurationInMinutes,
                'end_date' => $taskCalculatedEndDate,
            ];
            Log::info("Task data to be created for {$item->name}", $taskData);

            $createdTask = $project->tasks()->create($taskData);
            $createdTaskNames[] = $createdTask->name;

            // 次のタスクの開始日時を、現在のタスクの計算された終了日時に設定
            $currentTaskProcessingDateTime = $taskCalculatedEndDate->copy();
            // 次のタスクの開始が稼働時間外なら調整
            if ($currentTaskProcessingDateTime->copy()->setTime($currentTaskProcessingDateTime->hour, $currentTaskProcessingDateTime->minute)
                ->gte($currentTaskProcessingDateTime->copy()->setTimeFrom($carbonWorkDayEndTime))
            ) {
                $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
                Log::info("Next task start (after EOD adjustment): " . $currentTaskProcessingDateTime->format('Y-m-d H:i:s'));
            }
            while ($currentTaskProcessingDateTime->isWeekend()) {
                $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
                Log::info("Next task start (after weekend skip): " . $currentTaskProcessingDateTime->format('Y-m-d H:i:s'));
            }
            Log::info("End of loop for item {$item->name}. Next task processing date: " . $currentTaskProcessingDateTime->format('Y-m-d H:i:s'));
        }

        if (count($createdTaskNames) > 0) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($project)
                ->withProperties([
                    'template_name' => $template->name,
                    'created_tasks_count' => count($createdTaskNames),
                    'first_task_name' => $createdTaskNames[0] ?? null
                ])
                ->log("工程テンプレート「{$template->name}」から {$project->title} に " . count($createdTaskNames) . " 件の工程が一括作成されました。");
            Log::info('Activity log created for template application.');
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

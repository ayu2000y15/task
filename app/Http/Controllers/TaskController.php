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
use App\Models\User;

class TaskController extends Controller
{
    // ... (index, create, store, edit, update, destroy, deleteTaskAndChildren, updateProgress, updatePosition, updateParent, updateAssignee は変更なし) ...

    /**
     * 工程一覧を表示
     */
    public function index(Request $request, TaskService $taskService)
    {
        $this->authorize('viewAny', Task::class);

        // ▼▼▼【変更点1】'hide_completed' をフィルター条件に追加 ▼▼▼
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'character_id' => $request->input('character_id', ''),
            'assignee_id' => $request->input('assignee_id', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
            'due_date' => $request->input('due_date', ''),
            'hide_completed' => $request->boolean('hide_completed'), // booleanとして取得
        ];

        $sortBy = $request->input('sort_by', 'start_date');
        $sortOrder = $request->input('sort_order', 'asc');
        $orderableColumns = ['name', 'project_title', 'character_name', 'start_date', 'status'];
        if (!in_array($sortBy, $orderableColumns)) {
            $sortBy = 'start_date';
        }

        // 1. まずフィルター条件に直接一致するタスクのIDを取得
        $matchingTaskIdsQuery = $taskService->buildFilteredQuery($filters);

        // ▼▼▼【変更点2】「完了を非表示」が有効な場合、クエリに条件を追記 ▼▼▼
        if ($filters['hide_completed']) {
            // statusが'completed'でないタスクのみを対象にする
            $matchingTaskIdsQuery->where('status', '!=', 'completed');
        }

        $matchingTaskIds = $matchingTaskIdsQuery->pluck('id');

        // 2. 一致したタスクとそのすべての祖先タスクのIDを取得する
        // (このセクションは変更なし)
        $requiredTaskIds = collect();
        if ($matchingTaskIds->isNotEmpty()) {
            $allTasksLookup = Task::query()
                ->select('id', 'parent_id')
                ->whereHas('project', function ($q) {
                    $q->whereNotIn('status', ['completed', 'cancelled']);
                })
                ->get()
                ->keyBy('id');

            $tasksToProcess = $matchingTaskIds->toArray();
            $processedIds = collect();

            while (!empty($tasksToProcess)) {
                $currentId = array_pop($tasksToProcess);

                if ($processedIds->contains($currentId)) {
                    continue;
                }

                $requiredTaskIds->push($currentId);
                $processedIds->push($currentId);

                $task = $allTasksLookup->get($currentId);
                if ($task && $task->parent_id) {
                    array_push($tasksToProcess, $task->parent_id);
                }
            }
        }
        $requiredTaskIds = $requiredTaskIds->unique();

        // 3. 必要なタスクIDを使って、改めてタスク情報を取得する
        // (このセクションは変更なし)
        $query = Task::with(['project', 'files', 'children', 'character', 'assignees', 'workLogs']);

        if (array_filter($filters)) {
            if ($requiredTaskIds->isEmpty()) {
                $allTasks = collect();
            } else {
                $allTasks = $query->whereIn('id', $requiredTaskIds)->get();
            }
        } else {
            $query->whereHas('project', function ($q) {
                $q->whereNotIn('status', ['completed', 'cancelled']);
            });
            $allTasks = $query->get();
        }

        $tasksGroupedByParent = $allTasks->groupBy('parent_id');
        $hierarchicallySortedTasks = collect();

        // (再帰関数 appendTasksRecursively は変更なし)
        $appendTasksRecursively = function ($parentId, $tasksGroupedByParent, &$hierarchicallySortedTasks) use (&$appendTasksRecursively, $sortBy, $sortOrder) {
            $keyForGrouping = $parentId === null ? '' : $parentId;

            if (!$tasksGroupedByParent->has($keyForGrouping)) {
                return;
            }

            $childrenOfCurrentParent = $tasksGroupedByParent->get($keyForGrouping);

            $sortClosure = function ($task) use ($sortBy) {
                switch ($sortBy) {
                    case 'project_title':
                        return $task->project->title ?? '';
                    case 'character_name':
                        return $eagerLoadedTask->character->name ?? '';
                    default:
                        return $task->{$sortBy} ?? '';
                }
            };

            if ($sortOrder === 'desc') {
                $childrenOfCurrentParent = $childrenOfCurrentParent->sortByDesc($sortClosure);
            } else {
                $childrenOfCurrentParent = $childrenOfCurrentParent->sortBy($sortClosure);
            }

            foreach ($childrenOfCurrentParent as $task) {
                $hierarchicallySortedTasks->push($task);
                $appendTasksRecursively($task->id, $tasksGroupedByParent, $hierarchicallySortedTasks);
            }
        };

        $appendTasksRecursively(null, $tasksGroupedByParent, $hierarchicallySortedTasks);

        // (最終的なタスクを絞り込むロジックは変更なし)
        if (array_filter($filters)) {
            $tasks = $hierarchicallySortedTasks->filter(function ($task) use ($matchingTaskIds) {
                return $matchingTaskIds->contains($task->id);
            });
        } else {
            $tasks = $hierarchicallySortedTasks;
        }

        // (ビューに値を渡す部分は変更なし)
        $allProjects = Project::orderBy('title')->get();
        $charactersForFilter = collect();
        if (!empty($filters['project_id'])) {
            $projectWithChars = Project::find($filters['project_id']);
            if ($projectWithChars) {
                $charactersForFilter = $projectWithChars->characters()->orderBy('name')->get();
            }
        }

        $assigneesForFilter = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');

        $statusOptions = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '一時停止中',
            'cancelled' => 'キャンセル',
        ];

        $assigneeOptions = User::where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($user) {
                return ['id' => $user->id, 'name' => $user->name];
            })->values()->all();

        $taskIds = $tasks->pluck('id');
        $activeWorkLogs = \App\Models\WorkLog::whereIn('task_id', $taskIds)
            ->where('status', 'active')
            ->get();

        if ($request->ajax()) {
            $tasksToList = $tasks->where('is_milestone', false)->where('is_folder', false);
            $tableId = 'task-table-index';

            $html = view('tasks.partials.task-table', compact(
                'tasksToList',
                'assigneeOptions',
                'sortBy',
                'sortOrder',
                'tableId'
            ))->render();

            return response()->json(['html' => $html]);
        }

        return view('tasks.index', compact(
            'tasks',
            'allProjects',
            'charactersForFilter',
            'assigneesForFilter',
            'statusOptions',
            'filters',
            'assigneeOptions',
            'sortBy',
            'sortOrder',
            'activeWorkLogs'
        ));
    }

    public function create(Request $request, Project $project)
    {
        $this->authorize('create', Task::class);
        $processTemplates = ProcessTemplate::orderBy('name')->get();
        $parentTask = null;
        $project->load('characters');

        if ($request->has('parent')) {
            $parentTask = Task::findOrFail($request->parent);
        }

        $potentialParentTasks = $project->tasks()
            ->with('character')
            ->where('is_folder', false)
            ->orderBy('name')
            ->get();

        $parentTaskOptions = $potentialParentTasks->mapWithKeys(function ($ptask) {
            $characterName = $ptask->character ? $ptask->character->name . '：' : '(キャラクターなし)：';
            return [$ptask->id => $characterName . $ptask->name];
        });

        $characterParentTaskIds = $potentialParentTasks
            ->whereNotNull('character_id')
            ->pluck('id')
            ->all();

        // ★ 担当者候補を「アクティブ」なユーザーに限定し、IDと名前のペアで取得
        $assigneeOptions = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');
        $selectedAssignees = old('assignees', []); // バリデーション失敗時のための選択済み担当者

        return view('tasks.create', compact('project', 'parentTask', 'processTemplates', 'parentTaskOptions', 'characterParentTaskIds', 'assigneeOptions', 'selectedAssignees'));
    }

    public function store(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create', Task::class);

        $taskTypeInput = $request->input('is_milestone_or_folder', 'task');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'character_id' => ['nullable', Rule::exists('characters', 'id')->where('project_id', $project->id)],
            'start_date' => [
                Rule::requiredIf(fn() => in_array($taskTypeInput, ['milestone', 'task'])),
                'nullable',
                'date'
            ],
            'end_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn() => $taskTypeInput === 'task'),
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
                Rule::requiredIf(fn() => $taskTypeInput === 'task'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('start_date') && $request->filled('end_date') && $request->filled('duration_unit')) {
                        $startDate = Carbon::parse($request->input('start_date'));
                        $endDate = Carbon::parse($request->input('end_date'));
                        $diffInMinutes = $startDate->diffInMinutes($endDate);
                        $durationValue = (float)$value;
                        $durationUnit = $request->input('duration_unit');
                        $inputDurationInMinutes = 0;
                        switch ($durationUnit) {
                            case 'days':
                                $inputDurationInMinutes = $durationValue * 8 * 60;
                                break;
                            case 'hours':
                                $inputDurationInMinutes = $durationValue * 60;
                                break;
                            case 'minutes':
                                $inputDurationInMinutes = $durationValue;
                                break;
                        }
                        if (round($inputDurationInMinutes) > $diffInMinutes) {
                            $fail('工数が開始日時と終了日時の期間を超えています。');
                        }
                    }
                },
            ],
            'duration_unit' => [
                'nullable',
                'string',
                Rule::in(['minutes', 'hours', 'days']),
                Rule::requiredIf(fn() => $taskTypeInput === 'task' && $request->filled('duration_value')),
            ],
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
            'parent_id' => ['nullable', Rule::exists('tasks', 'id')->where('is_folder', false)],
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])],
            'apply_individual_to_all_characters' => 'nullable|boolean',
            'apply_to_all_character_siblings_of_parent' => 'nullable|boolean',
        ], [
            'duration_value.required_if' => '工程の場合、工数は必須です。',
            'duration_value.min' => '工数には0以上の値を入力してください。',
        ]);

        $isFolder = $taskTypeInput === 'folder';
        $isMilestone = $taskTypeInput === 'milestone';
        $isTodoTask = $taskTypeInput === 'todo_task';

        $applyToAllCharacters = $request->boolean('apply_individual_to_all_characters') && !$isFolder && !$request->filled('parent_id');
        $applyToCharacterSiblings = $request->boolean('apply_to_all_character_siblings_of_parent') && !$isFolder && $request->filled('parent_id');

        $baseTaskData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_milestone' => $isMilestone,
            'is_folder' => $isFolder,
            'progress' => 0,
            'status' => $validated['status'] ?? 'not_started',
        ];

        if ($isFolder || $isTodoTask) {
            $baseTaskData['start_date'] = null;
            $baseTaskData['end_date'] = null;
            $baseTaskData['duration'] = null;
            $baseTaskData['progress'] = $isFolder ? null : 0;
            $baseTaskData['status'] = $isFolder ? null : ($validated['status'] ?? 'not_started');
        } elseif ($isMilestone) {
            $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
            $baseTaskData['start_date'] = $startDate;
            $baseTaskData['end_date'] = $startDate ? $startDate->copy() : null;
            $baseTaskData['duration'] = 0;
        } else {
            $durationValue = (float)$validated['duration_value'];
            $durationUnit = $validated['duration_unit'];
            $calculatedDurationInMinutes = 0;
            switch ($durationUnit) {
                case 'days':
                    $calculatedDurationInMinutes = $durationValue * 8 * 60;
                    break;
                case 'hours':
                    $calculatedDurationInMinutes = $durationValue * 60;
                    break;
                case 'minutes':
                    $calculatedDurationInMinutes = $durationValue;
                    break;
            }
            $baseTaskData['start_date'] = Carbon::parse($validated['start_date']);
            $baseTaskData['end_date'] = Carbon::parse($validated['end_date']);
            $baseTaskData['duration'] = $calculatedDurationInMinutes;
        }

        $createdTasks = []; // ▼▼▼ 作成されたタスクを格納する配列を初期化 ▼▼▼
        $message = '工程が作成されました。';
        $createdTasksCount = 0;

        if ($applyToAllCharacters && $project->characters()->exists()) {
            foreach ($project->characters as $character) {
                $taskDataForCharacter = $baseTaskData;
                $taskDataForCharacter['character_id'] = $character->id;
                $task = new Task($taskDataForCharacter);
                $project->tasks()->save($task);
                $createdTasks[] = $task; // ★ 作成したタスクを配列に追加
                $createdTasksCount++;
            }
            if ($createdTasksCount > 0) {
                $message = "工程が {$createdTasksCount} 件のキャラクターに作成されました。";
            }
        } elseif ($applyToCharacterSiblings && $request->filled('parent_id')) {
            $originalParentId = $request->input('parent_id');
            $originalParentTask = Task::with('character')->find($originalParentId);

            if ($originalParentTask && !$originalParentTask->is_folder) {
                $taskDataForOriginalParent = $baseTaskData;
                $taskDataForOriginalParent['parent_id'] = $originalParentTask->id;
                $taskDataForOriginalParent['character_id'] = $originalParentTask->character_id;
                $mainCreatedTask = new Task($taskDataForOriginalParent);
                $project->tasks()->save($mainCreatedTask);
                $createdTasks[] = $mainCreatedTask; // ★ 作成したタスクを配列に追加
                $createdTasksCount++;

                if ($originalParentTask->character_id) {
                    $siblingCharacters = $project->characters()->where('id', '!=', $originalParentTask->character_id)->get();
                    $additionalCreatedCount = 0;
                    foreach ($siblingCharacters as $siblingCharacter) {
                        $siblingParentTask = Task::where('project_id', $project->id)->where('character_id', $siblingCharacter->id)->where('name', $originalParentTask->name)->where('is_folder', false)->first();
                        if ($siblingParentTask) {
                            $taskDataForSibling = $baseTaskData;
                            $taskDataForSibling['parent_id'] = $siblingParentTask->id;
                            $taskDataForSibling['character_id'] = $siblingCharacter->id;
                            $task = new Task($taskDataForSibling);
                            $project->tasks()->save($task);
                            $createdTasks[] = $task; // ★ 作成したタスクを配列に追加
                            $additionalCreatedCount++;
                        }
                    }
                    if ($additionalCreatedCount > 0) {
                        $message = "工程が作成され、さらに {$additionalCreatedCount} 件の関連キャラクターの同名親工程にも作成されました。";
                    }
                }
            }
        } else {
            $taskData = $baseTaskData;
            $parentId = $request->input('parent_id');
            $taskData['parent_id'] = $parentId;
            if ($parentId) {
                $parentTaskForChar = Task::find($parentId);
                $taskData['character_id'] = $parentTaskForChar ? $parentTaskForChar->character_id : null;
            } else {
                $taskData['character_id'] = ($isFolder) ? null : ($validated['character_id'] ?? null);
            }
            $task = new Task($taskData);
            $project->tasks()->save($task);
            $createdTasks[] = $task; // ★ 作成したタスクを配列に追加
            $createdTasksCount++;
        }

        if ($createdTasksCount === 0 && $isFolder) {
            $taskData = $baseTaskData;
            $taskData['parent_id'] = $request->input('parent_id');
            $taskData['character_id'] = null;
            $task = new Task($taskData);
            $project->tasks()->save($task);
            // フォルダには担当者をつけないので配列には追加しない
            $message = 'フォルダが作成されました。';
        }

        // ▼▼▼【ここが修正の核心】作成された全てのタスクに担当者を同期 ▼▼▼
        if (!empty($createdTasks) && isset($validated['assignees'])) {
            foreach ($createdTasks as $task) {
                if (!$task->is_folder) { // フォルダには担当者を割り当てない
                    $task->assignees()->sync($validated['assignees']);
                }
            }
        }

        return redirect()->route('projects.show', $project)->with('success', $message);
    }
    public function edit(Project $project, Task $task)
    {
        $this->authorize('update', $task);
        $project->load(['characters', 'tasks.assignees']); // ★ assigneesをロード

        $files = $task->is_folder ? $task->files()->orderBy('original_name')->get() : collect();

        $descendantAndSelfIds = $task->getAllDescendants()->pluck('id')->push($task->id)->all();

        $potentialParentTasks = $project->tasks()
            ->with('character')
            ->where('is_folder', false)
            ->whereNotIn('id', $descendantAndSelfIds)
            ->orderBy('name')
            ->get();

        $parentTaskOptions = $potentialParentTasks->mapWithKeys(function ($ptask) {
            $characterName = $ptask->character ? $ptask->character->name . '：' : '(キャラクターなし)：';
            return [$ptask->id => $characterName . $ptask->name];
        });

        $characterParentTaskIds = $potentialParentTasks
            ->whereNotNull('character_id')
            ->pluck('id')
            ->all();

        // ★ 担当者候補と選択済み担当者IDを取得
        $assigneeOptions = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');
        $selectedAssignees = old('assignees', $task->assignees->pluck('id')->toArray());

        return view('tasks.edit', compact('project', 'task', 'files', 'parentTaskOptions', 'characterParentTaskIds', 'assigneeOptions', 'selectedAssignees'));
    }


    public function update(Request $request, Project $project, Task $task): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $task);

        $currentTaskType = 'task'; //
        if ($task->is_milestone) $currentTaskType = 'milestone'; //
        elseif ($task->is_folder) $currentTaskType = 'folder'; //
        elseif (!$task->start_date && !$task->end_date && !$task->is_milestone && !$task->is_folder) $currentTaskType = 'todo_task'; //

        // バリデーションルールを修正
        $validated = $request->validate([
            'name' => 'required|string|max:255', //
            'description' => 'nullable|string', //
            'character_id' => ['nullable', Rule::exists('characters', 'id')->where('project_id', $project->id)], //
            'start_date' => [ //
                Rule::requiredIf(fn() => in_array($currentTaskType, ['milestone', 'task'])),
                'nullable',
                'date'
            ],
            'end_date' => [ //
                'nullable',
                'date',
                Rule::requiredIf(fn() => $currentTaskType === 'task'),
                Rule::prohibitedIf(fn() => $currentTaskType === 'milestone'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('start_date') && $request->filled($attribute)) {
                        if (Carbon::parse($value)->lt(Carbon::parse($request->input('start_date')))) {
                            $fail('終了日時は開始日時以降に設定してください。');
                        }
                    }
                },
            ],
            'duration_value' => [ //
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn() => $currentTaskType === 'task'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('start_date') && $request->filled('end_date') && $request->filled('duration_unit')) {
                        $startDate = Carbon::parse($request->input('start_date'));
                        $endDate = Carbon::parse($request->input('end_date'));
                        $diffInMinutes = $startDate->diffInMinutes($endDate);

                        $durationValue = (float)$value;
                        $durationUnit = $request->input('duration_unit');

                        $inputDurationInMinutes = 0;
                        switch ($durationUnit) {
                            case 'days':
                                $inputDurationInMinutes = $durationValue * 8 * 60;
                                break; // 1日8時間で計算
                            case 'hours':
                                $inputDurationInMinutes = $durationValue * 60;
                                break;
                            case 'minutes':
                                $inputDurationInMinutes = $durationValue;
                                break;
                        }

                        if (round($inputDurationInMinutes) > $diffInMinutes) {
                            $fail('工数が開始日時と終了日時の期間を超えています。');
                        }
                    }
                },
            ],
            'duration_unit' => [ //
                'nullable',
                'string',
                Rule::in(['minutes', 'hours', 'days']),
                Rule::requiredIf(fn() => $currentTaskType === 'task' && $request->filled('duration_value')),
            ],
            'assignees' => 'nullable|array', //
            'assignees.*' => 'exists:users,id', //
            'parent_id' => 'nullable|exists:tasks,id', //
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])], //
            'apply_edit_to_all_characters_same_name' => 'nullable|boolean', //
        ], [
            'end_date.required_if' => '工程の場合、終了日時は必須です。',
            'duration_value.required_if' => '工程の場合、工数は必須です。',
            'assignees.*.exists' => '選択された担当者が存在しません。',
        ]);

        $taskDataForCurrent = [
            'name' => $validated['name'], //
            'description' => $validated['description'], //
            'parent_id' => $request->filled('parent_id') ? $validated['parent_id'] : $task->parent_id, //
        ];

        if ($request->filled('parent_id') && $validated['parent_id']) { //
            $parentTaskForChar = Task::find($validated['parent_id']);
            $taskDataForCurrent['character_id'] = $parentTaskForChar->character_id;
        } elseif ($currentTaskType !== 'folder') {
            $taskDataForCurrent['character_id'] = (array_key_exists('character_id', $validated) && $validated['character_id'] !== '') ? $validated['character_id'] : $task->character_id; //
        } else {
            $taskDataForCurrent['character_id'] = null;
        }

        if ($currentTaskType === 'folder') {
            $taskDataForCurrent['status'] = null;
            $taskDataForCurrent['start_date'] = $task->start_date;
            $taskDataForCurrent['end_date'] = $task->end_date;
            $taskDataForCurrent['duration'] = $task->duration;
        } elseif ($currentTaskType === 'milestone') {
            $startDate = $request->filled('start_date') ? Carbon::parse($validated['start_date']) : $task->start_date; //
            $taskDataForCurrent['start_date'] = $startDate;
            $taskDataForCurrent['end_date'] = $startDate ? $startDate->copy() : null;
            $taskDataForCurrent['duration'] = 0;
            $taskDataForCurrent['status'] = $validated['status'] ?? $task->status; //
        } elseif ($currentTaskType === 'todo_task') {
            $taskDataForCurrent['start_date'] = null;
            $taskDataForCurrent['end_date'] = null;
            $taskDataForCurrent['duration'] = null;
            $taskDataForCurrent['status'] = $validated['status'] ?? $task->status; //
        } else { // 'task' の場合
            $startDate = Carbon::parse($validated['start_date']); //
            $endDate = Carbon::parse($validated['end_date']); //
            $durationValue = (float)$validated['duration_value']; //
            $durationUnit = $validated['duration_unit']; //

            $calculatedDurationInMinutes = 0;
            switch ($durationUnit) {
                case 'days':
                    $calculatedDurationInMinutes = $durationValue * 8 * 60;
                    break;
                case 'hours':
                    $calculatedDurationInMinutes = $durationValue * 60;
                    break;
                case 'minutes':
                    $calculatedDurationInMinutes = $durationValue;
                    break;
            }
            $taskDataForCurrent['start_date'] = $startDate;
            $taskDataForCurrent['end_date'] = $endDate;
            $taskDataForCurrent['duration'] = $calculatedDurationInMinutes;
            $taskDataForCurrent['status'] = $validated['status'] ?? $task->status; //
        }

        $task->fill($taskDataForCurrent);
        $task->save();

        if (array_key_exists('assignees', $validated)) { //
            $task->assignees()->sync($validated['assignees']); //
        }

        $message = '工程が更新されました。';

        $applyEditToAllCharactersSameName = $request->boolean('apply_edit_to_all_characters_same_name')
            && $task->character_id
            && $currentTaskType !== 'folder'
            && !$request->filled('parent_id');

        if ($applyEditToAllCharactersSameName) {
            $attributesToSync = [
                'name' => $task->name,
                'description' => $task->description,
                'start_date' => $task->start_date,
                'end_date' => $task->end_date,
                'duration' => $task->duration,
                'status' => $task->status,
                'progress' => $task->progress,
                'parent_id' => $task->parent_id,
            ];
            $otherTasks = Task::where('project_id', $project->id)
                ->where('name', $task->name)
                ->where('id', '!=', $task->id)
                ->whereNotNull('character_id')
                ->where('character_id', '!=', $task->character_id)
                ->where('is_milestone', $task->is_milestone)
                ->where('is_folder', $task->is_folder)
                ->get();
            $updatedCount = 0;
            foreach ($otherTasks as $otherTask) {
                $dataForOtherTask = $attributesToSync;
                $otherTask->fill($dataForOtherTask);
                $otherTask->save();
                // ★ 同名工程にも担当者を同期
                if (array_key_exists('assignees', $validated)) {
                    $otherTask->assignees()->sync($validated['assignees']);
                }
                $updatedCount++;
            }
            if ($updatedCount > 0) {
                $message = '工程が更新され、他のキャラクターの同名工程 (' . $updatedCount . '件) にも反映されました。';
            }
        }
        return redirect()->route('projects.show', $project)->with('success', $message);
    }
    /**
     * 工程を削除
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $task);

        $this->deleteTaskAndChildren($task);
        return redirect()->route('projects.show', $project)->with('success', '工程が削除されました。');
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
            'assignees' => 'nullable|array',
            'assignees.*' => 'sometimes|integer|exists:users,id'
        ]);

        $task->assignees()->sync($validated['assignees'] ?? []);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($task)
            ->log('担当者が更新されました。');

        // 更新後の担当者バッジのHTMLを返す
        $assigneesHtml = view('tasks.partials.assignee-badges', ['assignees' => $task->fresh()->assignees])->render();

        return response()->json([
            'success' => true,
            'message' => '担当者が更新されました。',
            'assigneesHtml' => $assigneesHtml
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

    public function storeFromTemplate(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create', Task::class);
        Log::info('storeFromTemplateメソッド呼び出し', $request->all()); // 日本語化

        $validated = $request->validate([
            'process_template_id' => 'required|exists:process_templates,id',
            'template_start_date' => 'required|date_format:Y-m-d\TH:i,Y-m-d\TH:i:s,Y-m-d',
            'parent_id_for_template' => 'nullable|exists:tasks,id',
            'character_id_for_template' => [
                // Rule::requiredIf(!$request->boolean('apply_template_to_all_characters')),
                'nullable',
                'exists:characters,id',
                function ($attribute, $value, $fail) use ($project) {
                    if ($value && !$project->characters()->where('id', $value)->exists()) {
                        $fail('選択されたキャラクターはこの案件に所属していません。');
                    }
                }
            ],
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
            'apply_template_to_all_characters' => 'nullable|boolean',
        ], [
            // 'character_id_for_template.required' => '「すべてのキャラクターへ適用」をチェックしない場合、所属先キャラクターの選択は必須です。',
            'working_hours_start.required' => '稼働開始時刻は必須です。',
            'working_hours_start.date_format' => '稼働開始時刻はHH:MM形式で入力してください。',
            'working_hours_end.required' => '稼働終了時刻は必須です。',
            'working_hours_end.date_format' => '稼働終了時刻はHH:MM形式で入力してください。',
            'working_hours_end.after' => '稼働終了時刻は開始時刻より後に設定してください。',
        ]);
        Log::info('storeFromTemplateのバリデーション通過'); // 日本語化

        $template = ProcessTemplate::with('items')->findOrFail($validated['process_template_id']);

        $carbonWorkDayStartTime = Carbon::parse($validated['working_hours_start']);
        $carbonWorkDayEndTime = Carbon::parse($validated['working_hours_end']);

        $workableMinutesPerDay = $carbonWorkDayEndTime->diffInMinutes($carbonWorkDayStartTime, true);

        if ($workableMinutesPerDay <= 0 && !($carbonWorkDayStartTime->eq($carbonWorkDayEndTime))) {
            Log::error('1日の稼働時間が正ではありません', [ // 日本語化
                'start_time' => $validated['working_hours_start'],
                'end_time' => $validated['working_hours_end'],
                'calculated_minutes' => $workableMinutesPerDay
            ]);
            return redirect()->back()->withErrors(['working_hours_end' => '稼働終了時刻は開始時刻より後に適切に設定し、0分以上の稼働時間を確保してください。'])->withInput();
        }
        Log::info('1日あたりの稼働分数', ['minutes' => $workableMinutesPerDay]); // 日本語化

        $applyToAllCharacters = $request->boolean('apply_template_to_all_characters');
        $parentTaskIdForTemplate = $validated['parent_id_for_template'] ?? null;
        $totalCreatedTasksCount = 0;
        $skippedTasksCount = 0;
        $firstCreatedTaskNameForLog = null;


        $charactersToProcess = collect();
        if ($applyToAllCharacters) {
            if ($project->characters()->exists()) {
                $charactersToProcess = $project->characters;
            } else {
                $charactersToProcess = collect([null]);
            }
        } else {
            $selectedCharacterId = $validated['character_id_for_template'] ?? null;
            if ($selectedCharacterId) {
                $character = $project->characters()->find($selectedCharacterId);
                if ($character) {
                    $charactersToProcess = collect([$character]);
                } else {
                    return redirect()->back()->withErrors(['character_id_for_template' => '指定されたキャラクターが見つかりません。'])->withInput();
                }
            } else {
                $charactersToProcess = collect([null]);
            }
        }

        if ($charactersToProcess->isEmpty()) {
            return redirect()->route('projects.show', $project)->with('info', 'テンプレートを適用する対象のキャラクターがいません。');
        }

        foreach ($charactersToProcess as $characterInstance) {
            $characterIdForThisIteration = $characterInstance ? $characterInstance->id : null;

            $currentTaskProcessingDateTime = Carbon::parse($validated['template_start_date']);
            Log::info('反復処理の初期処理日時（入力値）', ['character_id' => $characterIdForThisIteration, 'datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]); // 日本語化

            $parsedInputDate = Carbon::parse($validated['template_start_date']);
            if (strpos($validated['template_start_date'], 'T') === false && strpos($validated['template_start_date'], ' ') === false) {
                $currentTaskProcessingDateTime->setTimeFrom($carbonWorkDayStartTime);
                Log::info('入力に時刻なし、稼働開始時刻に設定', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]); // 日本語化
            } elseif ($currentTaskProcessingDateTime->copy()->setTime($currentTaskProcessingDateTime->hour, $currentTaskProcessingDateTime->minute)
                ->lt($parsedInputDate->copy()->setTimeFrom($carbonWorkDayStartTime))
            ) {
                $currentTaskProcessingDateTime->setTimeFrom($carbonWorkDayStartTime);
                Log::info('入力時刻が稼働開始より前のため調整', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]); // 日本語化
            }

            if (
                $currentTaskProcessingDateTime->copy()->setTime($currentTaskProcessingDateTime->hour, $currentTaskProcessingDateTime->minute)
                ->gte($currentTaskProcessingDateTime->copy()->setTimeFrom($carbonWorkDayEndTime)) && $workableMinutesPerDay > 0
            ) {
                $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
                Log::info('入力時刻が稼働終了より後のため翌営業日開始に移動', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]); // 日本語化
            }

            while ($currentTaskProcessingDateTime->isWeekend()) {
                $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
                Log::info('週末をスキップ、新日時', ['datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]); // 日本語化
            }
            Log::info('反復処理の最終的な初期処理日時', ['character_id' => $characterIdForThisIteration, 'datetime' => $currentTaskProcessingDateTime->format('Y-m-d H:i:s')]); // 日本語化

            foreach ($template->items()->orderBy('order')->get() as $itemIndex => $item) {
                Log::info("テンプレート項目処理中: {$item->name} (順序: {$item->order}), キャラクターID: {$characterIdForThisIteration}"); // 日本語化
                $taskDurationInMinutes = $item->default_duration ?? 0;
                Log::info("項目の工数（分）: {$taskDurationInMinutes}"); // 日本語化

                $actualTaskStartDate = $currentTaskProcessingDateTime->copy();
                if ($actualTaskStartDate->copy()->setTime($actualTaskStartDate->hour, $actualTaskStartDate->minute)
                    ->lt($actualTaskStartDate->copy()->setTimeFrom($carbonWorkDayStartTime))
                ) {
                    $actualTaskStartDate->setTimeFrom($carbonWorkDayStartTime);
                } elseif (
                    $actualTaskStartDate->copy()->setTime($actualTaskStartDate->hour, $actualTaskStartDate->minute)
                    ->gte($actualTaskStartDate->copy()->setTimeFrom($carbonWorkDayEndTime)) && $workableMinutesPerDay > 0
                ) {
                    $actualTaskStartDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                }
                while ($actualTaskStartDate->isWeekend()) {
                    $actualTaskStartDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                }
                Log::info("項目「{$item->name}」の実際の開始日時: " . $actualTaskStartDate->format('Y-m-d H:i:s')); // 日本語化


                $remainingDurationForThisTask = $taskDurationInMinutes;
                $taskCalculatedEndDate = $actualTaskStartDate->copy();

                if ($taskDurationInMinutes <= 0) {
                    $taskCalculatedEndDate = $actualTaskStartDate->copy();
                    Log::info("タスク工数が0のため、終了日時を開始日時と同じに設定: " . $taskCalculatedEndDate->format('Y-m-d H:i:s')); // 日本語化
                } else {
                    if ($workableMinutesPerDay <= 0) {
                        Log::warning("1日の稼働時間が0分のため、工数のあるタスク「{$item->name}」（工数: {$taskDurationInMinutes}分）を処理できません。終了日時を開始日時と同じにします。"); // 日本語化
                        $taskCalculatedEndDate = $actualTaskStartDate->copy();
                        $remainingDurationForThisTask = 0;
                    }
                    while ($remainingDurationForThisTask > 0) {
                        Log::info("「{$item->name}」の残り工数: {$remainingDurationForThisTask} 分。ループ開始時の計算上の終了日時: " . $taskCalculatedEndDate->format('Y-m-d H:i:s')); // 日本語化

                        $todayWorkEnd = $taskCalculatedEndDate->copy()->setTimeFrom($carbonWorkDayEndTime);
                        $minutesAvailableToday = $taskCalculatedEndDate->diffInMinutes($todayWorkEnd, false);
                        Log::info("本日利用可能な分数 ({$taskCalculatedEndDate->format('Y-m-d')} の {$taskCalculatedEndDate->format('H:i')} から {$todayWorkEnd->format('H:i')} まで): {$minutesAvailableToday}"); // 日本語化

                        if ($minutesAvailableToday <= 0) {
                            $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                            while ($taskCalculatedEndDate->isWeekend()) {
                                $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                            }
                            Log::info("本日の残り時間なし、または稼働終了後に開始。翌営業日の開始時刻に移動: " . $taskCalculatedEndDate->format('Y-m-d H:i:s')); // 日本語化
                            continue;
                        }

                        if ($remainingDurationForThisTask <= $minutesAvailableToday) {
                            $taskCalculatedEndDate->addMinutes($remainingDurationForThisTask);
                            $remainingDurationForThisTask = 0;
                            Log::info("タスクは本日中に完了。新しい計算上の終了日時: " . $taskCalculatedEndDate->format('Y-m-d H:i:s')); // 日本語化
                        } else {
                            $taskCalculatedEndDate->addMinutes($minutesAvailableToday);
                            $remainingDurationForThisTask -= $minutesAvailableToday;
                            Log::info("タスクは本日中に完了せず。残り工数: {$remainingDurationForThisTask}。本日の稼働終了時刻に到達: " . $taskCalculatedEndDate->format('Y-m-d H:i:s')); // 日本語化

                            $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                            while ($taskCalculatedEndDate->isWeekend()) {
                                $taskCalculatedEndDate->addDay()->setTimeFrom($carbonWorkDayStartTime);
                            }
                            Log::info("残りの工数のため、翌営業日の開始時刻に移動: " . $taskCalculatedEndDate->format('Y-m-d H:i:s')); // 日本語化
                        }
                    }
                }

                $taskExists = Task::where('project_id', $project->id)
                    ->where('name', $item->name)
                    ->where('character_id', $characterIdForThisIteration)
                    ->where('parent_id', $parentTaskIdForTemplate)
                    ->exists();

                if ($taskExists) {
                    $skippedTasksCount++;
                    Log::info("既存タスクのため作成をスキップ: '{$item->name}'", [ // 日本語化
                        'character_id' => $characterIdForThisIteration,
                        'parent_id' => $parentTaskIdForTemplate
                    ]);
                } else {
                    $taskData = [
                        'name' => $item->name,
                        'description' => null,
                        'parent_id' => $parentTaskIdForTemplate,
                        'character_id' => $characterIdForThisIteration,
                        'is_milestone' => false,
                        'is_folder' => false,
                        'progress' => 0,
                        'status' => 'not_started',
                        'start_date' => $actualTaskStartDate,
                        'duration' => $taskDurationInMinutes,
                        'end_date' => $taskCalculatedEndDate,
                    ];
                    Log::info("作成するタスクデータ: {$item->name}", $taskData); // 日本語化

                    $createdTask = $project->tasks()->create($taskData);
                    if ($totalCreatedTasksCount === 0) {
                        $firstCreatedTaskNameForLog = $createdTask->name;
                    }
                    $totalCreatedTasksCount++;
                }

                $currentTaskProcessingDateTime = $taskCalculatedEndDate->copy();
                if (
                    $currentTaskProcessingDateTime->copy()->setTime($currentTaskProcessingDateTime->hour, $currentTaskProcessingDateTime->minute)
                    ->gte($currentTaskProcessingDateTime->copy()->setTimeFrom($carbonWorkDayEndTime)) && $workableMinutesPerDay > 0
                ) {
                    $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
                }
                while ($currentTaskProcessingDateTime->isWeekend()) {
                    $currentTaskProcessingDateTime->addDay()->setTimeFrom($carbonWorkDayStartTime);
                }
                Log::info("項目「{$item->name}」のループ終了。次のタスクの処理日時: " . $currentTaskProcessingDateTime->format('Y-m-d H:i:s')); // 日本語化
            }
        }

        $message = "{$totalCreatedTasksCount} 件の工程を一括作成しました。";
        if ($skippedTasksCount > 0) {
            $message .= " ({$skippedTasksCount} 件は既に存在したためスキップされました)";
        }

        if ($totalCreatedTasksCount > 0 || $skippedTasksCount > 0) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($project)
                ->withProperties([
                    'template_name' => $template->name,
                    'created_tasks_count' => $totalCreatedTasksCount,
                    'skipped_tasks_count' => $skippedTasksCount,
                    'first_task_name' => $firstCreatedTaskNameForLog,
                    'applied_to_all_characters' => $applyToAllCharacters,
                    'characters_processed_count' => $charactersToProcess->count(),
                ])
                ->log("工程テンプレート「{$template->name}」から {$project->title} に工程が適用されました。");
            Log::info('テンプレート適用のためのアクティビティログ作成完了'); // 日本語化
        }

        return redirect()->route('projects.show', $project)->with('success', $message);
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

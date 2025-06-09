<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Holiday;
use Carbon\Carbon;
use App\Services\TaskService;

class CalendarController extends Controller
{
    /**
     * カレンダービューを表示
     */
    public function index(Request $request, TaskService $taskService)
    {
        $this->authorize('viewAny', Project::class);
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'character_id' => $request->input('character_id', ''),
            'assignee' => $request->input('assignee', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
        ];

        // ▼▼▼ 変更点: tasks.assignees もEager Loadingする ▼▼▼
        $projectsQuery = Project::with(['tasks' => function ($query) {
            $query->with(['character', 'assignees']); // ネストしたリレーションをロード
        }, 'tasks' => function ($query) use ($filters, $taskService) {
            $taskService->applyAssigneeFilter($query, $filters['assignee']);
            $taskService->applyCharacterFilter($query, $filters['character_id']);
            $taskService->applyStatusFilter($query, $filters['status']);
            $taskService->applySearchFilter($query, $filters['search']);
        }]);


        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        $projectsQuery->whereHas('tasks', function ($query) use ($filters, $taskService) {
            $taskService->applyAssigneeFilter($query, $filters['assignee']);
            $taskService->applyCharacterFilter($query, $filters['character_id']);
            $taskService->applyStatusFilter($query, $filters['status']);
            $taskService->applySearchFilter($query, $filters['search']);
        });

        $projects = $projectsQuery->get();

        $events = [];

        foreach ($projects as $project) {
            foreach ($project->tasks as $task) {
                if ($task->is_folder) {
                    continue;
                }

                $isAllDay = true;
                $start = null;
                $end = null;

                if ($task->start_date) {
                    if ($task->start_date->format('H:i:s') === '00:00:00') {
                        $isAllDay = true;
                        $start = $task->start_date->format('Y-m-d');
                        $end = $task->end_date ? $task->end_date->addDay()->format('Y-m-d') : null;
                    } else {
                        $isAllDay = false;
                        $start = $task->start_date->toIso8601String();
                        $end = $task->end_date ? $task->end_date->toIso8601String() : null;
                    }
                }

                $taskColor = $project->color;

                $events[] = [
                    'id' => 'task_' . $task->id,
                    'title' => $task->name,
                    'start' => $start,
                    'end' => $end,
                    'color' => $taskColor,
                    'textColor' => '#ffffff',
                    'allDay' => $isAllDay,
                    'url' => route('projects.tasks.edit', [$project, $task]),
                    'classNames' => [
                        'task-event',
                        $task->is_milestone ? 'milestone-event' : '',
                    ],
                    'extendedProps' => [
                        'type' => $task->is_milestone ? 'milestone' : 'task',
                        'description' => $task->description,
                        // ▼▼▼ 変更点: extendedPropsに担当者名を追加 ▼▼▼
                        'assignee_names' => $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : null,
                        'progress' => $task->progress,
                        'status' => $task->status,
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'project_color' => $project->color,
                        'character_name' => $task->character->name ?? null,
                    ]
                ];
            }
        }

        $startDate = Carbon::now()->subMonths(1)->startOfMonth();
        $endDate = Carbon::now()->addMonths(6)->endOfMonth();

        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();

        foreach ($holidays as $holiday) {
            $events[] = [
                'id' => 'holiday_' . $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date->format('Y-m-d'),
                'end' => $holiday->date->format('Y-m-d'),
                'display' => 'background',
                'color' => '#ffcdd2',
                'allDay' => true,
                'classNames' => ['holiday-event'],
                'extendedProps' => [
                    'type' => 'holiday'
                ]
            ];
        }

        $allAssignees = Task::whereNotNull('assignee')
            ->distinct()
            ->pluck('assignee')
            ->sort()
            ->values();

        $statusOptions = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留中',
            'cancelled' => 'キャンセル',
        ];

        $allProjects = Project::orderBy('title')->get();

        $charactersForFilter = collect();
        if (!empty($filters['project_id'])) {
            $projectWithChars = Project::with('characters')->find($filters['project_id']);
            if ($projectWithChars) {
                $charactersForFilter = $projectWithChars->characters;
            }
        }


        return view('calendar.index', [
            'events' => json_encode($events),
            'filters' => $filters,
            'allProjects' => $allProjects,
            'charactersForFilter' => $charactersForFilter,
            'allAssignees' => $allAssignees,
            'statusOptions' => $statusOptions,
        ]);
    }
}

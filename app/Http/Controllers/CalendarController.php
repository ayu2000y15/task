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
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'character_id' => $request->input('character_id', ''), // ★ 追加
            'assignee' => $request->input('assignee', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
        ];

        // TaskService を使った絞り込みを適用 (character_id も考慮)
        $projectsQuery = Project::with(['tasks' => function ($query) use ($filters) {
            app(TaskService::class)->applyAssigneeFilter($query, $filters['assignee']);
            app(TaskService::class)->applyCharacterFilter($query, $filters['character_id']); // Character filter
            app(TaskService::class)->applyStatusFilter($query, $filters['status']);
            app(TaskService::class)->applySearchFilter($query, $filters['search']);
        }]);


        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        $projects = $projectsQuery->get();

        $events = [];

        foreach ($projects as $project) {
            $events[] = [
                'id' => 'project_' . $project->id,
                'title' => $project->title,
                'start' => $project->start_date->format('Y-m-d'),
                'end' => $project->end_date->addDay()->format('Y-m-d'),
                'color' => $project->color,
                'textColor' => '#ffffff',
                'allDay' => true,
                'url' => route('projects.show', $project),
                'classNames' => ['project-event'],
                'extendedProps' => [
                    'type' => 'project',
                    'description' => $project->description
                ]
            ];

            foreach ($project->tasks as $task) {
                if ($task->is_folder) {
                    continue;
                }

                $taskColor = $task->color;
                switch ($task->status) {
                    case 'completed':
                        $taskColor = '#28a745';
                        break;
                    case 'in_progress':
                        $taskColor = '#007bff';
                        break;
                    case 'on_hold':
                        $taskColor = '#ffc107';
                        break;
                    case 'cancelled':
                        $taskColor = '#dc3545';
                        break;
                }

                $events[] = [
                    'id' => 'task_' . $task->id,
                    'title' => $task->name,
                    'start' => $task->start_date ? $task->start_date->format('Y-m-d') : null,
                    'end' => $task->end_date ? $task->end_date->addDay()->format('Y-m-d') : null,
                    'color' => $taskColor,
                    'textColor' => '#ffffff',
                    'allDay' => true,
                    'url' => route('projects.tasks.edit', [$project, $task]),
                    'classNames' => [
                        'task-event',
                        $task->is_milestone ? 'milestone-event' : '',
                    ],
                    'extendedProps' => [
                        'type' => $task->is_milestone ? 'milestone' : 'task',
                        'description' => $task->description,
                        'assignee' => $task->assignee,
                        'progress' => $task->progress,
                        'status' => $task->status,
                        'project_id' => $project->id,
                        'project_title' => $project->title
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
                'color' => '#ffe6e6',
                'textColor' => '#cc0000',
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
        // フィルター用にキャラクター一覧も取得
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
            'charactersForFilter' => $charactersForFilter, // ★ 追加
            'allAssignees' => $allAssignees,
            'statusOptions' => $statusOptions,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use App\Services\TaskService;
use App\Models\WorkShift;

class CalendarController extends Controller
{
    /**
     * カレンダービューを表示
     */
    public function index(Request $request, TaskService $taskService)
    {
        $this->authorize('viewAny', Project::class);

        // ... 既存のフィルター処理（変更なし） ...
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'character_id' => $request->input('character_id', ''),
            'assignee' => $request->input('assignee', ''),
            'assignee_id' => $request->input('assignee_id', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
        ];


        // ... 既存のタスクイベント生成処理（変更なし） ...
        // (projectsQuery, eventsの生成など)
        $taskQueryLogic = function ($query) use ($filters, $taskService) {
            $taskService->applyAssigneeFilter($query, $filters['assignee']);
            $taskService->applyCharacterFilter($query, $filters['character_id']);
            $taskService->applyStatusFilter($query, $filters['status']);
            $taskService->applySearchFilter($query, $filters['search']);

            if (!empty($filters['assignee_id'])) {
                $query->whereHas('assignees', function ($subQuery) use ($filters) {
                    $subQuery->where('users.id', $filters['assignee_id']);
                });
            }

            $query->where('tasks.status', '!=', 'completed');
        };

        $projectsQuery = Project::with(['tasks' => function ($query) use ($taskQueryLogic) {
            $query->with(['character', 'assignees'])->where($taskQueryLogic);
        }])->whereHas('tasks', $taskQueryLogic);

        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        $projects = $projectsQuery->get();
        $events = [];

        foreach ($projects as $project) {
            foreach ($project->tasks as $task) {
                if ($task->is_folder) continue;
                $isAllDay = $task->start_date ? ($task->start_date->format('H:i:s') === '00:00:00') : true;
                $start = $task->start_date ? ($isAllDay ? $task->start_date->format('Y-m-d') : $task->start_date->toIso8601String()) : null;
                $end = $task->end_date ? ($isAllDay ? $task->end_date->addDay()->format('Y-m-d') : $task->end_date->toIso8601String()) : null;
                $events[] = [
                    'id' => 'task_' . $task->id,
                    'title' => $task->name,
                    'start' => $start,
                    'end' => $end,
                    'color' => $project->color,
                    'textColor' => '#ffffff',
                    'allDay' => $isAllDay,
                    'url' => route('projects.tasks.edit', [$project, $task]),
                    'classNames' => ['task-event', $task->is_milestone ? 'milestone-event' : ''],
                    'extendedProps' => [
                        'type' => $task->is_milestone ? 'milestone' : 'task',
                        'description' => $task->description,
                        'assignee_names' => $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : null,
                        'progress' => $task->progress,
                        'status' => $task->status,
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'project_color' => $project->color,
                        'character_name' => optional($task->character)->name,
                    ]
                ];
            }
        }


        // ... 既存の祝日イベント生成処理 ...
        $startDate = Carbon::now()->subMonths(3)->startOfMonth();
        $endDate = Carbon::now()->addMonths(6)->endOfMonth();

        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();

        foreach ($holidays as $holiday) {
            // ▼▼▼【変更】祝日を背景でなく、通常のイベントとして表示するように変更します ▼▼▼
            $events[] = [
                'id' => 'holiday_' . $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => '#ef4444', // 赤色
                'borderColor' => '#ef4444',
                'textColor' => '#ffffff',
                'classNames' => ['holiday-event'],
                'extendedProps' => ['type' => 'holiday']
            ];
        }

        // ▼▼▼【ここから休日データを追加】▼▼▼
        $userShifts = WorkShift::with('user')
            ->whereIn('type', ['full_day_off', 'am_off', 'pm_off']) // 休日タイプのシフトのみ取得
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        foreach ($userShifts as $shift) {
            if ($shift->user) {
                $eventTitle = '';
                $eventColor = '';

                switch ($shift->type) {
                    case 'full_day_off':
                        $eventTitle = '休み(全日): ' . $shift->user->name;
                        $eventColor = '#16a34a'; // 緑色
                        break;
                    case 'am_off':
                        $eventTitle = '休み(午前): ' . $shift->user->name;
                        $eventColor = '#f97316'; // オレンジ色
                        break;
                    case 'pm_off':
                        $eventTitle = '休み(午後): ' . $shift->user->name;
                        $eventColor = '#ca8a04'; // 黄色
                        break;
                }

                $events[] = [
                    'id' => 'usershift_' . $shift->id,
                    'title' => $eventTitle,
                    'start' => $shift->date->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => $eventColor,
                    'borderColor' => $eventColor,
                    'textColor' => '#ffffff',
                    'classNames' => ['holiday-event'],
                    'extendedProps' => [
                        'type' => 'holiday',
                        'description' => $shift->name // 登録された休日の名称
                    ]
                ];
            }
        }

        // ... 既存のフィルター用データ取得処理（変更なし） ...
        $allAssignees = User::whereHas('tasks')->orderBy('name')->get()->pluck('name', 'id');
        $statusOptions = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'on_hold' => '一時停止中',
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Character;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\TaskService;

class GanttChartController extends Controller
{
    /**
     * ガントチャートを表示
     */
    public function index(Request $request, TaskService $taskService)
    {
        $filters = [
            'project_id' => $request->input('project_id', ''),
            'character_id' => $request->input('character_id', ''),
            'assignee' => $request->input('assignee', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
            'start_date' => $request->input('start_date', ''),
            'end_date' => $request->input('end_date', ''),
        ];

        $projectsQuery = Project::query();

        // プロジェクトIDで絞り込み
        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        // タスク関連のフィルターロジックをクロージャにまとめる
        $taskFilterLogic = function ($query) use ($filters, $taskService) {
            if (!empty($filters['character_id'])) {
                if ($filters['character_id'] === 'none') {
                    $query->whereNull('character_id');
                } else {
                    $query->where('character_id', $filters['character_id']);
                }
            }
            $taskService->applyAssigneeFilter($query, $filters['assignee']);
            $taskService->applyStatusFilter($query, $filters['status']);
            $taskService->applySearchFilter($query, $filters['search']);
            $taskService->applyDateRangeFilter($query, $filters['start_date'], $filters['end_date']);
        };

        // フィルター条件に合致するタスクを持つプロジェクトのみを対象にする
        $projectsQuery->whereHas('tasks', $taskFilterLogic);

        $projects = $projectsQuery->with([
            'characters' => function ($query) use ($taskFilterLogic) {
                $query->whereHas('tasks', $taskFilterLogic)->orderBy('name');
            },
            'characters.tasks' => function ($query) use ($taskFilterLogic) {
                $taskFilterLogic($query);
            },
            'tasks' => $taskFilterLogic,
        ])->get();

        $startDate = null;
        $endDate = null;

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date']);
            $endDate = Carbon::parse($filters['end_date']);
        } else {
            foreach ($projects as $project) {
                if (!$startDate || $project->start_date->lt($startDate)) {
                    $startDate = $project->start_date->copy();
                }

                if (!$endDate || $project->end_date->gt($endDate)) {
                    $endDate = $project->end_date->copy();
                }

                // フィルター後のタスクの期間も考慮
                foreach ($project->tasks as $task) {
                    if ($task->start_date && (!$startDate || $task->start_date->lt($startDate))) {
                        $startDate = $task->start_date->copy();
                    }
                    if ($task->end_date && (!$endDate || $task->end_date->gt($endDate))) {
                        $endDate = $task->end_date->copy();
                    }
                }
            }
        }

        if (!$startDate) {
            $startDate = Carbon::today()->subDays(7);
        }

        if (!$endDate) {
            $endDate = Carbon::today()->addMonths(1);
        }

        if ($startDate->gt($endDate)) {
            $startDate = $endDate->copy()->subDays(30);
        }

        $dates = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dates[] = [
                'date' => $date->copy(),
                'day' => $date->day,
                'is_weekend' => $date->isWeekend(),
                'is_saturday' => $date->isSaturday(),
                'is_sunday' => $date->isSunday(),
            ];
        }

        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('date');

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
        $characters = collect();
        if (!empty($filters['project_id'])) {
            $projectWithChars = Project::with('characters')->find($filters['project_id']);
            if ($projectWithChars) {
                $characters = $projectWithChars->characters;
            }
        }

        $today = Carbon::today();

        return view('gantt.index', [
            'projects' => $projects,
            'dates' => $dates,
            'holidays' => $holidays,
            'filters' => $filters,
            'allAssignees' => $allAssignees,
            'statusOptions' => $statusOptions,
            'allProjects' => $allProjects,
            'characters' => $characters,
            'today' => $today,
        ]);
    }
}

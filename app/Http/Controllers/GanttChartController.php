<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
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
            'project_id' => $request->input('project_id'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $projectsQuery = Project::with(['tasks' => function ($query) use ($filters) {
            app(TaskService::class)->applyAssigneeFilter($query, $filters['assignee']);
            app(TaskService::class)->applyStatusFilter($query, $filters['status']);
            app(TaskService::class)->applySearchFilter($query, $filters['search']);
            app(TaskService::class)->applyDateRangeFilter($query, $filters['start_date'], $filters['end_date']);
        }]);

        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        $projects = $projectsQuery->get();

        $startDate = null;
        $endDate = null;

        if (!empty($filters['start_date'])) {
            $startDate = Carbon::parse($filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $endDate = Carbon::parse($filters['end_date']);
        }

        if (!$startDate || !$endDate) {
            foreach ($projects as $project) {
                if (!$startDate || $project->start_date->lt($startDate)) {
                    $startDate = $project->start_date->copy();
                }

                if (!$endDate || $project->end_date->gt($endDate)) {
                    $endDate = $project->end_date->copy();
                }
            }
        }

        if (!$startDate) {
            $startDate = Carbon::today()->subDays(7);
        }

        if (!$endDate) {
            $endDate = Carbon::today()->addMonths(1);
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
        $today = Carbon::today();

        return view('gantt.index', [
            'projects' => $projects,
            'dates' => $dates,
            'holidays' => $holidays,
            'filters' => $filters,
            'allAssignees' => $allAssignees,
            'statusOptions' => $statusOptions,
            'allProjects' => $allProjects,
            'today' => $today,
        ]);
    }
}

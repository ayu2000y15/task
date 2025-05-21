<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Holiday;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * カレンダービューを表示
     */
    public function index(Request $request)
    {
        // フィルター条件を取得
        $filters = [
            'project_id' => $request->input('project_id'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        // プロジェクトのクエリを作成
        $projectsQuery = Project::with(['tasks' => function ($query) use ($filters) {
            // タスクのフィルタリング
            if (!empty($filters['assignee'])) {
                $query->where('assignee', $filters['assignee']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['search'])) {
                $query->where('name', 'like', '%' . $filters['search'] . '%');
            }
        }]);

        // 特定のプロジェクトのみ表示する場合
        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        // プロジェクトを取得
        $projects = $projectsQuery->get();

        // カレンダーイベントデータを作成
        $events = [];

        foreach ($projects as $project) {
            // プロジェクト自体をイベントとして追加
            $events[] = [
                'id' => 'project_' . $project->id,
                'title' => $project->title,
                'start' => $project->start_date->format('Y-m-d'),
                'end' => $project->end_date->addDay()->format('Y-m-d'), // FullCalendarは終了日を含まないため1日追加
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

            // プロジェクトのタスクをイベントとして追加
            foreach ($project->tasks as $task) {
                // タスクのステータスに応じた色を設定
                $taskColor = $task->color;
                switch ($task->status) {
                    case 'completed':
                        $taskColor = '#28a745'; // 完了は緑色
                        break;
                    case 'in_progress':
                        $taskColor = '#007bff'; // 進行中は青色
                        break;
                    case 'on_hold':
                        $taskColor = '#ffc107'; // 保留中は黄色
                        break;
                    case 'cancelled':
                        $taskColor = '#dc3545'; // キャンセルは赤色
                        break;
                }

                $events[] = [
                    'id' => 'task_' . $task->id,
                    'title' => $task->name,
                    'start' => $task->start_date->format('Y-m-d'),
                    'end' => $task->end_date->addDay()->format('Y-m-d'), // FullCalendarは終了日を含まないため1日追加
                    'color' => $taskColor,
                    'textColor' => '#ffffff',
                    'allDay' => true,
                    'url' => route('projects.tasks.edit', [$project, $task]),
                    'classNames' => [
                        'task-event',
                        $task->is_milestone ? 'milestone-event' : '',
                        $task->is_folder ? 'folder-event' : ''
                    ],
                    'extendedProps' => [
                        'type' => $task->is_milestone ? 'milestone' : ($task->is_folder ? 'folder' : 'task'),
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

        // 祝日データを取得
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

        // 担当者一覧を取得
        $allAssignees = Task::whereNotNull('assignee')
            ->distinct()
            ->pluck('assignee')
            ->sort()
            ->values();

        // ステータスオプション
        $statusOptions = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留中',
            'cancelled' => 'キャンセル',
        ];

        // 全プロジェクト一覧（フィルター用）
        $allProjects = Project::orderBy('title')->get();

        return view('calendar.index', [
            'events' => json_encode($events),
            'filters' => $filters,
            'allAssignees' => $allAssignees,
            'statusOptions' => $statusOptions,
            'allProjects' => $allProjects,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GanttChartController extends Controller
{
    /**
     * ガントチャートを表示
     */
    public function index(Request $request)
    {
        // フィルター条件を取得
        $filters = [
            'project_id' => $request->input('project_id'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
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

            if (!empty($filters['start_date'])) {
                $query->where('end_date', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('start_date', '<=', $filters['end_date']);
            }
        }]);

        // 特定のプロジェクトのみ表示する場合
        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        // プロジェクトを取得
        $projects = $projectsQuery->get();

        // 表示期間の決定
        $startDate = null;
        $endDate = null;

        // フィルターで期間が指定されている場合はそれを使用
        if (!empty($filters['start_date'])) {
            $startDate = Carbon::parse($filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $endDate = Carbon::parse($filters['end_date']);
        }

        // フィルターで期間が指定されていない場合は、プロジェクトの期間から自動計算
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

        // デフォルトの期間（プロジェクトがない場合や期間が特定できない場合）
        if (!$startDate) {
            $startDate = Carbon::today()->subDays(7);
        }

        if (!$endDate) {
            $endDate = Carbon::today()->addMonths(1);
        }

        // 表示する日付の配列を作成
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

        // 祝日データを取得
        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('date');

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

        // 今日の日付
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

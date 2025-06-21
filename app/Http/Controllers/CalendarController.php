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
use Carbon\CarbonPeriod;

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
            'assignee_id' => $request->input('assignee_id', ''),
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
        ];

        // 1. 表示する月の決定
        $month = $request->input('month', now()->format('Y-m'));
        $targetMonth = Carbon::parse($month)->startOfMonth();
        $startDate = $targetMonth->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $endDate = $targetMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        // 2. カレンダーデータの器を準備
        $calendarData = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $calendarData[$date->format('Y-m-d')] = [
                'date' => $date,
                'is_current_month' => $date->isSameMonth($targetMonth),
                'public_holiday' => null,
                'schedules' => collect(), // スケジュール格納用のコレクション
            ];
        }

        // 3. 祝日データを取得し、カレンダーにマージ
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])->get();
        foreach ($holidays as $holiday) {
            $dateString = $holiday->date->format('Y-m-d');
            if (isset($calendarData[$dateString])) {
                $calendarData[$dateString]['public_holiday'] = $holiday;
                // 祝日自体もイベントとして追加
                $holiday->type = 'holiday'; // タイプを動的に追加
                $calendarData[$dateString]['schedules']->push($holiday);
            }
        }

        // 4. 工程データを取得し、カレンダーにマージ
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

            // フィルターでステータスが指定されていない場合、完了タスクを除外する
            if (empty($filters['status'])) {
                $query->where('tasks.status', '!=', 'completed');
            }
        };

        $projectsQuery = Project::with(['tasks' => function ($query) use ($taskQueryLogic, $startDate, $endDate) {
            $query->with(['character', 'assignees'])
                ->where($taskQueryLogic)
                ->whereNotNull('start_date') // 開始日がないものは対象外
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });
        }])->whereHas('tasks', $taskQueryLogic);

        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }
        $projects = $projectsQuery->get();

        foreach ($projects as $project) {
            foreach ($project->tasks as $task) {
                // タスクの期間を一日ずつループ
                $periodForTask = CarbonPeriod::create($task->start_date, $task->end_date ?? $task->start_date);
                foreach ($periodForTask as $dateInPeriod) {
                    $dateString = $dateInPeriod->format('Y-m-d');
                    if (isset($calendarData[$dateString])) {
                        $clonedTask = clone $task; // 元のタスクオブジェクトを変更しないようにクローン
                        $clonedTask->type = $clonedTask->is_milestone ? 'milestone' : 'task';
                        $clonedTask->project_title = $project->title;
                        $clonedTask->color = $project->color;

                        // 表示位置（開始/中間/終了/単日）を判定してプロパティを追加
                        $isStartDate = $dateInPeriod->isSameDay($task->start_date);
                        $isEndDate = $task->end_date && $dateInPeriod->isSameDay($task->end_date);
                        $isSingleDay = !$task->end_date || $task->start_date->isSameDay($task->end_date);

                        if ($isSingleDay) {
                            $clonedTask->position = 'single';
                        } elseif ($isStartDate) {
                            $clonedTask->position = 'start';
                        } elseif ($isEndDate) {
                            $clonedTask->position = 'end';
                        } else {
                            $clonedTask->position = 'middle';
                        }

                        $calendarData[$dateString]['schedules']->push($clonedTask);
                    }
                }
            }
        }

        // 5. ユーザーの休日シフトを取得し、カレンダーにマージ
        $userShifts = WorkShift::with('user')
            ->whereIn('type', ['full_day_off', 'am_off', 'pm_off'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        foreach ($userShifts as $shift) {
            $dateString = $shift->date->format('Y-m-d');
            if (isset($calendarData[$dateString])) {
                $shift->type_original = $shift->type; // 元のタイプを保持
                $shift->type = 'usershift'; // 共通のタイプを設定
                $calendarData[$dateString]['schedules']->push($shift);
            }
        }

        // 6. 各日付のスケジュールをソート
        foreach ($calendarData as &$dayData) {
            $dayData['schedules'] = $dayData['schedules']->sortBy('start_date');
        }

        // フィルター用のデータ取得
        $allAssignees = User::whereHas('tasks')->orderBy('name')->get()->pluck('name', 'id');
        $statusOptions = Task::STATUS_OPTIONS;
        $allProjects = Project::orderBy('title')->get();
        $charactersForFilter = collect();
        if (!empty($filters['project_id'])) {
            $projectWithChars = Project::with('characters')->find($filters['project_id']);
            if ($projectWithChars) {
                $charactersForFilter = $projectWithChars->characters;
            }
        }

        return view('calendar.index', [
            'targetMonth' => $targetMonth,
            'calendarData' => $calendarData,
            'filters' => $filters,
            'allProjects' => $allProjects,
            'charactersForFilter' => $charactersForFilter,
            'allAssignees' => $allAssignees,
            'statusOptions' => $statusOptions,
        ]);
    }
}

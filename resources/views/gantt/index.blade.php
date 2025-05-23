@extends('layouts.app')

@section('title', 'ガントチャート')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>ガントチャート</h1>
        <div>
            <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterPanel">
                <i class="fas fa-filter"></i> フィルター
            </button>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">新規プロジェクト</a>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <div>
            <button class="btn btn-outline-primary me-2 view-mode active" data-mode="day" id="dayViewBtn">日ごとの表示</button>
            {{-- <button class="btn btn-outline-primary me-2 view-mode" data-mode="week" id="weekViewBtn">週ごとの表示</button>
            --}}
            <button class="toggle-details btn btn-outline-secondary me-2" id="toggleDetails">
                <i class="fas fa-columns"></i> 詳細を隠す
            </button>
        </div>
        <div>
            <button class="btn btn-primary" id="todayBtn">今日</button>
        </div>
    </div>

    <x-filter-panel :action="route('gantt.index')" :filters="$filters" :all-projects="$allProjects"
        :all-assignees="$allAssignees" :status-options="$statusOptions" :show-date-range-filter="true" />

    @if(
            $projects->isEmpty() || $projects->every(function ($project) {
                return $project->tasks->isEmpty();
            })
        )
        <div class="alert alert-info">
            表示するタスクがありません。フィルター条件を変更するか、新規プロジェクト/タスクを作成してください。
        </div>
    @else
        <div class="gantt-container">
            <div class="gantt-scroll-container">
                <table class="table table-bordered" id="ganttTable">
                    <thead class="gantt-header">
                        <tr>
                            <th rowspan="2" class="gantt-sticky-col" style="min-width: 400px; vertical-align: top;">タスク</th>
                            <th rowspan="2" class="detail-column" style="min-width: 100px; vertical-align: top;">担当者</th>
                            <th rowspan="2" class="detail-column" style="min-width: 80px; vertical-align: top;">工数</th>
                            <th rowspan="2" class="detail-column" style="min-width: 120px; vertical-align: top;">開始日</th>
                            <th rowspan="2" class="detail-column" style="min-width: 120px; vertical-align: top;">完了日</th>
                            <th rowspan="2" class="detail-column" style="min-width: 120px; vertical-align: top;">ステータス</th>
                            @php
                                $months = [];
                                foreach ($dates as $dateInfo) {
                                    $month = $dateInfo['date']->format('Y-m');
                                    if (!isset($months[$month])) {
                                        $months[$month] = [
                                            'name' => $dateInfo['date']->format('Y年n月'),
                                            'count' => 0
                                        ];
                                    }
                                    $months[$month]['count']++;
                                }
                            @endphp

                            @foreach($months as $month)
                                <th colspan="{{ $month['count'] }}" class="text-center bg-light">{{ $month['name'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($dates as $dateInfo)
                                @php
                                    $dateStr = $dateInfo['date']->format('Y-m-d');
                                    $isHoliday = isset($holidays[$dateStr]);
                                    $classes = [];
                                    $dayOfWeekMap = ['0' => '日', '1' => '月', '2' => '火', '3' => '水', '4' => '木', '5' => '金', '6' => '土'];
                                    $dayOfWeek = $dayOfWeekMap[$dateInfo['date']->format('w')];

                                    if ($dateInfo['is_saturday'])
                                        $classes[] = 'saturday';
                                    elseif ($dateInfo['is_sunday'] || $isHoliday)
                                        $classes[] = 'sunday';
                                    if ($dateInfo['date']->isSameDay($today))
                                        $classes[] = 'today';
                                @endphp
                                <th class="gantt-cell {{ implode(' ', $classes) }}"
                                    title="{{ $isHoliday ? $holidays[$dateStr]->name : '' }}" data-date="{{ $dateStr }}">
                                    <div class="date-header">
                                        <span class="date">{{ $dateInfo['day'] }}</span>
                                        <span class="day">{{ $dayOfWeek }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                            @if(!$project->tasks->isEmpty())
                                <tr class="project-header">
                                    <td class="gantt-sticky-col">
                                        <div class="d-flex justify-content-between">
                                            <div class="task-name">
                                                <span class="toggle-children" data-project-id="{{ $project->id }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                                <div class="project-icon" style="background-color: {{ $project->color }};">
                                                    {{ mb_substr($project->title, 0, 1) }}
                                                </div>
                                                <a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>
                                                @if($project->is_favorite)
                                                    <i class="fas fa-star text-warning ms-2"></i>
                                                @endif
                                            </div>
                                            <div class="task-actions">
                                                <a href="{{ route('projects.tasks.create', $project) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-plus"></i> タスク追加
                                                </a>
                                                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="detail-column" colspan="5"></td>

                                    @php
                                        $projectStartDate = $project->start_date->format('Y-m-d');
                                        $projectEndDate = $project->end_date->format('Y-m-d');
                                        $startPosition = -1;
                                        $projectLength = 0;
                                        foreach ($dates as $index => $dateInfoLoop) {
                                            $currentDate = $dateInfoLoop['date']->format('Y-m-d');
                                            if ($currentDate === $projectStartDate)
                                                $startPosition = $index;
                                            if ($currentDate >= $projectStartDate && $currentDate <= $projectEndDate)
                                                $projectLength++;
                                        }
                                    @endphp

                                    @for($i = 0; $i < count($dates); $i++)
                                        @php
                                            $dateStr = $dates[$i]['date']->format('Y-m-d');
                                            $isHoliday = isset($holidays[$dateStr]);
                                            $classes = [];
                                            if ($dates[$i]['is_saturday'])
                                                $classes[] = 'saturday';
                                            elseif ($dates[$i]['is_sunday'] || $isHoliday)
                                                $classes[] = 'sunday';
                                            if ($dates[$i]['date']->isSameDay($today))
                                                $classes[] = 'today';
                                            $hasBar = $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $projectLength);
                                            if ($hasBar)
                                                $classes[] = 'has-bar';
                                        @endphp
                                        <td class="gantt-cell {{ implode(' ', $classes) }} p-0" data-date="{{ $dateStr }}">
                                            @if($hasBar)
                                                <div class="h-100 w-100 gantt-bar"
                                                    style="background-color: {{ $project->color }}; opacity: 0.7;"></div>
                                                <div class="gantt-tooltip">
                                                    <div class="tooltip-content">
                                                        {{ $project->title }}<br>
                                                        期間: {{ $project->start_date->format('Y/m/d') }} 〜
                                                        {{ $project->end_date->format('Y/m/d') }}
                                                    </div>
                                                    <div class="tooltip-arrow"></div>
                                                </div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                                @include('gantt.partials.task_rows', ['tasks' => $project->tasks->where('parent_id', null)->sortBy(function ($task) {
                                return $task->start_date ?? '9999-12-31'; }), 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 0])
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@section('styles')
    <style>
        .gantt-container {
            overflow-x: auto;
            position: relative;
        }

        .gantt-scroll-container {
            position: relative;
        }

        .gantt-sticky-col {
            left: 0;
            z-index: 10;
            background-color: #fff;
            border-right: 2px solid #dee2e6;
        }

        .gantt-header th {
            top: 0;
            background-color: #f8f9fa;
            z-index: 20;
        }

        .gantt-header .gantt-sticky-col {
            left: 0;
            z-index: 30;
        }

        .gantt-cell {
            min-width: 30px !important;
            text-align: center;
            vertical-align: middle;
            position: relative;
        }

        .gantt-cell.today {
            background-color: #fff3cd !important;
            border-left: 1px solid #ffc107;
            border-right: 1px solid #ffc107;
        }

        .gantt-cell.saturday {
            background-color: #e9f5ff;
        }

        .gantt-cell.sunday {
            background-color: #ffe9e9;
        }

        /* #ganttTable.week-view .gantt-cell { min-width: 80px !important; } */
        .task-progress {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: inherit;
        }

        /* 進捗バーの背景を白っぽく */
        .milestone-diamond {
            width: 16px;
            height: 16px;
            transform: rotate(45deg);
            display: block;
            z-index: 5;
            position: relative;
            margin: 12px auto;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
        }

        #ganttTable.details-hidden .detail-column {
            display: none;
        }

        .project-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 0.8em;
            margin-right: 5px;
        }

        .date-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
        }

        .date-header .day {
            font-size: 0.7rem;
            color: #6c757d;
        }

        .assignee-edit-form {
            position: absolute;
            background: white;
            z-index: 100;
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .editable-cell {
            cursor: pointer;
        }

        .editable-cell:hover {
            background-color: #f0f0f0;
        }

        .status-indicator {
            width: 6px;
            height: calc(100% - 2px);
            position: absolute;
            left: 1px;
            top: 1px;
            bottom: 1px;
            z-index: 5;
            border-radius: 3px;
        }

        .gantt-bar {
            position: relative;
            transition: all 0.2s ease;
        }

        .gantt-cell:hover {
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
            z-index: 100;
        }

        /* opacityの変更は削除 */
        .gantt-tooltip {
            display: none;
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            min-width: 150px;
        }

        .gantt-tooltip.task {
            top: -80px;
        }

        .tooltip-content {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            text-align: center;
        }

        .tooltip-arrow {
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid rgba(0, 0, 0, 0.8);
        }

        td.gantt-cell.has-bar:hover .gantt-tooltip {
            display: block;
        }

        /* .has-bar を追加してバーのあるセルでのみツールチップ表示 */
        tr:hover .gantt-sticky-col {
            background-color: #f8f9fa;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#ganttTable').addClass('day-view');

            $('#toggleDetails').on('click', function () {
                const $table = $('#ganttTable');
                const $button = $(this);
                if ($table.hasClass('details-hidden')) {
                    $table.removeClass('details-hidden');
                    $button.html('<i class="fas fa-columns"></i> 詳細を隠す');
                } else {
                    $table.addClass('details-hidden');
                    $button.html('<i class="fas fa-columns"></i> 詳細を表示');
                }
            });

            $(document).on('click', '.toggle-children', function () {
                const $icon = $(this).find('i');
                const isExpanded = $icon.hasClass('fa-chevron-down');
                const taskId = $(this).data('task-id');
                const projectId = $(this).data('project-id');

                let childrenSelector;
                if (taskId) {
                    childrenSelector = `tr.task-parent-${taskId}`;
                } else if (projectId) {
                    childrenSelector = `tr.project-${projectId}-tasks.task-level-0`;
                } else {
                    return;
                }
                const $directChildren = $(childrenSelector);

                if (isExpanded) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    // すべての子孫を非表示にする
                    function hideAllDescendants($elements) {
                        $elements.each(function () {
                            $(this).hide();
                            const childTaskId = $(this).find('.toggle-children').data('task-id');
                            if (childTaskId) {
                                $(this).find('.toggle-children i.fa-chevron-down')
                                    .removeClass('fa-chevron-down').addClass('fa-chevron-right');
                                hideAllDescendants($(`tr.task-parent-${childTaskId}`));
                            }
                        });
                    }
                    hideAllDescendants($directChildren);
                } else {
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    $directChildren.show();
                    // 注意：この場合、直下の子のみを開きます。
                    // 子要素のトグル状態は変更しないため、以前に閉じられていた孫は閉じたままになります。
                }
            });


            $(document).on('change', '.status-select', function () {
                const taskId = $(this).data('task-id');
                const projectId = $(this).data('project-id');
                const status = $(this).val();
                let progress = $(`#task-progress-bar-${taskId}`).parent().data('progress') || 0;

                if (status === 'completed') {
                    progress = 100;
                } else if (status === 'not_started' || status === 'cancelled') {
                    progress = 0;
                }

                $.ajax({
                    url: `/projects/${projectId}/tasks/${taskId}/progress`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { status: status, progress: progress },
                    success: function (response) {
                        if (response.success) {
                            $(`#task-progress-bar-${taskId}`).css('width', progress + '%').parent().data('progress', progress);
                            let statusColor = '';
                            switch (status) {
                                case 'not_started': statusColor = '#6c757d'; break;
                                case 'in_progress': statusColor = '#0d6efd'; break;
                                case 'completed': statusColor = '#198754'; break;
                                case 'on_hold': statusColor = '#ffc107'; break;
                                case 'cancelled': statusColor = '#dc3545'; break;
                            }
                            $(`.status-indicator-${taskId}`).css('background-color', statusColor);
                        }
                    }
                });
            });

            function scrollToToday() {
                const $todayCell = $('.gantt-cell.today').first();
                if ($todayCell.length) {
                    const stickyColWidth = $('.gantt-sticky-col').first().outerWidth() || 0;
                    const cellPositionInContainer = $todayCell.position().left;
                    const currentScrollLeft = $('.gantt-container').scrollLeft();
                    const targetScroll = currentScrollLeft + cellPositionInContainer - stickyColWidth - 50;

                    $('.gantt-container').animate({ scrollLeft: targetScroll > 0 ? targetScroll : 0 }, 300);
                }
            }

            $('#todayBtn').on('click', scrollToToday);
            scrollToToday();


            $(document).on('click', '.editable-cell[data-field="assignee"]', function () {
                const $this = $(this);
                const taskId = $this.data('task-id');
                const currentValue = $this.data('value') || '';
                $this.addClass('d-none');
                const $editForm = $this.next('.assignee-edit-form');
                $editForm.removeClass('d-none');
                const $input = $editForm.find('input');
                $input.focus();
                $input.select();
                function completeEdit() {
                    const newValue = $input.val().trim();
                    if (newValue !== currentValue) {
                        const projectId = $input.data('project-id');
                        $.ajax({
                            url: `/projects/${projectId}/tasks/${taskId}/assignee`,
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            data: { assignee: newValue },
                            success: function (data) {
                                if (data.success) {
                                    $this.text(newValue || '-');
                                    $this.data('value', newValue);
                                } else { alert('担当者の更新に失敗しました。'); }
                            },
                            error: function () { alert('エラーが発生しました。'); }
                        });
                    }
                    $editForm.addClass('d-none');
                    $this.removeClass('d-none');
                }
                $input.on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); completeEdit(); } });
                $input.on('blur', completeEdit);
            });
        });
    </script>
@endsection
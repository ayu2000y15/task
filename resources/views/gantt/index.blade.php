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
            <button class="btn btn-outline-primary me-2 active">日ごとの表示</button>
            <button class="btn btn-outline-primary">週ごとの表示</button>
            <button class="toggle-details btn btn-outline-secondary me-2" id="toggleDetails">
                <i class="fas fa-columns"></i> 詳細を隠す
            </button>
        </div>
        <div>
            <button class="btn btn-primary">今日</button>
        </div>
    </div>

    <!-- フィルターパネル -->
    <div class="collapse {{ array_filter($filters) ? 'show' : '' }}" id="filterPanel">
        <div class="filter-panel mb-4">
            <form action="{{ route('gantt.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="project_id" class="form-label">プロジェクト</label>
                    <select class="form-select" id="project_id" name="project_id">
                        <option value="">すべて</option>
                        @foreach($allProjects as $project)
                            <option value="{{ $project->id }}" {{ $filters['project_id'] == $project->id ? 'selected' : '' }}>
                                {{ $project->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="assignee" class="form-label">担当者</label>
                    <select class="form-select" id="assignee" name="assignee">
                        <option value="">すべて</option>
                        @foreach($allAssignees as $assignee)
                            <option value="{{ $assignee }}" {{ $filters['assignee'] == $assignee ? 'selected' : '' }}>
                                {{ $assignee }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">ステータス</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">すべて</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['status'] == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">タスク名検索</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">開始日</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="{{ $filters['start_date'] }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">終了日</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                        value="{{ $filters['end_date'] }}">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">フィルター適用</button>
                    <a href="{{ route('gantt.index') }}" class="btn btn-secondary">リセット</a>
                </div>
            </form>
        </div>
    </div>

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
            <table class="table table-bordered" id="ganttTable">
                <thead class="gantt-header">
                    <tr>
                        <th style="min-width: 300px;">タスク</th>
                        <th class="detail-column" style="min-width: 100px;">担当者</th>
                        <th class="detail-column" style="min-width: 80px;">工数</th>
                        <th class="detail-column" style="min-width: 120px;">開始日</th>
                        <th class="detail-column" style="min-width: 120px;">完了日</th>
                        <th class="detail-column" style="min-width: 120px;">ステータス</th>
                        <!-- 月表示の追加 -->
                        @php
                            $currentMonth = null;
                            $monthColspan = 0;
                            $months = [];

                            // 月ごとの日数をカウント
                            foreach ($dates as $date) {
                                $month = $date['date']->format('Y-m');
                                if (!isset($months[$month])) {
                                    $months[$month] = [
                                        'name' => $date['date']->format('Y年n月'),
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
                        <th colspan="7"></th>
                        @foreach($dates as $date)
                            @php
                                $dateStr = $date['date']->format('Y-m-d');
                                $isHoliday = isset($holidays[$dateStr]);
                                $classes = [];

                                if ($date['is_saturday']) {
                                    $classes[] = 'saturday';
                                } elseif ($date['is_sunday'] || $isHoliday) {
                                    $classes[] = 'sunday';
                                }

                                if ($date['date']->isSameDay($today)) {
                                    $classes[] = 'today';
                                }
                            @endphp
                            <th class="gantt-cell {{ implode(' ', $classes) }}"
                                title="{{ $isHoliday ? $holidays[$dateStr]->name : '' }}" data-date="{{ $dateStr }}">
                                {{ $date['day'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                        @if(!$project->tasks->isEmpty())
                            <!-- プロジェクト行 -->
                            <tr class="project-header">
                                <td colspan="7">
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

                                <!-- プロジェクトのガントバー -->
                                @php
                                    $projectStartDate = $project->start_date->format('Y-m-d');
                                    $projectEndDate = $project->end_date->format('Y-m-d');

                                    $startPosition = 0;
                                    $projectLength = 0;

                                    foreach ($dates as $index => $date) {
                                        $currentDate = $date['date']->format('Y-m-d');

                                        if ($currentDate === $projectStartDate) {
                                            $startPosition = $index;
                                        }

                                        if ($currentDate >= $projectStartDate && $currentDate <= $projectEndDate) {
                                            $projectLength++;
                                        }
                                    }
                                @endphp

                                @for($i = 0; $i < count($dates); $i++)
                                    @php
                                        $dateStr = $dates[$i]['date']->format('Y-m-d');
                                        $isHoliday = isset($holidays[$dateStr]);
                                        $classes = [];

                                        if ($dates[$i]['is_saturday']) {
                                            $classes[] = 'saturday';
                                        } elseif ($dates[$i]['is_sunday'] || $isHoliday) {
                                            $classes[] = 'sunday';
                                        }

                                        if ($dates[$i]['date']->isSameDay($today)) {
                                            $classes[] = 'today';
                                        }
                                    @endphp
                                    <td class="gantt-cell {{ implode(' ', $classes) }} p-0">
                                        @if($i >= $startPosition && $i < ($startPosition + $projectLength))
                                            <div class="h-100 w-100" style="background-color: rgba(0, 123, 255, 0.2);"></div>
                                        @endif
                                    </td>
                                @endfor
                            </tr>

                            <!-- プロジェクトのタスク -->
                            @include('gantt.partials.task_rows', ['tasks' => $project->tasks, 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 0])
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            // 詳細カラムの表示/非表示切り替え
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

            // タスク階層の展開/縮小
            $('.toggle-children').on('click', function () {
                const $icon = $(this).find('i');
                const isExpanded = $icon.hasClass('fa-chevron-down');
                const projectId = $(this).data('project-id');
                const taskId = $(this).data('task-id');

                if (isExpanded) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    if (taskId) {
                        $(`.task-parent-${taskId}`).hide();
                    } else {
                        $(`.project-${projectId}-tasks`).hide();
                    }
                } else {
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    if (taskId) {
                        $(`.task-parent-${taskId}`).show();
                    } else {
                        $(`.project-${projectId}-tasks`).show();
                    }
                }
            });

            // ステータス変更
            $('.status-select').on('change', function () {
                const taskId = $(this).data('task-id');
                const projectId = $(this).data('project-id');
                const status = $(this).val();
                const $progressSlider = $(`#progress-slider-${taskId}`);
                const progress = $progressSlider.val();


                // Ajaxリクエストでステータスを更新
                $.ajax({
                    url: `/projects/${projectId}/tasks/${taskId}/progress`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        progress: $progressSlider.val(),
                        status: status
                    },
                    success: function (response) {
                        if (response.success) {
                            if (status === 'completed') {
                                $(`#task-progress-bar-${taskId}`).css('width', '100%');
                            }
                        }
                    }
                });
            });

        });
    </script>
@endsection
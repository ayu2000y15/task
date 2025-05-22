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
            <button class="btn btn-outline-primary me-2 view-mode" data-mode="week" id="weekViewBtn">週ごとの表示</button>
            <button class="toggle-details btn btn-outline-secondary me-2" id="toggleDetails">
                <i class="fas fa-columns"></i> 詳細を隠す
            </button>
        </div>
        <div>
            <button class="btn btn-primary" id="todayBtn">今日</button>
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
                            @foreach($dates as $date)
                                @php
                                    $dateStr = $date['date']->format('Y-m-d');
                                    $isHoliday = isset($holidays[$dateStr]);
                                    $classes = [];

                                    // 曜日を漢字で表示
                                    $dayOfWeekMap = [
                                        '0' => '日',
                                        '1' => '月',
                                        '2' => '火',
                                        '3' => '水',
                                        '4' => '木',
                                        '5' => '金',
                                        '6' => '土',
                                    ];
                                    $dayOfWeek = $dayOfWeekMap[$date['date']->format('w')];

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
                                    <div class="date-header">
                                        <span class="date">{{ $date['day'] }}</span>
                                        <span class="day">{{ $dayOfWeek }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                            @if(!$project->tasks->isEmpty())
                                <!-- プロジェクト行 -->
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

                                    <!-- プロジェクトのガントバー -->
                                    @php
                                        $projectStartDate = $project->start_date->format('Y-m-d');
                                        $projectEndDate = $project->end_date->format('Y-m-d');

                                        $startPosition = -1;
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

                                            $hasBar = $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $projectLength);
                                            if ($hasBar) {
                                                $classes[] = 'has-bar';
                                            }
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

                                <!-- プロジェクトのタスク -->
                                @include('gantt.partials.task_rows', ['tasks' => $project->tasks->where('parent_id', null)->sortBy('start_date'), 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 0])
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
        /* ガントチャート用の追加スタイル */
        .gantt-container {
            overflow-x: auto;
            position: relative;
        }

        .gantt-scroll-container {
            position: relative;
        }

        .gantt-sticky-col {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #fff;
            border-right: 2px solid #dee2e6;
        }

        /* プロジェクトヘッダーの固定を解除 */
        .project-header .gantt-sticky-col {
            position: relative;
            left: auto;
            z-index: 1;
        }

        /* ヘッダー部の固定を解除 */
        .gantt-header .gantt-sticky-col {
            position: relative;
            left: auto;
            z-index: 1;
        }

        /* ヘッダーを固定 */
        .gantt-cell {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #fff;
        }

        .gantt-cell th {
            background-color: #fff;
        }

        .gantt-cell .gantt-sticky-col {
            position: sticky;
            left: 0;
            z-index: 21;
            background-color: #fff;
        }

        .task-progress {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .milestone-diamond {
            width: 16px;
            height: 16px;
            transform: rotate(45deg);
            display: block;
            z-index: 5;
            position: relative;
            margin: 12px auto;
        }

        /* 詳細を隠す機能のスタイル修正 */
        #ganttTable.details-hidden .detail-column {
            display: none;
        }

        /* 階層構造のインデント */
        .task-indent {
            padding-left: 20px;
            border-left: 1px solid #ddd;
        }



        /* 週表示モード */
        .week-view .gantt-cell {
            min-width: 60px !important;
        }

        .day-view .gantt-cell {
            min-width: 30px !important;
        }

        /* 日付ヘッダーのスタイル */
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

        /* 担当者編集フォーム */
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
            background-color: #f8f9fa;
        }

        /* ステータスインジケーターのスタイル改善 */
        .status-indicator {
            width: 8px;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 5;
        }

        /* タスクバーのホバー効果 */
        .gantt-bar {
            position: relative;
            transition: all 0.2s ease;
        }

        .gantt-bar:hover {
            opacity: 1 !important;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
            z-index: 100;
        }

        /* セルのホバー効果 */
        .gantt-cell {
            position: relative;
        }

        .gantt-cell.has-bar:hover .gantt-bar {
            opacity: 1 !important;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
        }

        /* 新しいツールチップスタイル */
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
            display: none;
            position: absolute;
            top: -80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            min-width: 150px;
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

        .gantt-cell.has-bar:hover .gantt-tooltip {
            display: block;
        }

        /* タスク行のホバー効果 */
        tr:hover .gantt-sticky-col {
            background-color: #f8f9fa;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            // 現在のビューモード（デフォルトは日表示）
            let currentViewMode = 'day';
            $('#ganttTable').addClass('day-view');

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
                        // 直接の子タスクだけを非表示にする
                        $(`.task-parent-${taskId}`).hide();
                        // 子タスクのアイコンも閉じた状態にする
                        $(`.task-parent-${taskId} .toggle-children i.fa-chevron-down`).removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    } else {
                        // プロジェクトの直接の子タスクを非表示
                        $(`.project-${projectId}-tasks:not([class*="task-parent-"])`).hide();
                        // プロジェクト内のすべてのタスクのアイコンを閉じた状態にする
                        $(`.project-${projectId}-tasks .toggle-children i.fa-chevron-down`).removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    }
                } else {
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    if (taskId) {
                        // 直接の子タスクだけを表示
                        $(`.task-parent-${taskId}`).show();
                    } else {
                        // プロジェクトの直接の子タスクを表示
                        $(`.project-${projectId}-tasks:not([class*="task-parent-"])`).show();
                    }
                }
            });

            // ステータス変更
            $('.status-select').on('change', function () {
                const taskId = $(this).data('task-id');
                const projectId = $(this).data('project-id');
                const status = $(this).val();

                // Ajaxリクエストでステータスを更新
                $.ajax({
                    url: `/projects/${projectId}/tasks/${taskId}/progress`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        status: status,
                        progress: status === 'completed' ? 100 : (status === 'in_progress' ? 50 : 0)
                    },
                    success: function (response) {
                        if (response.success) {
                            if (status === 'completed') {
                                $(`#task-progress-bar-${taskId}`).css('width', '100%');
                            } else if (status === 'in_progress') {
                                $(`#task-progress-bar-${taskId}`).css('width', '50%');
                            } else {
                                $(`#task-progress-bar-${taskId}`).css('width', '0%');
                            }

                            // ステータスインジケーターの色を更新
                            let statusColor = '';
                            switch (status) {
                                case 'not_started':
                                    statusColor = '#6c757d'; // 灰色
                                    break;
                                case 'in_progress':
                                    statusColor = '#0d6efd'; // 青色
                                    break;
                                case 'completed':
                                    statusColor = '#198754'; // 緑色
                                    break;
                                case 'on_hold':
                                    statusColor = '#ffc107'; // 黄色
                                    break;
                                case 'cancelled':
                                    statusColor = '#dc3545'; // 赤色
                                    break;
                            }
                            $(`.status-indicator-${taskId}`).css('background-color', statusColor);
                        }
                    }
                });
            });

            // 週表示/日表示の切り替え
            $('.view-mode').on('click', function () {
                const mode = $(this).data('mode');

                // ボタンのアクティブ状態を切り替え
                $('.view-mode').removeClass('active');
                $(this).addClass('active');

                // テーブルのクラスを切り替え
                $('#ganttTable').removeClass('day-view week-view');
                $('#ganttTable').addClass(`${mode}-view`);

                currentViewMode = mode;
            });

            // 今日ボタン
            $('#todayBtn').on('click', function () {
                // 今日の日付を含む列までスクロール
                const $todayCell = $('.gantt-cell.today').first();
                if ($todayCell.length) {
                    const scrollPosition = $todayCell.offset().left - 400; // 左側に余裕を持たせる
                    $('.gantt-container').scrollLeft(scrollPosition);
                }
            });

            // 担当者の編集機能
            $(document).on('click', '.editable-cell[data-field="assignee"]', function () {
                const $this = $(this);
                const taskId = $this.data('task-id');
                const currentValue = $this.data('value') || '';

                // 現在のセルを非表示にして入力フォームを表示
                $this.addClass('d-none');
                const $editForm = $this.next('.assignee-edit-form');
                $editForm.removeClass('d-none');

                // 入力フィールドにフォーカスを当てる
                const $input = $editForm.find('input');
                $input.focus();
                $input.select();

                // 入力完了時の処理（Enterキーまたはフォーカスが外れた時）
                function completeEdit() {
                    const newValue = $input.val().trim();

                    // 値が変更された場合のみ更新
                    if (newValue !== currentValue) {
                        const projectId = $input.data('project-id');

                        // 担当者を更新
                        $.ajax({
                            url: `/projects/${projectId}/tasks/${taskId}/assignee`,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                assignee: newValue
                            },
                            success: function (data) {
                                if (data.success) {
                                    // 成功時の処理
                                    $this.text(newValue || '-');
                                    $this.data('value', newValue);
                                } else {
                                    alert('担当者の更新に失敗しました。');
                                }
                            },
                            error: function () {
                                alert('エラーが発生しました。');
                            }
                        });
                    }

                    // 入力フォームを非表示にしてセルを表示
                    $editForm.addClass('d-none');
                    $this.removeClass('d-none');
                }

                // Enterキーで編集完了
                $input.on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        completeEdit();
                    }
                });

                // フォーカスが外れたら編集完了
                $input.on('blur', completeEdit);
            });
        });
    </script>
@endsection
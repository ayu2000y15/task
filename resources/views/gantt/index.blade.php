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
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 新規衣装案件</a>
            @endcan
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
        :all-characters="$characters" :all-assignees="$allAssignees" :status-options="$statusOptions"
        :show-date-range-filter="true" />

    @if(
            $projects->isEmpty() || $projects->every(function ($project) {
                // 案件全体のタスク、またはキャラクターに紐づくタスクのいずれかがあれば表示対象とする
                return $project->tasks()->whereNull('character_id')->get()->isEmpty() && $project->characters->every(function ($char) {
                    return $char->tasks->isEmpty();
                });
            })
        )
        <div class="alert alert-info">
            表示する工程がありません。フィルター条件を変更するか、新規衣装案件/工程を作成してください。
        </div>
    @else
        <div class="gantt-container">
            <div class="gantt-scroll-container">
                <table class="table table-bordered" id="ganttTable">
                    <thead class="gantt-header">
                        <tr>
                            <th rowspan="2" class="gantt-sticky-col" style="vertical-align: top;">工程</th>
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
                            @if($project->tasks()->whereNull('character_id')->whereNull('parent_id')->exists() || $project->characters()->whereHas('tasks')->exists() || $project->tasks()->whereNotNull('character_id')->whereNull('parent_id')->exists())
                                <tr class="project-header project-{{ $project->id }}-tasks task-level-0">
                                    <td class="gantt-sticky-col">
                                        <div class="d-flex justify-content-between">
                                            <div class="task-name">
                                                <span class="toggle-children" data-project-id="{{ $project->id }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                                <div class="project-icon"
                                                    style="background-color: {{ $project->color }}; color: white;">
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
                                                    <i class="fas fa-plus"></i> 工程追加
                                                </a>
                                                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="detail-column">&nbsp;</td> {{-- 担当者 --}}
                                    <td class="detail-column">&nbsp;</td> {{-- 工数 --}}
                                    <td class="detail-column">&nbsp;</td> {{-- 開始日 --}}
                                    <td class="detail-column">&nbsp;</td> {{-- 完了日 --}}
                                    <td class="detail-column">&nbsp;</td> {{-- ステータス --}}

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
                                            $cellClasses = [];
                                            if ($dates[$i]['is_saturday'])
                                                $cellClasses[] = 'saturday';
                                            elseif ($dates[$i]['is_sunday'] || $isHoliday)
                                                $cellClasses[] = 'sunday';
                                            if ($dates[$i]['date']->isSameDay($today))
                                                $cellClasses[] = 'today';
                                            $hasBar = $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $projectLength);
                                            if ($hasBar)
                                                $cellClasses[] = 'has-bar';
                                        @endphp
                                        <td class="gantt-cell {{ implode(' ', $cellClasses) }} p-0" data-date="{{ $dateStr }}">
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
                                {{-- 案件全体の工程 (キャラクターに紐づかない) --}}
                                @php
                                    $projectLevelTasks = $project->tasks
                                        ->whereNull('character_id')
                                        ->whereNull('parent_id')
                                        ->sortBy(function ($task, $key) {
                                            // [is_null(start_date), start_date, name] の順でソート
                                            return [$task->start_date === null, $task->start_date, $task->name];
                                        });
                                @endphp
                                @include('gantt.partials.task_rows', ['tasks' => $projectLevelTasks, 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 0, 'parent_character_id' => null])

                                {{-- キャラクターごとの工程 --}}
                                @foreach($project->characters->sortBy('name') as $character)
                                    @php
                                        $characterTasks = $character->tasks
                                            ->whereNull('parent_id')
                                            ->sortBy(function ($task, $key) {
                                                return [$task->start_date === null, $task->start_date, $task->name];
                                            });
                                    @endphp
                                    <tr class="character-header project-{{ $project->id }}-tasks task-level-0"
                                        data-project-id-for-toggle="{{ $project->id }}">
                                        <td class="gantt-sticky-col">
                                            <div class="d-flex align-items-center" style="padding-left: 20px;">
                                                <span class="toggle-children" data-character-id="{{ $character->id }}"
                                                    data-project-id-of-char="{{ $project->id }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                                <i class="fas fa-user-circle text-info me-2 ms-1" style="font-size: 1.1em;"></i>
                                                <strong>{{ $character->name }}</strong>
                                            </div>
                                        </td>
                                        <td class="detail-column">&nbsp;</td>
                                        <td class="detail-column">&nbsp;</td>
                                        <td class="detail-column">&nbsp;</td>
                                        <td class="detail-column">&nbsp;</td>
                                        <td class="detail-column">&nbsp;</td>
                                        @for($i = 0; $i < count($dates); $i++)
                                            @php
                                                $dateStr = $dates[$i]['date']->format('Y-m-d');
                                                $isHoliday = isset($holidays[$dateStr]);
                                                $cellClasses = [];
                                                if ($dates[$i]['is_saturday'])
                                                    $cellClasses[] = 'saturday';
                                                elseif ($dates[$i]['is_sunday'] || $isHoliday)
                                                    $cellClasses[] = 'sunday';
                                                if ($dates[$i]['date']->isSameDay($today))
                                                    $cellClasses[] = 'today';
                                            @endphp
                                            <td class="gantt-cell {{ implode(' ', $cellClasses) }} p-0" data-date="{{ $dateStr }}">&nbsp;</td>
                                        @endfor
                                    </tr>
                                    @include('gantt.partials.task_rows', ['tasks' => $characterTasks, 'project' => $project, 'character' => $character, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 1, 'parent_character_id' => $character->id])
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ★ここからファイルアップロードモーダルを追加 --}}
    <div class="modal fade" id="ganttFileUploadModal" tabindex="-1" aria-labelledby="ganttFileUploadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ganttFileUploadModalLabel">ファイルアップロード: <span id="ganttFolderUploadName"
                            class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Dropzone.js フォーム --}}
                    <form action="#" method="post" class="dropzone dropzone-custom-style mb-3"
                        id="gantt-file-upload-dropzone">
                        @csrf {{-- CSRFトークン --}}
                        <div class="dz-message text-center" data-dz-message>
                            <p class="mb-2">ここにファイルをドラッグ＆ドロップ</p>
                            <p class="mb-3 text-muted small">または</p>
                            <button type="button" class="btn btn-outline-primary dz-button-bootstrap">
                                <i class="fas fa-folder-open me-1"></i>ファイルを選択
                            </button>
                        </div>
                    </form>

                    <h6><i class="fas fa-list-ul"></i> アップロード済みファイル</h6>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <ul class="list-group" id="gantt-uploaded-file-list">
                            {{-- ここにAjaxでファイル一覧がロードされます --}}
                            <li class="list-group-item text-center text-muted">ファイルを読み込み中...</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <button type="button" class="btn btn-primary" id="ganttProcessUploadQueueBtn"><i
                            class="fas fa-upload"></i> 選択したファイルをアップロード</button>
                </div>
            </div>
        </div>
    </div>
    {{-- ★ここまでファイルアップロードモーダル --}}
@endsection

@section('styles')
    <style>
        /* ... （既存のスタイルは変更なし） ... */
        .gantt-container {
            overflow-x: auto;
            position: relative;
        }

        .gantt-scroll-container {
            position: relative;
        }

        .gantt-sticky-col {
            position: -webkit-sticky;
            /* Safari対応 */
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #fff;
            /* 背景色がないと下のセルが透ける */
            border-right: 2px solid #dee2e6;
            /* 区切り線を明確に */
        }

        .gantt-header th {
            position: -webkit-sticky;
            /* Safari対応 */
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 20;
            /* スティッキー列より手前に */
        }

        .gantt-header .gantt-sticky-col {
            z-index: 30;
            /* 日付ヘッダーよりさらに手前に */
        }

        .character-header td {
            /* キャラクターヘッダー行のスタイル */
            background-color: #f0f8ff;
            /* 例: AliceBlue */
            font-weight: 500;
            /* border-bottom: 1px dashed #ccc; */
            /* 必要であれば区切り線 */
        }

        .character-header:hover td {
            background-color: #e6f2ff;
        }

        /* 他のスタイルは前回提示のものを流用 */
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

        .task-progress {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: inherit;
        }

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

        .editable-cell {
            cursor: pointer;
        }

        .editable-cell:hover {
            background-color: #f0f0f0;
        }

        .gantt-bar {
            position: relative;
            transition: all 0.2s ease;
        }

        .gantt-cell:hover {
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
            z-index: 100;
        }

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

        tr:hover .gantt-sticky-col {
            background-color: #f8f9fa;
        }

        /* ▼ Dropzoneカスタムスタイル (tasks.edit.blade.php からコピー) ▼ */
        .dropzone-custom-style {
            border: 2px dashed #007bff !important;
            border-radius: .25rem;
            padding: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            min-height: 150px;
        }

        .dropzone-custom-style .dz-message {
            color: #6c757d;
            font-weight: 500;
            width: 100%;
            text-align: center;
            align-self: center;
        }

        .dropzone-custom-style .dz-message p {
            margin-bottom: 0.5rem;
        }

        .dropzone-custom-style .dz-preview {
            width: 120px;
            height: auto;
            margin: 0.25rem;
            background-color: transparent;
            border: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            border-radius: 20px;
            /* Bootstrap 5風に調整 */
        }

        .dropzone-custom-style .dz-image {
            width: 80px;
            height: 80px;
            display: flex;
            border: 1px solid #dee2e6;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .dropzone-custom-style .dz-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            background-color: transparent;
        }

        .dropzone-custom-style .dz-details {
            display: block;
            text-align: center;
            width: 100%;
            position: relative;
        }

        .dropzone-custom-style .dz-filename {
            display: block;
            font-size: 0.75em;
            color: #495057;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.2;
            margin-top: 0.25rem;
        }

        .dropzone-custom-style .dz-filename span {
            background-color: transparent;
        }

        .dropzone-custom-style .dz-size {
            font-size: 0.65em;
            color: #6c757d;
            margin-top: 0.15rem;
            background-color: transparent;
        }

        .dropzone-custom-style .dz-progress,
        .dropzone-custom-style .dz-error-message,
        .dropzone-custom-style .dz-success-mark,
        .dropzone-custom-style .dz-error-mark {
            display: none;
        }

        .dropzone-custom-style .dz-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.8);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            line-height: 18px;
            text-align: center;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            opacity: 1;
            z-index: 30;
        }

        .dropzone-custom-style .dz-remove:hover {
            text-decoration: none !important;
            color: #aaaaaa;
        }

        /* ▲ Dropzoneカスタムスタイル ▲ */

        /* --- ガントチャート 工程名折り返し対応 --- */
        .gantt-sticky-col .task-name a,
        /* 案件ヘッダー行の案件名リンク */
        .gantt-sticky-col .task-name-column a,
        /* 通常工程の工程名リンク */
        .gantt-sticky-col .task-name-column>span:not([class*="icon"]):not(.badge):not(.toggle-children)

        /* フォルダ名やマイルストーン名など、リンクではないテキスト */
            {
            white-space: normal;
            /* テキストの折り返しを許可 */
            word-break: break-word;
            /* 必要に応じて単語の途中でも改行 */
            overflow: visible;
            /* overflow:hiddenによる切り捨てを解除 */
            text-overflow: clip;
            /* ellipsis(...)表示を解除 */
            max-width: none;
            /* max-widthによる制限を解除 */
            display: inline;
            /* アイコン等と自然に並ぶように */
        }

        /* 工程名全体のコンテナも折り返しに対応させる */
        .gantt-sticky-col .task-name,
        .gantt-sticky-col .task-name-column {
            white-space: normal;
            /* 親要素も折り返しを許可 */
            overflow: visible;
        }

        /* 折り返しによる高さ変更時の垂直アラインメント調整 */
        .gantt-sticky-col>.d-flex.justify-content-between,
        /* td直下のflexコンテナ */
        .gantt-sticky-col .task-name-column.d-flex

        /* 工程名とアイコンのflexコンテナ */
            {
            align-items: flex-start;
            /* 上揃えにする */
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        Dropzone.autoDiscover = false; // グローバルスコープで最初に設定
        let ganttDropzoneInstance;
        let currentUploadProjectId;
        let currentUploadTaskId;
        const overlay = document.getElementById('upload-loading-overlay'); // オーバーレイ要素をキャッシュ

        function fetchAndDisplayGanttFiles(projectId, taskId) {
            const ganttUploadedFileListEl = document.getElementById('gantt-uploaded-file-list');
            if (!ganttUploadedFileListEl) return;
            ganttUploadedFileListEl.innerHTML = '<li class="list-group-item text-center text-muted">ファイルを読み込み中...</li>';

            axios.get(`/projects/${projectId}/tasks/${taskId}/files`)
                .then(function (response) {
                    ganttUploadedFileListEl.innerHTML = response.data;
                })
                .catch(function (error) {
                    ganttUploadedFileListEl.innerHTML = '<li class="list-group-item text-center text-danger">ファイル一覧の取得に失敗しました。</li>';
                    console.error("Error fetching files for Gantt modal:", error);
                });
        }

        function initializeGanttDropzone(projectId, taskId) {
            const dropzoneElement = document.getElementById('gantt-file-upload-dropzone');
            if (!dropzoneElement) {
                console.error('Dropzone element #gantt-file-upload-dropzone not found!');
                return;
            }

            const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
            const uploadUrl = `/projects/${projectId}/tasks/${taskId}/files`;

            if (!csrfTokenEl || !csrfTokenEl.getAttribute('content')) {
                console.error("CSRF token not found for Gantt Dropzone.");
                return;
            }


            if (ganttDropzoneInstance) {
                ganttDropzoneInstance.destroy();
            }

            const clickableButton = dropzoneElement.querySelector('.dz-button-bootstrap');
            if (!clickableButton) {
                console.error("Dropzone clickable button '.dz-button-bootstrap' not found in Gantt modal.");
                return;
            }

            ganttDropzoneInstance = new Dropzone(dropzoneElement, {
                url: uploadUrl,
                method: 'post',
                clickable: clickableButton,
                paramName: "file",
                maxFilesize: 100,
                maxFiles: 10,
                parallelUploads: 10,
                acceptedFiles: ".jpeg,.jpg,.png,.gif,.svg,.bmp,.tiff,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.tar,.gz,.txt,.md,.csv,.json,.xml,.html,.css,.js,.php,.py,.java,.c,.cpp,.cs,.rb,.go,.sql,.ai,.psd,.fig,.sketch,video/*,audio/*,application/octet-stream",
                addRemoveLinks: true,
                dictRemoveFile: "×",
                dictCancelUpload: "↵",
                dictMaxFilesExceeded: "一度にアップロードできるファイルは10個までです。",
                headers: {
                    'X-CSRF-TOKEN': csrfTokenEl.getAttribute('content')
                },
                autoProcessQueue: false,
                init: function () {
                    // "processingmultiple" からオーバーレイ表示ロジックを削除
                    this.on("success", function (file, response) {
                        fetchAndDisplayGanttFiles(currentUploadProjectId, currentUploadTaskId);
                        this.removeFile(file);
                    });
                    this.on("error", function (file, message) {
                        let errorMessage = "アップロードに失敗しました。";
                        if (typeof message === "string") errorMessage = message;
                        else if (message && message.errors && message.errors.file) errorMessage = message.errors.file[0];
                        else if (message && message.message) errorMessage = message.message;
                        alert("エラー: " + errorMessage);
                        this.removeFile(file);
                    });
                    this.on("queuecomplete", function () {
                        if (overlay) overlay.style.display = 'none'; // Hide overlay

                        if (this.getQueuedFiles().length === 0 && this.getUploadingFiles().length === 0) {
                            if (this.getRejectedFiles().length > 0 || this.getFilesWithStatus(Dropzone.ERROR).length > 0) {
                                alert('一部のファイルのアップロードに失敗しました。');
                            }
                        }
                    });
                    this.on("errormultiple", function (files, message) {
                        if (overlay) overlay.style.display = 'none'; // Hide overlay on error
                        alert('一部または全てのファイルのアップロードに失敗しました。詳細を確認してください。');
                    });
                }
            });
        }

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
                const $toggleSpan = $(this);
                const $icon = $toggleSpan.find('i');
                const isExpanded = $icon.hasClass('fa-chevron-down');
                const $parentRow = $toggleSpan.closest('tr');
                let $directChildrenToToggle;
                if ($parentRow.hasClass('project-header')) {
                    const projectId = $toggleSpan.data('project-id');
                    $directChildrenToToggle = $(`tr.project-${projectId}-tasks.task-level-0:not(.project-header)`);
                } else if ($parentRow.hasClass('character-header')) {
                    const characterId = $toggleSpan.data('character-id');
                    const projectIdOfChar = $toggleSpan.data('project-id-of-char');
                    $directChildrenToToggle = $(`tr.project-${projectIdOfChar}-tasks.task-parent-char-${characterId}.task-level-1`);
                } else {
                    const taskId = $toggleSpan.data('task-id');
                    if (taskId) {
                        $directChildrenToToggle = $(`tr.task-parent-${taskId}`);
                    }
                }
                if (!$directChildrenToToggle || $directChildrenToToggle.length === 0) {
                    if (isExpanded) $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    else $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    return;
                }
                if (isExpanded) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    function closeAllDescendants($elements) {
                        $elements.each(function () {
                            const $currentElement = $(this);
                            $currentElement.hide();
                            const $toggleSpanOnCurrentElement = $currentElement.find('.toggle-children').first();
                            if ($toggleSpanOnCurrentElement.length > 0) {
                                $toggleSpanOnCurrentElement.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                                let $grandChildren;
                                const characterId = $toggleSpanOnCurrentElement.data('character-id');
                                const taskId = $toggleSpanOnCurrentElement.data('task-id');
                                if (characterId) {
                                    const projectIdOfChar = $toggleSpanOnCurrentElement.data('project-id-of-char');
                                    $grandChildren = $(`tr.project-${projectIdOfChar}-tasks.task-parent-char-${characterId}.task-level-1`);
                                } else if (taskId) {
                                    $grandChildren = $(`tr.task-parent-${taskId}`);
                                }
                                if ($grandChildren && $grandChildren.length > 0) {
                                    closeAllDescendants($grandChildren);
                                }
                            }
                        });
                    }
                    closeAllDescendants($directChildrenToToggle);
                } else {
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    $directChildrenToToggle.show();
                }
            });

            function scrollToToday() {
                const $todayCell = $('.gantt-cell.today').first();
                if ($todayCell.length) {
                    const ganttContainer = $('.gantt-container');
                    const stickyColWidth = $('.gantt-sticky-col').first().outerWidth() || 0;
                    const cellOffsetLeft = $todayCell.offset().left - ganttContainer.offset().left + ganttContainer.scrollLeft();
                    const targetScroll = cellOffsetLeft - stickyColWidth - 50;
                    ganttContainer.animate({ scrollLeft: Math.max(0, targetScroll) }, 300);
                }
            }
            $('#todayBtn').on('click', scrollToToday);

            $(document).on('change', '.status-select', function () {
                const taskId = $(this).data('task-id');
                const projectId = $(this).data('project-id');
                const status = $(this).val();
                let progress = 0;
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
                        if (!response.success) {
                            console.error('ステータス更新に失敗しました: ' + (response.message || ''));
                        }
                        location.reload();
                    },
                    error: function () {
                        console.error('ステータス更新中にエラーが発生しました。');
                    }
                });
            });

            $(document).on('click', '.editable-cell[data-field="assignee"]', function () {
                const $this = $(this);
                if ($this.find('input').length) return;
                const currentValue = $this.text().trim() === '-' ? '' : $this.text().trim();
                const taskId = $this.data('task-id');
                let projectId;
                const $taskRow = $this.closest('tr[class*="project-"]');
                const classList = $taskRow.attr('class');
                const projectMatch = classList ? classList.match(/project-(\d+)-tasks/) : null;
                if (projectMatch && projectMatch[1]) {
                    projectId = projectMatch[1];
                } else {
                    console.error('Project ID not found for task:', taskId, 'on row with classes:', classList);
                    return;
                }
                $this.html(`<input type="text" class="form-control form-control-sm assignee-input-inline" value="${currentValue}" data-task-id="${taskId}" data-project-id="${projectId}">`);
                const $input = $this.find('input');
                $input.focus().select();
                function completeAssigneeEdit() {
                    const newValue = $input.val().trim();
                    if (newValue !== currentValue) {
                        $.ajax({
                            url: `/projects/${projectId}/tasks/${taskId}/assignee`,
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            data: { assignee: newValue },
                            success: function (data) {
                                if (data.success) {
                                    $this.text(newValue || '-');
                                } else {
                                    console.error('担当者の更新に失敗しました: ' + (data.message || ''));
                                    $this.text(currentValue || '-');
                                }
                            },
                            error: function () {
                                console.error('担当者更新中にエラーが発生しました。');
                                $this.text(currentValue || '-');
                            }
                        });
                    } else {
                        $this.text(currentValue || '-');
                    }
                }
                $input.on('blur', completeAssigneeEdit);
                $input.on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); completeAssigneeEdit(); } else if (e.key === 'Escape') { $input.val(currentValue); completeAssigneeEdit(); } });
            });

            const fileUploadModalEl = document.getElementById('ganttFileUploadModal');
            const ganttFolderUploadNameEl = document.getElementById('ganttFolderUploadName');
            const processQueueBtn = document.getElementById('ganttProcessUploadQueueBtn');

            $(document).on('click', '.gantt-upload-file-btn', function () {
                currentUploadProjectId = $(this).data('project-id');
                currentUploadTaskId = $(this).data('task-id');
                const taskName = $(this).data('task-name');
                if (ganttFolderUploadNameEl) ganttFolderUploadNameEl.textContent = taskName;
                initializeGanttDropzone(currentUploadProjectId, currentUploadTaskId);
                fetchAndDisplayGanttFiles(currentUploadProjectId, currentUploadTaskId);
            });

            if (processQueueBtn) {
                processQueueBtn.addEventListener('click', function () {
                    if (overlay) overlay.style.display = 'flex'; // Show overlay
                    if (ganttDropzoneInstance && ganttDropzoneInstance.getQueuedFiles().length > 0) {
                        ganttDropzoneInstance.processQueue();
                    } else {
                        if (overlay) overlay.style.display = 'none'; // Hide overlay if no files
                        alert('アップロードするファイルが選択されていません。');
                    }
                });
            }

            if (fileUploadModalEl) {
                fileUploadModalEl.addEventListener('hidden.bs.modal', function () {
                    if (ganttDropzoneInstance) {
                        ganttDropzoneInstance.removeAllFiles(true);
                    }
                    const ganttUploadedFileListEl = document.getElementById('gantt-uploaded-file-list');
                    if (ganttUploadedFileListEl) {
                        ganttUploadedFileListEl.innerHTML = '<li class="list-group-item text-center text-muted">ファイルを読み込み中...</li>';
                    }
                });
            }

            $(document).on('click', '#gantt-uploaded-file-list .delete-file-btn', function (e) {
                e.preventDefault();
                const button = $(this);
                const url = button.data('url');
                if (!url) {
                    console.error("Delete URL not found on Gantt delete button");
                    return;
                }
                if (confirm('本当にこのファイルを削除しますか？')) {
                    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfTokenMeta || !csrfTokenMeta.content) {
                        console.error("CSRF token meta tag not found or empty for Gantt delete.");
                        return;
                    }
                    axios.delete(url, { headers: { 'X-CSRF-TOKEN': csrfTokenMeta.content } })
                        .then(function (response) {
                            if (response.data.success) {
                                button.closest('.list-group-item').remove();
                                const ganttUploadedFileListEl = document.getElementById('gantt-uploaded-file-list');
                                if (ganttUploadedFileListEl && $(ganttUploadedFileListEl).children().length === 0) {
                                    ganttUploadedFileListEl.innerHTML = '<li class="list-group-item text-center text-muted">アップロードされたファイルはありません。</li>';
                                }
                            } else {
                                alert('ファイルの削除に失敗しました。\n' + (response.data.message || ''));
                            }
                        })
                        .catch(function (error) {
                            alert('ファイルの削除中にエラーが発生しました。');
                            console.error(error);
                        });
                }
            });
        });
    </script>
@endsection
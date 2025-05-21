@extends('layouts.app')

@section('title', 'カレンダー')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>カレンダー</h1>
        <div>
            <button class="btn btn-outline-primary me-2" type="button" id="showFilterBtn">
                <i class="fas fa-filter"></i> フィルター
            </button>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">新規プロジェクト</a>
        </div>
    </div>

    <!-- フィルターパネル -->
    <div class="collapse {{ array_filter($filters) ? 'show' : '' }}" id="filterPanel">
        <div class="filter-panel mb-4">
            <div class="filter-close" id="closeFilterBtn">
                <i class="fas fa-times"></i>
            </div>
            <form action="{{ route('calendar.index') }}" method="GET" class="row g-3">
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
                <div class="col-md-12 d-flex">
                    <button type="submit" class="btn btn-primary me-2">フィルター適用</button>
                    <a href="{{ route('calendar.index') }}" class="btn btn-secondary">リセット</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">カレンダー</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showProjectsToggle" checked>
                    <label class="form-check-label" for="showProjectsToggle">プロジェクトを表示</label>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- イベント詳細モーダル -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">イベント詳細</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h5 id="eventTitle"></h5>
                        <p id="eventDate" class="text-muted"></p>
                    </div>
                    <div id="eventProject" class="mb-3">
                        <strong>プロジェクト:</strong> <span id="eventProjectName"></span>
                    </div>
                    <div id="eventAssignee" class="mb-3">
                        <strong>担当者:</strong> <span id="eventAssigneeName"></span>
                    </div>
                    <div id="eventStatus" class="mb-3">
                        <strong>ステータス:</strong> <span id="eventStatusName"></span>
                    </div>
                    <div id="eventDescription" class="mb-3">
                        <strong>説明:</strong>
                        <p id="eventDescriptionText"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <a href="#" id="eventEditLink" class="btn btn-primary">編集</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // フィルターパネルの表示/非表示
            const showFilterBtn = document.getElementById('showFilterBtn');
            const closeFilterBtn = document.getElementById('closeFilterBtn');
            const filterPanel = document.getElementById('filterPanel');

            if (showFilterBtn && closeFilterBtn && filterPanel) {
                showFilterBtn.addEventListener('click', function () {
                    const bsCollapse = new bootstrap.Collapse(filterPanel);
                    bsCollapse.show();
                });

                closeFilterBtn.addEventListener('click', function () {
                    const bsCollapse = new bootstrap.Collapse(filterPanel);
                    bsCollapse.hide();
                });
            }

            // イベントデータを取得
            const events = {!! $events !!};

            // プロジェクトイベントとタスクイベントを分離
            const projectEvents = events.filter(event => event.extendedProps.type === 'project');
            const taskEvents = events.filter(event => event.extendedProps.type === 'task' || event.extendedProps.type === 'milestone');
            const holidayEvents = events.filter(event => event.extendedProps.type === 'holiday');

            // FullCalendarの初期化
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                buttonText: {
                    today: '今日',
                    month: '月',
                    week: '週',
                    day: '日',
                    list: 'リスト'
                },
                events: [...taskEvents, ...holidayEvents], // 初期状態ではプロジェクトは非表示
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                dayMaxEvents: true, // イベントが多い場合は「+more」を表示
                firstDay: 1, // 月曜始まり
                eventClick: function (info) {
                    // デフォルトのリンク動作を防止
                    info.jsEvent.preventDefault();

                    // イベントデータを取得
                    const event = info.event;
                    const extendedProps = event.extendedProps;

                    // モーダルにデータを設定
                    document.getElementById('eventTitle').textContent = event.title;
                    document.getElementById('eventDate').textContent = `${event.start.toLocaleDateString()} - ${event.end ? event.end.toLocaleDateString() : event.start.toLocaleDateString()}`;

                    // イベントタイプに応じて表示内容を変更
                    if (extendedProps.type === 'holiday') {
                        // 祝日の場合
                        document.getElementById('eventProject').style.display = 'none';
                        document.getElementById('eventAssignee').style.display = 'none';
                        document.getElementById('eventStatus').style.display = 'none';
                        document.getElementById('eventDescription').style.display = 'none';
                        document.getElementById('eventEditLink').style.display = 'none';
                    } else if (extendedProps.type === 'project') {
                        // プロジェクトの場合
                        document.getElementById('eventProject').style.display = 'none';
                        document.getElementById('eventAssignee').style.display = 'none';
                        document.getElementById('eventStatus').style.display = 'none';
                        document.getElementById('eventDescription').style.display = 'block';
                        document.getElementById('eventDescriptionText').textContent = extendedProps.description || '説明はありません';
                        document.getElementById('eventEditLink').style.display = 'inline-block';
                        document.getElementById('eventEditLink').href = event.url;
                    } else {
                        // タスクの場合
                        document.getElementById('eventProject').style.display = 'block';
                        document.getElementById('eventProjectName').textContent = extendedProps.project_title;

                        document.getElementById('eventAssignee').style.display = 'block';
                        document.getElementById('eventAssigneeName').textContent = extendedProps.assignee || '未割り当て';

                        document.getElementById('eventStatus').style.display = 'block';
                        let statusText = '';
                        switch (extendedProps.status) {
                            case 'not_started':
                                statusText = '未着手';
                                break;
                            case 'in_progress':
                                statusText = '進行中';
                                break;
                            case 'completed':
                                statusText = '完了';
                                break;
                            case 'on_hold':
                                statusText = '保留中';
                                break;
                            case 'cancelled':
                                statusText = 'キャンセル';
                                break;
                            default:
                                statusText = extendedProps.status;
                        }
                        document.getElementById('eventStatusName').textContent = statusText;

                        document.getElementById('eventDescription').style.display = 'block';
                        document.getElementById('eventDescriptionText').textContent = extendedProps.description || '説明はありません';

                        document.getElementById('eventEditLink').style.display = 'inline-block';
                        document.getElementById('eventEditLink').href = event.url;
                    }

                    // モーダルを表示
                    const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
                    eventModal.show();
                },
                eventDidMount: function (info) {
                    // マイルストーンのスタイル調整
                    if (info.event.extendedProps.type === 'milestone') {
                        info.el.style.borderRadius = '0';
                        info.el.style.height = '0';
                        info.el.style.borderLeft = '10px solid transparent';
                        info.el.style.borderRight = '10px solid transparent';
                        info.el.style.borderBottom = '10px solid ' + info.event.backgroundColor;
                        info.el.style.backgroundColor = 'transparent';
                        info.el.style.color = info.event.backgroundColor;
                    }

                    // プロジェクトイベントのスタイル調整
                    if (info.event.extendedProps.type === 'project') {
                        info.el.style.opacity = '0.7';
                        info.el.style.border = '1px dashed ' + info.event.borderColor;
                    }
                },
                // 祝日の背景色を設定
                dayCellDidMount: function (info) {
                    // 土曜日
                    if (info.date.getDay() === 6) {
                        info.el.classList.add('fc-day-sat');
                    }
                    // 日曜日
                    else if (info.date.getDay() === 0) {
                        info.el.classList.add('fc-day-sun');
                    }
                }
            });

            calendar.render();

            // プロジェクト表示切替
            const showProjectsToggle = document.getElementById('showProjectsToggle');

            showProjectsToggle.addEventListener('change', function () {
                if (this.checked) {
                    // プロジェクトを表示
                    calendar.getEventSources().forEach(source => source.remove());
                    calendar.addEventSource([...projectEvents, ...taskEvents, ...holidayEvents]);
                } else {
                    // プロジェクトを非表示
                    calendar.getEventSources().forEach(source => source.remove());
                    calendar.addEventSource([...taskEvents, ...holidayEvents]);
                }
            });
        });
    </script>
@endsection
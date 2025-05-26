@extends('layouts.app')

@section('title', 'カレンダー')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>カレンダー</h1>
        <div>
            <button class="btn btn-outline-primary me-2" type="button" id="showFilterBtn" data-bs-toggle="collapse"
                data-bs-target="#filterPanel">
                <i class="fas fa-filter"></i> フィルター
            </button>
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規衣装案件
                </a>
            @endcan
        </div>
    </div>

    <x-filter-panel :action="route('calendar.index')" :filters="$filters" :all-projects="$allProjects"
        :all-characters="$charactersForFilter" {{-- ★ :all-charactersを追加 --}} :all-assignees="$allAssignees"
        :status-options="$statusOptions" />
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">カレンダー</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showProjectsToggle" checked>
                    <label class="form-check-label" for="showProjectsToggle">衣装案件を表示</label>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

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
                        <strong>衣装案件:</strong> <span id="eventProjectName"></span>
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

@section('styles')
    <style>
        /* カレンダー用の追加スタイル */
        .calendar-container {
            height: calc(100vh - 300px);
            min-height: 500px;
        }

        .fc-daygrid-day-number {
            padding: 5px;
        }

        .fc-daygrid-day-top {
            justify-content: center;
        }

        .fc-day-sat {
            background-color: #e6f2ff;
        }

        .fc-day-sun {
            background-color: #ffe6e6;
        }

        .fc-day-today {
            background-color: #fffbcc !important;
        }

        .fc-event {
            cursor: pointer;
            border-radius: 3px;
            margin: 1px 0;
        }

        .fc-event-title {
            font-size: 0.85em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .calendar-container {
                height: calc(100vh - 250px);
                min-height: 400px;
            }

            .fc .fc-toolbar {
                flex-direction: column;
                gap: 10px;
            }

            .fc .fc-toolbar-title {
                font-size: 1.2em;
                text-align: center;
            }

            .fc .fc-button {
                padding: 0.3em 0.5em;
                font-size: 0.9em;
            }

            .fc-header-toolbar {
                margin-bottom: 0.5em !important;
            }

            .fc-daygrid-day-number {
                padding: 2px;
                font-size: 0.8em;
            }

            .fc-event-title {
                font-size: 0.75em;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // イベントデータを取得
            const events = {!! $events !!};

            // 衣装案件イベントと工程イベントを分離
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
                    right: 'dayGridMonth,listMonth'
                },
                buttonText: {
                    today: '今日',
                    month: '月',
                    list: 'リスト'
                },
                events: [...taskEvents, ...holidayEvents], // 初期状態では衣装案件は非表示
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: false
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
                        // 衣装案件の場合
                        document.getElementById('eventProject').style.display = 'none';
                        document.getElementById('eventAssignee').style.display = 'none';
                        document.getElementById('eventStatus').style.display = 'none';
                        document.getElementById('eventDescription').style.display = 'block';
                        document.getElementById('eventDescriptionText').textContent = extendedProps.description || '説明はありません';
                        document.getElementById('eventEditLink').style.display = 'inline-block';
                        document.getElementById('eventEditLink').href = event.url;
                    } else {
                        // 工程の場合
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
                }
            });

            calendar.render();

            // 衣装案件表示/非表示の切り替え
            const showProjectsToggle = document.getElementById('showProjectsToggle');
            showProjectsToggle.addEventListener('change', function () {
                if (this.checked) {
                    // 衣装案件を表示
                    projectEvents.forEach(event => {
                        calendar.addEvent(event);
                    });
                } else {
                    // 衣装案件を非表示
                    const events = calendar.getEvents();
                    events.forEach(event => {
                        if (event.extendedProps.type === 'project') {
                            event.remove();
                        }
                    });
                }
            });

            // 初期表示時に衣装案件を表示
            if (showProjectsToggle.checked) {
                projectEvents.forEach(event => {
                    calendar.addEvent(event);
                });
            }
        });
    </script>
@endsection
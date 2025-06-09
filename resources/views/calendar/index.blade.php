@extends('layouts.app')

@section('title', 'カレンダー')

@push('styles')
    <style>
        /* FullCalendarのデフォルトのoverflow:hiddenを上書きして、内容がはみ出せるようにする */
        .fc-event-main {
            overflow: visible !important;
        }

        /* カスタムイベントのコンテナ */
        .custom-fc-content {
            padding: 2px 4px;
            overflow: hidden;
            font-size: 12px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* イベントタイトル */
        .custom-fc-content .fc-event-title {
            font-weight: 600;
            white-space: normal;
            /* タイトルを折り返し表示 */
            line-height: 1.3;
        }

        /* 週・日・リスト表示での詳細情報エリア */
        .custom-fc-content .fc-event-details {
            margin-top: 4px;
            font-size: 11px;
            opacity: 0.95;
        }

        /* キャラクター名や時間などの各詳細項目 */
        .fc-event-detail-item {
            white-space: normal;
            /* テキストがコンテナ幅で折り返すように設定 */
            word-break: break-all;
            /* コンテナが非常に狭い場合でも強制的に改行して見切れを防ぐ */
            line-height: 1.4;
        }

        .fc-event-detail-item .fa-fw {
            margin-right: 4px;
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
@endpush


@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ array_filter($filters) ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">カレンダー</h1>
            <div>
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <i class="fas fa-chevron-down fa-xs ml-2" x-show="!filtersOpen"></i>
                    <i class="fas fa-chevron-up fa-xs ml-2" x-show="filtersOpen" style="display:none;"></i>
                </x-secondary-button>
            </div>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6" style="display: none;">
            <x-filter-panel :action="route('calendar.index')" :filters="$filters" :all-projects="$allProjects"
                :all-characters="$charactersForFilter" :all-assignees="$allAssignees" :status-options="$statusOptions" />
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-4 sm:p-6">
            <div id="calendar" data-events='{!! $events !!}'></div>
        </div>
    </div>

    {{-- イベント詳細表示用モーダル --}}
    <x-modal name="eventDetailModal" maxWidth="xl">
        <div class="p-6" x-data="{
                    type: '', title: '', start: '', end: '', color: '#ffffff', allDay: true,
                    url: '', description: '', assignee: '', status: '', project_title: '',
                    statusLabels: {{ json_encode($statusOptions) }}
                }" @open-modal.window="if ($event.detail.name === 'eventDetailModal') {
                    const event = $event.detail.eventData;
                    type = event.extendedProps.type;
                    title = event.title;
                    start = event.startStr;
                    allDay = event.allDay;
                    if (event.end) {
                        if (allDay) {
                            end = new Date(new Date(event.endStr).setDate(new Date(event.endStr).getDate() - 1)).toLocaleDateString('ja-JP');
                        } else {
                            end = new Date(event.endStr).toLocaleString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                        }
                    } else {
                         end = new Date(start).toLocaleDateString('ja-JP');
                    }
                    color = event.backgroundColor;
                    url = event.url;
                    description = event.extendedProps.description;
                    assignee = event.extendedProps.assignee_names; // assigneeから変更
                    status = event.extendedProps.status;
                    project_title = event.extendedProps.project_title;
                }">
            <div class="flex justify-between items-start pb-3 border-b dark:border-gray-600">
                <div class="flex items-center">
                    <div class="w-4 h-4 rounded-full mr-3" :style="`background-color: ${color}`"></div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100" x-text="title"></h2>
                </div>
                <button @click="$dispatch('close-modal', { name: 'eventDetailModal' })"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="mt-4 space-y-3 text-sm">
                <div x-show="project_title">
                    <strong class="font-semibold text-gray-600 dark:text-gray-400 w-20 inline-block">案件名:</strong>
                    <span class="text-gray-800 dark:text-gray-200" x-text="project_title"></span>
                </div>
                <div>
                    <strong class="font-semibold text-gray-600 dark:text-gray-400 w-20 inline-block"
                        x-text="allDay ? '期間:' : '日時:'"></strong>
                    <span class="text-gray-800 dark:text-gray-200"
                        x-text="allDay ? `${new Date(start).toLocaleDateString('ja-JP')} 〜 ${end}` : `${new Date(start).toLocaleString('ja-JP', { hour: '2-digit', minute: '2-digit' })} 〜 ${new Date(end).toLocaleString('ja-JP', { hour: '2-digit', minute: '2-digit' })}`"></span>
                </div>
                <div x-show="assignee">
                    <strong class="font-semibold text-gray-600 dark:text-gray-400 w-20 inline-block">担当者:</strong>
                    <span class="text-gray-800 dark:text-gray-200" x-text="assignee"></span>
                </div>
                <div x-show="status">
                    <strong class="font-semibold text-gray-600 dark:text-gray-400 w-20 inline-block">ステータス:</strong>
                    <span class="text-gray-800 dark:text-gray-200" x-text="statusLabels[status] || status"></span>
                </div>
                <div x-show="description">
                    <strong class="font-semibold text-gray-600 dark:text-gray-400 w-20 block mb-1">説明:</strong>
                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap" x-text="description"></p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a :href="url" x-show="url"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <span x-text="type === 'project' ? '案件詳細へ' : '工程を編集'"></span>
                </a>
                <x-secondary-button @click="$dispatch('close-modal', { name: 'eventDetailModal' })" class="ml-2">
                    閉じる
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                const eventsData = JSON.parse(calendarEl.getAttribute('data-events') || '[]');

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },

                    locale: 'ja',
                    buttonText: { today: '今日', month: '月', week: '週', day: '日', list: 'リスト' },
                    // ▼▼▼ 変更点: 初期表示を「日」ビューに設定 ▼▼▼
                    initialView: 'timeGridDay',
                    navLinks: true,
                    dayMaxEvents: true,
                    events: eventsData,

                    slotMinTime: '00:00:00',
                    slotMaxTime: '24:00:00',
                    slotDuration: '00:30:00',
                    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

                    eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'narrow' },
                    listDayFormat: { month: 'long', day: 'numeric', weekday: 'short' },
                    listDaySideFormat: false,

                    // ▼▼▼ 変更点: eventContentの表示内容を修正 ▼▼▼
                    eventContent: function (arg) {
                        let props = arg.event.extendedProps;
                        if (props.type === 'task' || props.type === 'milestone') {
                            let container = document.createElement('div');
                            container.classList.add('custom-fc-content');

                            let titleEl = document.createElement('div');
                            titleEl.classList.add('fc-event-title');
                            titleEl.innerText = arg.event.title;
                            container.appendChild(titleEl);

                            if (arg.view.type !== 'dayGridMonth') {
                                let detailsEl = document.createElement('div');
                                detailsEl.classList.add('fc-event-details');

                                // キャラクター名
                                let characterEl = document.createElement('div');
                                characterEl.classList.add('fc-event-detail-item');
                                characterEl.innerHTML = `<i class="fas fa-dragon fa-fw"></i> ${props.character_name || '未設定'}`;
                                detailsEl.appendChild(characterEl);

                                // 担当者名
                                if (props.assignee_names) {
                                    let assigneeEl = document.createElement('div');
                                    assigneeEl.classList.add('fc-event-detail-item');
                                    assigneeEl.innerHTML = `<i class="fas fa-user fa-fw"></i> ${props.assignee_names}`;
                                    detailsEl.appendChild(assigneeEl);
                                }

                                // 開始～終了時間
                                let timeRangeEl = document.createElement('div');
                                timeRangeEl.classList.add('fc-event-detail-item');
                                let start = arg.event.start;
                                let end = arg.event.end;
                                let timeText = '';

                                if (start) {
                                    if (arg.event.allDay) {
                                        timeText = '終日';
                                    } else {
                                        const formatOptions = { hour: '2-digit', minute: '2-digit', hour12: false };
                                        const startTime = start.toLocaleTimeString('ja-JP', formatOptions);
                                        const endTime = end ? end.toLocaleTimeString('ja-JP', formatOptions) : '';
                                        timeText = `${startTime} 〜 ${endTime}`;
                                    }
                                }
                                timeRangeEl.innerHTML = `<i class="far fa-clock fa-fw"></i> ${timeText}`;
                                detailsEl.appendChild(timeRangeEl);

                                container.appendChild(detailsEl);
                            }

                            return { domNodes: [container] };
                        }
                        return;
                    },

                    eventClick: function (info) {
                        info.jsEvent.preventDefault();

                        if (info.event.extendedProps.type === 'holiday') {
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: {
                                name: 'eventDetailModal',
                                eventData: info.event
                            }
                        }));
                    },
                });

                calendar.render();
            }
        });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'カレンダー')

{{-- ▼▼▼ 変更点: ツールチップのデザインを全面的に刷新 ▼▼▼ --}}
@push('styles')
    <style>
        /* --- イベント自体のスタイル調整 --- */
        .fc-event-main {
            overflow: visible !important;
        }

        .custom-fc-content {
            padding: 2px 4px;
            overflow: hidden;
            font-size: 12px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .custom-fc-content .fc-event-title {
            font-weight: 600;
            white-space: normal;
            line-height: 1.3;
        }

        .custom-fc-content .fc-event-details {
            margin-top: 4px;
            font-size: 11px;
            opacity: 0.95;
        }

        .fc-event-detail-item {
            white-space: normal;
            word-break: break-all;
            line-height: 1.4;
        }

        .fc-event-detail-item .fa-fw {
            margin-right: 4px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* --- 新しいツールチップのスタイル --- */
        /* ツールチップのコンテンツラッパー */
        .calendar-tooltip-content {
            padding: 8px 12px;
            /* 内側の余白を少し設定 */
        }

        /* ツールチップのタイトル */
        .tooltip-title {
            font-size: 1em;
            /* 親要素(12px)の1倍 */
            font-weight: 600;
            color: #fff;
            margin: 0 0 6px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #555;
        }

        /* 詳細リスト(dl) */
        .tooltip-details {
            margin: 0;
            padding: 0;
            font-size: 0.9em;
            /* 親要素(12px)の0.9倍 */
        }

        /* 各詳細行 */
        .tooltip-detail-row {
            display: flex;
            align-items: flex-start;
            /* 上揃えで複数行に対応 */
            line-height: 1.4;
            /* 行間をコンパクトに */
            padding: 2px 0;
            /* 各行の上下の余白を最小限に */
        }

        /* ラベル(dt) */
        .tooltip-detail-row dt {
            font-weight: 500;
            color: #a0aec0;
            /* Tailwindのgray-400相当 */
            flex-shrink: 0;
            width: 65px;
            /* ラベルの幅を固定して揃える */
        }

        /* 値(dd) */
        .tooltip-detail-row dd {
            margin: 0;
            flex-grow: 1;
            color: #e2e8f0;
            /* Tailwindのgray-300相当 */
            word-break: break-all;
        }
    </style>
@endpush


@section('content')
    @php
        $shouldFiltersBeOpen = array_filter($filters) && !request()->has('close_filters');
    @endphp
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ $shouldFiltersBeOpen ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">カレンダー</h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <i class="fas fa-chevron-down fa-xs ml-2" x-show="!filtersOpen"></i>
                    <i class="fas fa-chevron-up fa-xs ml-2" x-show="filtersOpen" style="display:none;"></i>
                </x-secondary-button>

                @auth
                    @php
                        $isFilteringBySelf = isset($filters['assignee_id']) && $filters['assignee_id'] == Auth::id();
                        $baseClass = 'inline-flex items-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150';
                        $activeClass = 'bg-blue-600 border-transparent text-white hover:bg-blue-700 focus:ring-blue-500';
                        $inactiveClass = 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-indigo-500';
                    @endphp
                    <a href="{{ route('calendar.index', ['assignee_id' => Auth::id(), 'close_filters' => 1]) }}"
                        class="{{ $baseClass }} {{ $isFilteringBySelf ? $activeClass : $inactiveClass }}">
                        <i class="fas fa-user-check mr-2"></i>担当:自分
                    </a>
                @endauth
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

    <div id="calendar-event-tooltip"
        class="fixed z-[100] hidden rounded-md bg-gray-900 px-0 py-0 text-xs font-medium text-white shadow-lg dark:bg-gray-800 border border-gray-700 max-w-xs"
        role="tooltip">
    </div>

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
                assignee = event.extendedProps.assignee_names;
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
                const statusOptions = {!! json_encode($statusOptions) !!};

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },

                    locale: 'ja',
                    buttonText: { today: '今日', month: '月', week: '週', day: '日', list: 'リスト' },
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

                                let characterEl = document.createElement('div');
                                characterEl.classList.add('fc-event-detail-item');
                                characterEl.innerHTML = `<i class="fas fa-dragon fa-fw"></i> ${props.character_name || '未設定'}`;
                                detailsEl.appendChild(characterEl);

                                if (props.assignee_names) {
                                    let assigneeEl = document.createElement('div');
                                    assigneeEl.classList.add('fc-event-detail-item');
                                    assigneeEl.innerHTML = `<i class="fas fa-user fa-fw"></i> ${props.assignee_names}`;
                                    detailsEl.appendChild(assigneeEl);
                                }

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

                    // ▼▼▼ 変更点: ツールチップのHTML生成部分を新しいデザインに刷新 ▼▼▼
                    eventMouseEnter: function (info) {
                        if (info.event.extendedProps.type !== 'task' && info.event.extendedProps.type !== 'milestone') {
                            return;
                        }

                        const tooltip = document.getElementById('calendar-event-tooltip');
                        if (!tooltip) return;

                        const props = info.event.extendedProps;
                        const statusText = statusOptions[props.status] || props.status || '不明';

                        const contentHtml = `
                        <div class="calendar-tooltip-content">
                            <h3 class="tooltip-title">${info.event.title}</h3>
                            <dl class="tooltip-details">
                                <div class="tooltip-detail-row">
                                    <dt>案件:</dt>
                                    <dd>${props.project_title || ''}</dd>
                                </div>
                                <div class="tooltip-detail-row">
                                    <dt>キャラクター:</dt>
                                    <dd>${props.character_name || '未設定'}</dd>
                                </div>
                                 <div class="tooltip-detail-row">
                                    <dt>担当者:</dt>
                                    <dd>${props.assignee_names || '未設定'}</dd>
                                </div>
                                <div class="tooltip-detail-row">
                                    <dt>ステータス:</dt>
                                    <dd>${statusText}</dd>
                                </div>
                                ${props.description ? `
                                <div class="tooltip-detail-row">
                                    <dt>説明:</dt>
                                    <dd>${props.description}</dd>
                                </div>` : ''}
                            </dl>
                        </div>
                    `;

                        tooltip.innerHTML = contentHtml;
                        tooltip.style.display = 'block';

                        const eventRect = info.el.getBoundingClientRect();
                        const tooltipRect = tooltip.getBoundingClientRect();
                        let top = eventRect.top + window.scrollY - tooltipRect.height - 5;
                        let left = eventRect.left + window.scrollX + (eventRect.width / 2) - (tooltipRect.width / 2);

                        if (top < window.scrollY) {
                            top = eventRect.bottom + window.scrollY + 5;
                        }
                        if (left < window.scrollX) {
                            left = window.scrollX + 5;
                        }
                        if (left + tooltipRect.width > window.innerWidth) {
                            left = window.innerWidth - tooltipRect.width - 5;
                        }

                        tooltip.style.top = `${top}px`;
                        tooltip.style.left = `${left}px`;
                    },

                    eventMouseLeave: function (info) {
                        const tooltip = document.getElementById('calendar-event-tooltip');
                        if (tooltip) {
                            tooltip.style.display = 'none';
                        }
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
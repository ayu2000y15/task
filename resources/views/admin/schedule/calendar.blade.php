@extends('layouts.app')
@section('title', '全員スケジュール')

@push('styles')
    <style>
        .fc .fc-daygrid-day-top {
            flex-direction: row;
            justify-content: flex-start;
        }

        .fc .fc-daygrid-day-number {
            padding: 4px;
        }

        .custom-fc-content {
            padding: 2px 4px;
            overflow: hidden;
            font-size: 12px;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .fc-event-title-ellipsis {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ツールチップの基本スタイル */
        .custom-calendar-tooltip {
            /* JSで位置を指定するため、absoluteに */
            position: absolute;
            /* JSで表示を制御するため、初期状態は非表示 */
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.2s, visibility 0.2s;

            /* 以下はデザイン部分 */
            width: max-content;
            max-width: 300px;
            background-color: #1f2937; /* gray-800 */
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 8px 12px;
            z-index: 1001; /* カレンダーより手前に表示 */
            font-size: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .dark .custom-calendar-tooltip {
             background-color: #374151; /* gray-700 */
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">全員のスケジュール</h1>
        </div>

        {{-- デフォルトシフト表示 --}}
        <div x-data="{ open: false }" class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div @click="open = !open"
                class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer">
                <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300"><i
                        class="fas fa-cog mr-2"></i>全ユーザーのデフォルトシフト</h5>
                <button><i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i></button>
            </div>
            <div x-show="open" x-collapse class="p-4 overflow-x-auto">
                <table class="min-w-full text-xs border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="p-1 border dark:border-gray-600 text-left">ユーザー</th>
                            @foreach (['日', '月', '火', '水', '木', '金', '土'] as $day)
                                @if('土' === $day)
                                    <th class="p-1 border text-blue-500 dark:border-gray-600">{{ $day }}</th>
                                @elseif('日' === $day)
                                    <th class="p-1 border text-red-500 dark:border-gray-600">{{ $day }}</th>
                                @else
                                    <th class="p-1 border dark:border-gray-600">{{ $day }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-b dark:border-gray-600">
                                <td class="p-1 border-x dark:border-gray-600 font-semibold">{{ $user->name }}</td>
                                @foreach ([0, 1, 2, 3, 4, 5, 6] as $dayOfWeek)
                                    @php
                                        $pattern = $allDefaultPatterns->get($user->id, collect())->firstWhere('day_of_week', $dayOfWeek);
                                    @endphp
                                    <td class="p-1 border-x dark:border-gray-600 text-center">
                                        @if($pattern && $pattern->is_workday)
                                            <div>
                                                @if($pattern->location === 'remote')
                                                    <i class="fas fa-home text-blue-500" title="在宅勤務"></i>
                                                @else
                                                    <i class="fas fa-building text-green-500" title="出勤"></i>
                                                @endif
                                            </div>
                                            <div class="text-xs mt-1">
                                                {{ \Carbon\Carbon::parse($pattern->start_time)->format('H:i') }}
                                                <span class="text-gray-400">-</span>
                                                {{ \Carbon\Carbon::parse($pattern->end_time)->format('H:i') }}
                                            </div>
                                        @else
                                            <span class="text-gray-400">休日</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-2 sm:p-4">
            <div id="schedule-calendar"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('schedule-calendar');
    if (!calendarEl) return;

    // 1. ページに単一のツールチップ要素を作成して追加
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-calendar-tooltip';
    document.body.appendChild(tooltip);

    let calendar;

    function renderCalendar() {
        if (calendar) {
            calendar.destroy();
        }
        const isMobile = window.innerWidth < 768;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: isMobile ? 'listWeek' : 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            locale: 'ja',
            buttonText: {
                today: '今日',
                month: '月',
                week: '週',
                day: '日',
                list: 'リスト'
            },
            eventDisplay: 'list-item',
            dayMaxEvents: true,
            events: `{{ route('api.schedule.events') }}`,

            eventContent: function(arg) {
                let container = document.createElement('div');
                container.classList.add('custom-fc-content'); // flex, gap-4pxが適用される

                const props = arg.event.extendedProps;

                // 勤務場所アイコンを追加 (work と location_only のみ)
                if (props.type === 'work' || props.type === 'location_only') {
                    const locationIcon = document.createElement('i');
                    // fa-fw を付けておくとアイコンの幅が揃ってきれいです
                    locationIcon.className = 'fas fa-fw';

                    if (props.location === 'remote') {
                        locationIcon.classList.add('fa-home', 'text-blue-500');
                        locationIcon.title = '在宅勤務';
                    } else {
                        locationIcon.classList.add('fa-building', 'text-green-500');
                        locationIcon.title = '出勤';
                    }
                    container.appendChild(locationIcon);
                }

                // イベントタイトルを追加
                let titleEl = document.createElement('div');
                titleEl.classList.add('fc-event-title-ellipsis');
                titleEl.innerText = arg.event.title;
                container.appendChild(titleEl);

                // 祝日の場合は文字を赤く、太字にする
                if (props.type === 'holiday') {
                    titleEl.classList.add('text-red-600', 'dark:text-red-400', 'font-semibold');
                }

                // メモアイコンを追加 (monthly.blade.phpと合わせてfa-comment-altに変更)
                if (props.notes) {
                    let notesIcon = document.createElement('i');
                    notesIcon.className = 'fas fa-comment-alt text-gray-400 ml-1 flex-shrink-0';
                    notesIcon.title = props.notes;
                    container.appendChild(notesIcon);
                }

                return { domNodes: [container] };
            },

            eventMouseEnter: function(info) {
                const props = info.event.extendedProps;
                let tooltipHtml = '';

                if (props.location) {
                    const locationText = props.location === 'remote' ? '在宅' : '出勤';
                    tooltipHtml += `<div><strong class="font-semibold">場所:</strong> ${locationText}</div>`;
                }

                tooltipHtml += `<div><strong class="font-semibold">内容:</strong> ${info.event.title}</div>`;

                if (props.notes) {
                    tooltipHtml += `<hr class="my-1 border-gray-500">`;
                    const sanitizedNotes = props.notes.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    tooltipHtml += `<div><strong class="font-semibold">メモ:</strong> ${sanitizedNotes}</div>`;
                }

                if (tooltipHtml) {
                    // ツールチップに内容をセット
                    tooltip.innerHTML = tooltipHtml;

                    // ツールチップの位置を計算
                    const rect = info.el.getBoundingClientRect();
                    tooltip.style.left = `${rect.left + window.scrollX + rect.width / 2}px`;
                    tooltip.style.top = `${rect.top + window.scrollY - 10}px`; // 10px上に表示

                    // 中央揃えと上方向へのオフセット
                    tooltip.style.transform = 'translate(-50%, -100%)';

                    // 表示
                    tooltip.style.visibility = 'visible';
                    tooltip.style.opacity = '1';
                }
            },

            eventMouseLeave: function(info) {
                // 非表示
                tooltip.style.visibility = 'hidden';
                tooltip.style.opacity = '0';
            }
        });

        calendar.render();
    }

    renderCalendar();

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(renderCalendar, 250);
    });
});
</script>
@endpush
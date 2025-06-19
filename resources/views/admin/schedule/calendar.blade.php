@extends('layouts.app')
@section('title', '全員スケジュール')

@push('styles')
    <style>
        /* ... CSSは変更なし ... */
        .fc .fc-daygrid-day-top {
            flex-direction: row;
            justify-content: flex-start;
        }

        .fc .fc-daygrid-day-number {
            padding: 4px;
        }

        .fc-event-main {
            overflow: visible !important;
            height: 100%;
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

        .fc-event.holiday-event .fc-event-title {
            color: #ef4444; /* red-500 */
            font-weight: 700;
        }
        .dark .fc-event.holiday-event .fc-event-title {
            color: #f87171; /* red-400 */
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">全員のスケジュール</h1>
        </div>

        {{-- デフォルトシフト表示 (変更なし) --}}
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
                            <th class="p-2 border dark:border-gray-600 text-left">ユーザー</th>
                            @foreach (['日', '月', '火', '水', '木', '金', '土'] as $day)
                                @if('土' === $day)
                                    <th class="p-2 border text-blue-500 dark:border-gray-600">{{ $day }}</th>
                                @elseif('日' === $day)
                                    <th class="p-2 border text-red-500 dark:border-gray-600">{{ $day }}</th>
                                @else
                                    <th class="p-2 border dark:border-gray-600">{{ $day }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-b dark:border-gray-600">
                                <td class="p-2 border-x dark:border-gray-600 font-semibold">{{ $user->name }}</td>
                                @foreach ([0, 1, 2, 3, 4, 5, 6] as $dayOfWeek)
                                    @php
                                        $pattern = $allDefaultPatterns->get($user->id, collect())->firstWhere('day_of_week', $dayOfWeek);
                                    @endphp
                                    <td class="p-2 border-x dark:border-gray-600 text-center">
                                        @if($pattern && $pattern->is_workday)
                                            {{ \Carbon\Carbon::parse($pattern->start_time)->format('H:i') }}<br>-<br>{{ \Carbon\Carbon::parse($pattern->end_time)->format('H:i') }}
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

        {{-- ▼▼▼【data-events属性を削除】▼▼▼ --}}
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

            // ▼▼▼【ここを追加】イベントの表示形式を「リストアイテム（バッジ）」に変更 ▼▼▼
            eventDisplay: 'list-item',
            // ▲▲▲【追加ここまで】▲▲▲

            dayMaxEvents: true,
            events: function(fetchInfo, successCallback, failureCallback) {
                const start = fetchInfo.start.toISOString().slice(0, 10);
                const end = fetchInfo.end.toISOString().slice(0, 10);

                fetch(`{{ route('api.schedule.events') }}?start=${start}&end=${end}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        successCallback(data);
                    })
                    .catch(error => {
                        console.error('Error fetching calendar events:', error);
                        failureCallback(error);
                    });
            },
            eventContent: function(arg) {
                // eventDisplay: 'list-item' を使用する場合、FullCalendarが自動で
                // 色付きの点（ドット）を付けてくれるので、ここではタイトルのみを返します。
                // 以前追加したカスタムCSSにより、フォントや改行は適切に処理されます。
                let container = document.createElement('div');
                container.classList.add('custom-fc-content');
                let titleEl = document.createElement('div');
                titleEl.classList.add('fc-event-title');
                titleEl.innerText = arg.event.title;
                container.appendChild(titleEl);
                return { domNodes: [container] };
            },
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
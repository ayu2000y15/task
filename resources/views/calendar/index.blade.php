@extends('layouts.app')

@section('title', '工程カレンダー (' . $targetMonth->format('Y年n月') . ')')

@section('content')
    @php
        $shouldFiltersBeOpen = array_filter($filters) && !request()->has('close_filters');
    @endphp
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ $shouldFiltersBeOpen ? 'true' : 'false' }} }">
        {{-- ▼▼▼【ここからヘッダー部分を修正】▼▼▼ --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            {{-- 月ナビゲーションとタイトル --}}
            <div class="flex items-center space-x-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 hidden sm:block">工程カレンダー</h1>

            </div>
            {{-- 操作ボタングループ --}}
            <div class="flex items-center space-x-2">
                <x-secondary-button as="a"
                    href="{{ route('admin.schedule.calendar', ['month' => $targetMonth->format('Y-m')]) }}">
                    <i class="fas fa-users mr-2"></i> 全員のスケジュール
                </x-secondary-button>
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                </x-secondary-button>
                <a href="{{ route('calendar.index', array_merge($filters, ['month' => $targetMonth->copy()->subMonth()->format('Y-m')])) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i
                        class="fas fa-chevron-left"></i> 前月</a>
                <span class="font-semibold text-lg">{{ $targetMonth->format('Y年n月') }}</span>
                <a href="{{ route('calendar.index', array_merge($filters, ['month' => $targetMonth->copy()->addMonth()->format('Y-m')])) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月 <i
                        class="fas fa-chevron-right"></i></a>
            </div>
        </div>
        {{-- ▲▲▲【ヘッダー部分修正ここまで】▲▲▲ --}}

        {{-- ▼▼▼【凡例を追加】▼▼▼ --}}
        <div class="flex items-center gap-x-4 gap-y-2 text-xs flex-wrap mb-4">

            <span class="flex items-center gap-1"><i class="fas fa-tasks text-blue-500"></i> 工程</span>
            <span class="flex items-center gap-1"><i class="fas fa-flag text-red-500"></i> 予定</span>
            <span class="flex items-center gap-1"><i class="fas fa-bed text-green-500"></i> 個人休</span>
            <span class="flex items-center gap-1"><i class="fas fa-glass-cheers text-pink-500"></i> 祝日</span>
        </div>
        {{-- ▲▲▲【凡例追加ここまで】▲▲▲ --}}


        {{-- フィルターパネル --}}
        <div x-show="filtersOpen" x-collapse class="mb-6" style="display: none;">
            <x-filter-panel :action="route('calendar.index')" :filters="$filters" :all-projects="$allProjects"
                :all-characters="$charactersForFilter" :all-assignees="$allAssignees" :status-options="$statusOptions" />
        </div>

        {{-- PC用グリッドカレンダー --}}
        <div class="hidden md:block bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="grid grid-cols-7">
                @foreach (['日', '月', '火', '水', '木', '金', '土'] as $dayOfWeek)
                    <div class="py-2 text-center text-sm font-semibold text-gray-600 dark:text-gray-300 border-b dark:border-gray-700
                            @if($dayOfWeek === '土') bg-blue-50 dark:bg-blue-900/30 @endif
                            @if($dayOfWeek === '日') bg-red-50 dark:bg-red-900/30 @endif">
                        {{ $dayOfWeek }}
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 min-h-[75vh]">
                @foreach ($calendarData as $dateString => $dayData)
                    @php
                        $date = $dayData['date'];
                        $isPublicHoliday = $dayData['public_holiday'];
                        $isToday = $date->isToday();
                    @endphp
                    <div class="relative p-2 border-t border-r border-gray-200 dark:border-gray-700
                            {{ !$dayData['is_current_month'] ? 'bg-gray-50 dark:bg-gray-800/50' : '' }}
                            {{ $date->isSaturday() ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}
                            {{ $date->isSunday() ? 'bg-red-50 dark:bg-red-900/30' : '' }}
                            {{ $isPublicHoliday && $dayData['is_current_month'] ? 'bg-green-50 dark:bg-green-900/30' : '' }}
                        ">
                        <div
                            class="text-sm font-semibold {{ $isToday ? 'text-white bg-blue-500 rounded-full w-6 h-6 flex items-center justify-center' : '' }}">
                            {{ $date->day }}
                        </div>
                        @if ($filters['project_id'])
                            <a href="{{ route('projects.tasks.create', ['project' => $filters['project_id'], 'type' => 'milestone', 'date' => $dateString]) }}"
                                class="flex items-center justify-center w-5 h-5 text-gray-400 hover:text-blue-500 hover:bg-blue-100 dark:hover:bg-gray-700 rounded-full transition"
                                title="{{ $date->format('n/j') }}に予定を追加">
                                <i class="fas fa-plus fa-xs"></i>
                            </a>
                        @endif
                        @if ($isPublicHoliday && !$dayData['schedules']->where('type', 'holiday')->count() > 1)
                            <div class="text-xs text-green-600 truncate" title="{{ $isPublicHoliday->name }}">
                                {{ $isPublicHoliday->name }}</div>
                        @endif
                        <div class="mt-1 space-y-1 text-xs">
                            @foreach ($dayData['schedules'] as $schedule)
                                @include('calendar.partials.event-item', ['schedule' => $schedule])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- スマホ用リスト表示 --}}
        <div class="md:hidden space-y-4">
            @foreach ($calendarData as $dateString => $dayData)
                @if ($dayData['is_current_month'] && (!$dayData['schedules']->isEmpty() || $dayData['public_holiday']))
                    @php
                        $date = $dayData['date'];
                        $isPublicHoliday = $dayData['public_holiday'];
                    @endphp
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <div class="font-semibold border-b pb-2 mb-3
                                    {{ $date->isSaturday() ? 'text-blue-600 dark:text-blue-400' : '' }}
                                    {{ $date->isSunday() ? 'text-red-500 dark:text-red-400' : '' }}
                                    {{ $isPublicHoliday ? 'text-green-600 dark:text-green-400' : '' }}
                                ">
                            {{ $date->format('n/j') }} ({{ $date->isoFormat('ddd') }})
                            @if ($isPublicHoliday)
                                <span class="ml-2 text-xs font-normal">({{ $isPublicHoliday->name }})</span>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @forelse ($dayData['schedules'] as $schedule)
                                @include('calendar.partials.event-item', ['schedule' => $schedule])
                            @empty
                                <p class="text-xs text-gray-500">予定されている工程はありません</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection
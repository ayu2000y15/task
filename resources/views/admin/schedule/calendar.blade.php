@extends('layouts.app')

@section('title', '全員のスケジュール (' . $targetMonth->format('Y年n月') . ')')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- ヘッダーと月ナビゲーション --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">全員のスケジュール</h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button as="a" href="{{ route('calendar.index') }}">
                    <i class="fas fa-tasks mr-2"></i> 工程カレンダーへ
                </x-secondary-button>
                <a href="{{ route('admin.schedule.calendar', ['month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i
                        class="fas fa-chevron-left"></i> 前月</a>
                <span class="font-semibold text-lg">{{ $targetMonth->format('Y年n月') }}</span>
                <a href="{{ route('admin.schedule.calendar', ['month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月 <i
                        class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        {{-- デフォルトスケジュール表示 --}}
        <div x-data="{ open: false }" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-md border dark:border-gray-700">
            <div @click="open = !open" class="px-4 py-3 border-b dark:border-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <h2 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center">
                    <i class="fas fa-user-clock mr-3 text-gray-500"></i>
                    全ユーザーのデフォルトスケジュール
                </h2>
                <i class="fas text-gray-500" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
            <div x-show="open" x-collapse style="display: none;">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">ユーザー名</th>
                                @foreach (['日', '月', '火', '水', '木', '金', '土'] as $day)
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                             @forelse ($activeUsers as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-3 py-2 font-bold text-gray-800 dark:text-gray-100 whitespace-nowrap">{{ $user->name }}</td>
                                    @php
                                        $userPatterns = $allDefaultPatterns->get($user->id);
                                    @endphp
                                    @for ($i = 0; $i < 7; $i++)
                                        @php
                                            $pattern = $userPatterns ? $userPatterns->firstWhere('day_of_week', $i) : null;
                                        @endphp
                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                            @if ($pattern && $pattern->is_workday)
                                                <div class="flex items-center justify-center">
                                                    @if($pattern->location === 'remote')
                                                        <i class="fas fa-home text-blue-500 mr-1.5" title="在宅"></i>
                                                    @else
                                                        <i class="fas fa-building text-green-500 mr-1.5" title="出勤"></i>
                                                    @endif
                                                    <span class="font-mono text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($pattern->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($pattern->end_time)->format('H:i') }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">休日</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-gray-500">アクティブなユーザーがいません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 凡例 --}}
        <div class="flex items-center gap-x-4 gap-y-2 text-xs flex-wrap mb-4">
            <span class="flex items-center gap-1"><i class="fas fa-building text-green-500"></i> 出勤</span>
            <span class="flex items-center gap-1"><i class="fas fa-home text-blue-500"></i> 在宅</span>
            <span class="flex items-center gap-1"><i class="fas fa-bed text-red-500"></i> 全休</span>
            <span class="flex items-center gap-1"><i class="fas fa-bed text-yellow-500 opacity-80"></i> 午前/午後休</span>
        </div>

        {{-- PC用月間グリッドカレンダー (変更なし) --}}
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
                        $isSaturday = $date->isSaturday();
                        $isSunday = $date->isSunday();
                        $isPublicHoliday = $dayData['public_holiday'];
                        $isToday = $date->isToday();
                    @endphp
                    <div class="p-2 border-t border-r border-gray-200 dark:border-gray-700
                            {{ !$dayData['is_current_month'] ? 'bg-gray-50 dark:bg-gray-800/50' : '' }}
                            {{ $isSaturday ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}
                            {{ $isSunday ? 'bg-red-50 dark:bg-red-900/30' : '' }}
                            {{ $isPublicHoliday && $dayData['is_current_month'] ? 'bg-green-50 dark:bg-green-900/30' : '' }}
                        ">
                        <div
                            class="text-sm font-semibold {{ $isToday ? 'text-white bg-blue-500 rounded-full w-6 h-6 flex items-center justify-center' : '' }}">
                            {{ $date->day }}
                        </div>
                        @if ($isPublicHoliday)
                            <div class="text-xs text-green-600 truncate" title="{{ $isPublicHoliday->name }}">
                                {{ $isPublicHoliday->name }}</div>
                        @endif
                        <div class="mt-1 space-y-1 text-xs">
                            @foreach ($dayData['schedules'] as $schedule)
                                @include('admin.schedule.partials.schedule-item', ['schedule' => $schedule])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ▼▼▼【ここから修正】スマホ用のカレンダー/リスト表示 ▼▼▼ --}}
        <div class="md:hidden" x-data="{ viewMode: 'calendar' }">
            {{-- 表示切り替えボタン --}}
            <div class="flex justify-end mb-4">
                <div class="flex items-center bg-gray-200 dark:bg-gray-700 p-0.5 rounded-lg">
                    <button @click="viewMode = 'calendar'"
                        class="px-3 py-1 text-xs font-bold rounded-md transition-colors"
                        :class="viewMode === 'calendar' ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-300 shadow' : 'text-gray-500 dark:text-gray-400'">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        カレンダー
                    </button>
                    <button @click="viewMode = 'list'"
                        class="px-3 py-1 text-xs font-bold rounded-md transition-colors"
                        :class="viewMode === 'list' ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-300 shadow' : 'text-gray-500 dark:text-gray-400'">
                        <i class="fas fa-list mr-1"></i>
                        リスト
                    </button>
                </div>
            </div>

            {{-- スマホ用月間グリッドカレンダー --}}
            <div x-show="viewMode === 'calendar'" style="display: none;" class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                <div class="grid grid-cols-7">
                    @foreach (['日', '月', '火', '水', '木', '金', '土'] as $dayOfWeek)
                        <div class="py-1 text-center text-sm font-semibold text-gray-600 dark:text-gray-300 border-b dark:border-gray-700
                                @if($dayOfWeek === '土') bg-blue-50 dark:bg-blue-900/30 @endif
                                @if($dayOfWeek === '日') bg-red-50 dark:bg-red-900/30 @endif">
                            {{ $dayOfWeek }}
                        </div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7">
                    @foreach ($calendarData as $dateString => $dayData)
                        @php
                            $date = $dayData['date'];
                            $isSaturday = $date->isSaturday();
                            $isSunday = $date->isSunday();
                            $isPublicHoliday = $dayData['public_holiday'];
                            $isToday = $date->isToday();
                        @endphp
                        {{-- 日付セル: overflow-hiddenを削除し、min-hを増やす --}}
                        <div class="relative p-1 border-t border-r border-gray-200 dark:border-gray-700 min-h-[90px]
                                {{ !$dayData['is_current_month'] ? 'bg-gray-50 dark:bg-gray-800/50' : '' }}
                                {{ $isSaturday ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}
                                {{ $isSunday ? 'bg-red-50 dark:bg-red-900/30' : '' }}
                                {{ $isPublicHoliday && $dayData['is_current_month'] ? 'bg-green-50 dark:bg-green-900/30' : '' }}
                            ">
                            <div class="text-sm font-semibold h-5 flex items-center justify-center {{ $isToday ? 'text-white bg-blue-500 rounded-full w-5' : '' }}">
                                {{ $date->day }}
                            </div>
                            @if ($isPublicHoliday)
                                <div class="text-[10px] text-green-600 truncate" title="{{ $isPublicHoliday->name }}">
                                    {{ $isPublicHoliday->name }}</div>
                            @endif
                            <div class="mt-1 space-y-1">
                                @foreach ($dayData['schedules'] as $schedule)
                                    @include('admin.schedule.partials.schedule-item', ['schedule' => $schedule])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- スマホ用スケジュールリスト --}}
            <div x-show="viewMode === 'list'" style="display: none;" class="space-y-4">
                @foreach ($calendarData as $dateString => $dayData)
                    @if ($dayData['is_current_month'] && (!$dayData['schedules']->isEmpty() || $dayData['public_holiday']))
                        @php
                            $date = $dayData['date'];
                            $isSaturday = $date->isSaturday();
                            $isSunday = $date->isSunday();
                            $isPublicHoliday = $dayData['public_holiday'];
                        @endphp
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                            <div class="font-semibold border-b pb-2 mb-3
                                        {{ $isSaturday ? 'text-blue-600 dark:text-blue-400' : '' }}
                                        {{ $isSunday ? 'text-red-500 dark:text-red-400' : '' }}
                                        {{ $isPublicHoliday ? 'text-green-600 dark:text-green-400' : '' }}
                                    ">
                                {{ $date->format('n/j') }} ({{ $date->isoFormat('ddd') }})
                                @if ($isPublicHoliday)
                                    <span class="ml-2 text-xs font-normal">({{ $isPublicHoliday->name }})</span>
                                @endif
                            </div>
                            <div class="space-y-2">
                                @forelse ($dayData['schedules'] as $schedule)
                                    @include('admin.schedule.partials.schedule-item', ['schedule' => $schedule])
                                @empty
                                    <p class="text-xs text-gray-500">スケジュール登録者はいません</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        {{-- ▲▲▲【修正ここまで】▲▲▲ --}}
    </div>
@endsection
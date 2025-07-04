@extends('layouts.app')

@section('title', '作業・勤怠履歴')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業・勤怠履歴</h1>

            <div class="flex-shrink-0">
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    <a href="{{ route('work-records.index', array_merge(request()->query(), ['view' => 'timeline'])) }}"
                        class="px-4 py-2 text-sm font-medium {{ $viewMode === 'timeline' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border border-gray-200 dark:border-gray-600 rounded-l-lg">
                        <i class="fas fa-stream fa-fw mr-1"></i> タイムライン
                    </a>
                    <a href="{{ route('work-records.index', array_merge(request()->query(), ['view' => 'list'])) }}"
                        class="px-4 py-2 text-sm font-medium {{ $viewMode === 'list' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border-t border-b border-r border-gray-200 dark:border-gray-600 rounded-r-md">
                        <i class="fas fa-list fa-fw mr-1"></i> リスト
                    </a>
                </div>
            </div>
        </div>

        <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            {{-- 期間モード切り替えボタン --}}
            <div class="flex justify-center border-b dark:border-gray-700 pb-4">
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    @php
                        // 表示モードは維持し、dateは今日にリセットして期間モードを切り替える
                        $queryParams = ['view' => $viewMode, 'date' => now()->format('Y-m-d')];
                    @endphp
                    <a href="{{ route('work-records.index', array_merge($queryParams, ['period' => 'day'])) }}"
                        class="px-4 py-2 text-sm font-medium {{ $period === 'day' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border border-gray-200 dark:border-gray-600 rounded-l-lg">
                        日
                    </a>
                    <a href="{{ route('work-records.index', array_merge($queryParams, ['period' => 'week'])) }}"
                        class="px-4 py-2 text-sm font-medium {{ $period === 'week' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border-t border-b border-gray-200 dark:border-gray-600">
                        週
                    </a>
                    <a href="{{ route('work-records.index', array_merge($queryParams, ['period' => 'month'])) }}"
                        class="px-4 py-2 text-sm font-medium {{ $period === 'month' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border border-gray-200 dark:border-gray-600 rounded-r-lg">
                        月
                    </a>
                </div>
            </div>

            {{-- 日付/週/月 ナビゲーション --}}
            <form action="{{ route('work-records.index') }}" method="GET"
                class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-4">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                <input type="hidden" name="period" value="{{ $period }}">

                @if($period === 'day')
                    <a href="{{ route('work-records.index', ['view' => $viewMode, 'period' => 'day', 'date' => $targetDate->copy()->subDay()->format('Y-m-d')]) }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        <i class="fas fa-chevron-left"></i> 前日
                    </a>
                    <input type="date" name="date" value="{{ $targetDate->format('Y-m-d') }}" onchange="this.form.submit()"
                        class="border-gray-300 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm font-semibold">
                    <a href="{{ route('work-records.index', ['view' => $viewMode, 'period' => 'day', 'date' => $targetDate->copy()->addDay()->format('Y-m-d')]) }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        翌日 <i class="fas fa-chevron-right"></i>
                    </a>
                @elseif($period === 'week')
                    <a href="{{ route('work-records.index', ['view' => $viewMode, 'period' => 'week', 'date' => $targetDate->copy()->subWeek()->format('Y-m-d')]) }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        <i class="fas fa-chevron-left"></i> 前の週
                    </a>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">
                        {{ $targetDate->copy()->startOfWeek()->format('Y/n/j') }} -
                        {{ $targetDate->copy()->endOfWeek()->format('Y/n/j') }}
                    </span>
                    <a href="{{ route('work-records.index', ['view' => $viewMode, 'period' => 'week', 'date' => $targetDate->copy()->addWeek()->format('Y-m-d')]) }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        次の週 <i class="fas fa-chevron-right"></i>
                    </a>
                @else {{-- month --}}
                    <a href="{{ route('work-records.index', ['view' => $viewMode, 'period' => 'month', 'date' => $targetDate->copy()->subMonthNoOverflow()->format('Y-m-d')]) }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        <i class="fas fa-chevron-left"></i> 前の月
                    </a>
                    <input type="month" name="date" value="{{ $targetDate->format('Y-m') }}" onchange="this.form.submit()"
                        class="border-gray-300 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm font-semibold">
                    <a href="{{ route('work-records.index', ['view' => $viewMode, 'period' => 'month', 'date' => $targetDate->copy()->addMonthNoOverflow()->format('Y-m-d')]) }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        次の月 <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </form>

            {{-- サマリー表示エリア --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center mt-4 pt-4 border-t dark:border-gray-700">
                <div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">総勤務時間 (拘束時間)</h4>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200 mt-1">
                        {{ gmdate('H:i:s', $summary['detention_seconds']) }}
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">総休憩時間 (休憩+中抜け)</h4>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200 mt-1">
                        {{ gmdate('H:i:s', $summary['break_seconds']) }}
                    </p>
                </div>
                <div>
                    {{-- 【名称変更】支払対象時間 -> 実質勤務時間 --}}
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">実質勤務時間</h4>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ gmdate('H:i:s', $summary['payable_seconds']) }}
                    </p>
                </div>
            </div>
        </div>

        @if ($viewMode === 'list')
            @include('work-records.partials.list-view')
        @else
            @include('work-records.partials.timeline-view')
        @endif
    </div>
@endsection
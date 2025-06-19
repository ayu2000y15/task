@extends('layouts.app')

@section('title', '作業・勤怠履歴')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業・勤怠履歴</h1>

            <div class="flex-shrink-0">
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    <a href="{{ route('work-records.index', ['view' => 'timeline', 'date' => request('date', now()->format('Y-m-d'))]) }}"
                        class="px-4 py-2 text-sm font-medium {{ $viewMode === 'timeline' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border border-gray-200 dark:border-gray-600 rounded-l-lg">
                        <i class="fas fa-stream fa-fw mr-1"></i> タイムライン
                    </a>
                    <a href="{{ route('work-records.index', ['view' => 'list', 'period' => request('period', 'today')]) }}"
                        class="px-4 py-2 text-sm font-medium {{ $viewMode === 'list' ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-500' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600' }} border-t border-b border-r border-gray-200 dark:border-gray-600 rounded-r-md">
                        <i class="fas fa-list fa-fw mr-1"></i> リスト
                    </a>
                </div>
            </div>
        </div>

        @if ($viewMode === 'list')
            {{-- ======================================================= --}}
            {{-- リスト表示 (List View) --}}
            {{-- ======================================================= --}}
            @include('work-records.partials.list-view')
        @else
            {{-- ======================================================= --}}
            {{-- タイムライン表示 (Timeline View) --}}
            {{-- ======================================================= --}}
            @include('work-records.partials.timeline-view')
        @endif
    </div>
@endsection

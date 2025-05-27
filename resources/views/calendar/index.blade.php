@extends('layouts.app')

@section('title', 'カレンダー')

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
            type: '', title: '', start: '', end: '', color: '#ffffff',
            url: '', description: '', assignee: '', status: '', project_title: '',
            statusLabels: {{ json_encode($statusOptions) }}
        }" @open-modal.window="if ($event.detail.name === 'eventDetailModal') {
            const event = $event.detail.eventData;
            type = event.extendedProps.type;
            title = event.title;
            start = event.startStr;
            end = event.end ? new Date(new Date(event.endStr).setDate(new Date(event.endStr).getDate() - 1)).toLocaleDateString('ja-JP') : start;
            color = event.backgroundColor;
            url = event.url;
            description = event.extendedProps.description;
            assignee = event.extendedProps.assignee;
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
                    <strong class="font-semibold text-gray-600 dark:text-gray-400 w-20 inline-block">期間:</strong>
                    <span class="text-gray-800 dark:text-gray-200"
                        x-text="`${new Date(start).toLocaleDateString('ja-JP')} 〜 ${end}`"></span>
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
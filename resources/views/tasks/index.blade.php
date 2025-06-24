@extends('layouts.app')

@section('title', '工程一覧')

@section('content')
    <div id="running-work-logs-data" class="hidden">
        {{ isset($activeWorkLogs) ? json_encode($activeWorkLogs) : '[]' }}
    </div>
    @php
        $shouldFiltersBeOpen = array_filter(Arr::except($filters, ['hide_completed'])) && !request()->has('close_filters');
    @endphp
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ $shouldFiltersBeOpen ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">工程一覧</h1>
            <div class="flex items-center flex-wrap gap-2">
                <button
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                    type="button" x-on:click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-2"></i> フィルター
                    <span x-show="filtersOpen" style="display: none;"><i class="fas fa-chevron-up ml-2 fa-xs"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down ml-2 fa-xs"></i></span>
                </button>
                @auth
                    @php
                        $isFilteringBySelf = isset($filters['assignee_id']) && $filters['assignee_id'] == Auth::id();
                        $baseClass = 'inline-flex items-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150';
                        $activeClass = 'bg-blue-600 border-transparent text-white hover:bg-blue-700 focus:ring-blue-500';
                        $inactiveClass = 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-indigo-500';
                    @endphp
                    <a href="{{ route('tasks.index', ['assignee_id' => Auth::id(), 'close_filters' => 1]) }}"
                        class="{{ $baseClass }} {{ $isFilteringBySelf ? $activeClass : $inactiveClass }}">
                        <i class="fas fa-user-check mr-2"></i>担当:自分
                    </a>
                @endauth
                @php
                    $hideCompletedParams = request()->query();
                    $isHidingCompleted = $filters['hide_completed'] ?? false;
                    if ($isHidingCompleted) {
                        unset($hideCompletedParams['hide_completed']);
                        $buttonText = '完了を表示';
                        $buttonIcon = 'fa-eye';
                        $buttonClass = $activeClass;
                    } else {
                        $hideCompletedParams['hide_completed'] = 1;
                        $buttonText = '完了を非表示';
                        $buttonIcon = 'fa-eye-slash';
                        $buttonClass = $inactiveClass;
                    }
                @endphp
                <a href="{{ route('tasks.index', $hideCompletedParams) }}" class="{{ $baseClass }} {{ $buttonClass }}">
                    <i class="fas {{ $buttonIcon }} mr-2"></i>{{ $buttonText }}
                </a>
                @can('create', App\Models\Project::class)
                    <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i
                            class="fas fa-plus mr-1"></i>新規衣装案件</x-primary-button>
                @endcan
            </div>
        </div>
        <div
            class="p-2 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-500">
            <i class="fas fa-info-circle mr-1"></i>
            案件ステータスが「完了」、または「キャンセル」の場合は表示されません。<br>
            　工数の1日は8時間として計算しています。
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6">
            <x-filter-panel :action="route('tasks.index')" :filters="$filters" :all-projects="$allProjects"
                :all-characters="$charactersForFilter" :all-assignees="$assigneesForFilter" :status-options="$statusOptions"
                :show-due-date-filter="true" />
        </div>

        <div class="mb-6" x-data="{ activeTab: 'tasks', listView: 'list' }">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto pb-px" aria-label="Tabs">
                    <button
                        class="tab-button py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm whitespace-nowrap focus:outline-none"
                        :class="{ 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500': activeTab === 'tasks', 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500': activeTab !== 'tasks' }"
                        x-on:click="activeTab = 'tasks'">
                        <i class="fas fa-tasks mr-1"></i> 工程
                        ({{ $tasks->where('is_milestone', false)->where('is_folder', false)->count() }})
                    </button>
                    <button
                        class="tab-button py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm whitespace-nowrap focus:outline-none"
                        :class="{ 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500': activeTab === 'milestones', 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500': activeTab !== 'milestones' }"
                        x-on:click="activeTab = 'milestones'">
                        <i class="fas fa-flag mr-1"></i> 予定 ({{ $tasks->where('is_milestone', true)->count() }})
                    </button>
                </nav>
            </div>
            <div class="mt-3">
                <div x-show="activeTab === 'tasks'" id="tasks-panel" role="tabpanel">
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                        <div
                            class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-2">
                            <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-0">工程一覧</h5>
                        </div>
                        <div id="task-table-container" class="overflow-x-auto overflow-y-auto max-h-[65vh]">
                            @include('tasks.partials.task-table', ['tasksToList' => $tasks->where('is_milestone', false)->where('is_folder', false), 'tableId' => 'tasks-list-table'])
                        </div>
                    </div>
                </div>
                <div x-show="activeTab === 'milestones'" id="milestones-panel" role="tabpanel">
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-0">予定一覧</h5>
                        </div>
                        <div id="milestone-table-container" class="overflow-x-auto">
                            @include('tasks.partials.task-table', ['tasksToList' => $tasks->where('is_milestone', true), 'tableId' => 'milestones-list-table', 'isMilestoneView' => true])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div x-data="{ isModalOpen: false, currentTaskId: null, assigneesForTask: [], selectedAssignees: [] }"
        x-show="isModalOpen"
        x-on:open-assignee-modal.window="isModalOpen = true; currentTaskId = $event.detail.taskId; assigneesForTask = $event.detail.assignees; selectedAssignees = []"
        x-on:keydown.escape.window="isModalOpen = false" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto z-50 flex items-center justify-center"
        style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden sm:max-w-md sm:w-full"
            @click.away="isModalOpen = false">
            <div class="px-4 py-3 sm:px-6 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-200">担当者を選択</h3>
            </div>
            <div class="p-4 sm:p-6 max-h-60 overflow-y-auto">
                <template x-for="assignee in assigneesForTask" :key="assignee.id">
                    <div class="mb-2">
                        <label :for="'assignee-' + assignee.id + '-' + currentTaskId"
                            class="inline-flex items-center w-full p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" :id="'assignee-' + assignee.id + '-' + currentTaskId"
                                :value="assignee.id" x-model="selectedAssignees"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300" x-text="assignee.name"></span>
                        </label>
                    </div>
                </template>
            </div>
            <div class="px-4 py-3 sm:px-6 bg-gray-100 dark:bg-gray-700 text-right">
                <button type="button"
                    class="inline-flex justify-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm"
                    @click="isModalOpen = false">キャンセル</button>
                <button type="button"
                    class="inline-flex justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:text-sm"
                    @click="handleStartTimerWithSelection(currentTaskId, selectedAssignees); isModalOpen = false">開始</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- ▼▼▼【ここから変更】スクリプトを修正 ▼▼▼ --}}
    <script>
        function setupSortableTable(containerSelector) {
            const container = document.querySelector(containerSelector);
            if (!container) return;

            container.addEventListener('click', function (event) {
                const link = event.target.closest('a.sortable-link');
                if (!link) return;

                event.preventDefault();
                const url = link.href;
                container.style.opacity = '0.5';

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.html) {
                            container.innerHTML = data.html;

                            // ★ 更新されたHTMLに対して、JSの機能を再初期化する
                            if (window.initTasksIndex) {
                                window.initTasksIndex();
                            }
                            if (window.initializeWorkTimers) {
                                window.initializeWorkTimers();
                            }
                        }
                        window.history.pushState({}, '', url);
                    })
                    .catch(error => {
                        console.error('Sort request failed:', error);
                        alert('データの並び替えに失敗しました。ページをリロードします。');
                        window.location.reload();
                    })
                    .finally(() => {
                        container.style.opacity = '1';
                    });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            setupSortableTable('#task-table-container');
            setupSortableTable('#milestone-table-container');
        });
    </script>
    {{-- ▲▲▲【変更ここまで】▲▲▲ --}}
@endpush
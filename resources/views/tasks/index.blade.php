@extends('layouts.app')

@section('title', '工程一覧')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ filtersOpen: {{ array_filter($filters) ? 'true' : 'false' }} }">
    {{-- ... (フィルターボタン等は変更なし) ... --}}
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">工程一覧</h1>
        <div class="flex space-x-2">
            <button
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                type="button" x-on:click="filtersOpen = !filtersOpen">
                <i class="fas fa-filter mr-2"></i> フィルター
                <span x-show="filtersOpen" style="display: none;"><i class="fas fa-chevron-up ml-2 fa-xs"></i></span>
                <span x-show="!filtersOpen"><i class="fas fa-chevron-down ml-2 fa-xs"></i></span>
            </button>
            @can('create', App\Models\Project::class)
            <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i class="fas fa-plus mr-1"></i>新規衣装案件</x-primary-button>

            @endcan
        </div>
    </div>

    <div x-show="filtersOpen" x-collapse class="mb-6">
        <x-filter-panel
            :action="route('tasks.index')"
            :filters="$filters"
            :all-projects="$allProjects"
            :all-characters="$charactersForFilter"
            :all-assignees="$assignees"
            :status-options="$statusOptions"
            :show-due-date-filter="true"
        />
    </div>

    <div class="mb-6" x-data="{ activeTab: 'tasks', listView: 'list' }">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto pb-px" aria-label="Tabs">
                <button
                    class="tab-button py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm whitespace-nowrap focus:outline-none disabled:opacity-50 disabled:pointer-events-none"
                    :class="{ 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500': activeTab === 'tasks', 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500': activeTab !== 'tasks' }"
                    x-on:click="activeTab = 'tasks'">
                    <i class="fas fa-tasks mr-1"></i> 工程 ({{ $tasks->where('is_milestone', false)->where('is_folder', false)->count() }})
                </button>
                <button
                    class="tab-button py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm whitespace-nowrap focus:outline-none disabled:opacity-50 disabled:pointer-events-none"
                    :class="{ 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500': activeTab === 'milestones', 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500': activeTab !== 'milestones' }"
                    x-on:click="activeTab = 'milestones'">
                    <i class="fas fa-flag mr-1"></i> 重要納期 ({{ $tasks->where('is_milestone', true)->count() }})
                </button>
                <button
                    class="tab-button py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm whitespace-nowrap focus:outline-none disabled:opacity-50 disabled:pointer-events-none"
                    :class="{ 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500': activeTab === 'folders', 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500': activeTab !== 'folders' }"
                    x-on:click="activeTab = 'folders'">
                    <i class="fas fa-folder mr-1"></i> フォルダ ({{ $tasks->where('is_folder', true)->count() }})
                </button>
            </nav>
        </div>

        <div class="mt-3">
            <div x-show="activeTab === 'tasks'" id="tasks-panel" role="tabpanel" aria-labelledby="tasks-tab-button">
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-2">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-0">工程一覧</h5>
                        <div class="flex space-x-1 bg-gray-200 dark:bg-gray-700 p-0.5 rounded-md">
                            <button class="px-3 py-1 text-sm font-medium rounded-md"
                                    :class="{ 'bg-white dark:bg-gray-900 shadow-sm text-gray-700 dark:text-gray-200': listView === 'list', 'text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-900 hover:shadow-sm': listView !== 'list' }"
                                    x-on:click="listView = 'list'">
                                <i class="fas fa-list"></i> リスト
                            </button>
                            <button class="px-3 py-1 text-sm font-medium rounded-md"
                                    :class="{ 'bg-white dark:bg-gray-900 shadow-sm text-gray-700 dark:text-gray-200': listView === 'board', 'text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-900 hover:shadow-sm': listView !== 'board' }"
                                    x-on:click="listView = 'board'">
                                <i class="fas fa-columns"></i> ボード
                            </button>
                        </div>
                    </div>
                    <div x-show="listView === 'list'" class="overflow-x-auto overflow-y-auto max-h-[65vh]">
                        @include('tasks.partials.task-table', ['tasksToList' => $tasks->where('is_milestone', false)->where('is_folder', false), 'tableId' => 'tasks-list-table'])
                    </div>
                     <div x-show="listView === 'board'" class="p-4 text-gray-500 dark:text-gray-400">
                        <p>ボードビューは現在開発中です。(このエリアにカンバンボード風の表示を実装予定)</p>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'milestones'" id="milestones-panel" role="tabpanel" aria-labelledby="milestones-tab-button">
                 <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-0">重要納期一覧</h5>
                    </div>
                    <div class="overflow-x-auto"> @include('tasks.partials.task-table', ['tasksToList' => $tasks->where('is_milestone', true), 'tableId' => 'milestones-list-table', 'isMilestoneView' => true])
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'folders'" id="folders-panel" role="tabpanel" aria-labelledby="folders-tab-button">
                 <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-0">フォルダ一覧</h5>
                    </div>
                    <div class="overflow-x-auto"> @include('tasks.partials.task-table', ['tasksToList' => $tasks->where('is_folder', true)->sortBy('name'), 'tableId' => 'folders-list-table', 'isFolderView' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
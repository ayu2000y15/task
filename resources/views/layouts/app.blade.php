<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>衣装案件管理 - @yield('title', config('app.name', 'Laravel'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
    @stack('styles')
    <style>
        /* サイドバーのプロジェクト名とアイコンのレイアウト調整用 */
        .project-link-content {
            display: flex;
            justify-content: space-between; /* タイトルグループとアイコン群を両端揃え */
            align-items: flex-start; /* 上揃え */
            width: 100%;
        }
        .project-title-group {
            display: flex;
            align-items: center; /* アイコン(頭文字)とタイトルを縦中央揃え */
            min-width: 0; /* トランケートが効くように */
            flex-grow: 1; /* タイトル部分が伸びるように */
            margin-right: 8px; /* アイコン群との間にスペース */
        }
        .project-title-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .project-status-icons-sidebar {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        .header-main-nav a {
            flex-shrink: 0;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-100 dark:text-gray-100 dark:bg-gray-900">
    <div x-data="{ sidebarOpen: false }">
        <div x-show="sidebarOpen" class="fixed inset-0 z-20 bg-black opacity-50 md:hidden" @click="sidebarOpen = false" style="display: none;"></div>

        <aside
        class="fixed inset-y-0 left-0 z-30 w-64 h-screen overflow-y-auto transition duration-300 ease-in-out transform bg-white shadow-lg dark:bg-gray-800 md:translate-x-0"
        :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
        <div class="flex items-center justify-center h-16 bg-gray-50 dark:bg-gray-700">
            <a href="{{ route('home.index') }}" class="text-xl font-semibold text-gray-700 dark:text-white">衣装案件管理</a>
        </div>

        {{-- サイドバーナビゲーション --}}
        <nav class="px-2 py-4" x-data="{ openFavorites: true, openNormalProjects: true, openUpcomingTasks: true }">
            @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="flex items-center px-3 py-2 mb-3 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md dark:text-gray-200 hover:bg-gray-800 hover:text-white dark:hover:bg-blue-600">
                <i class="fas fa-plus w-5 h-5 mr-2"></i> 新規衣装案件
            </a>
            @endcan

            {{-- 期限間近の工程セクション --}}
            <div>
                <div @click="openUpcomingTasks = !openUpcomingTasks" class="flex items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer">
                    <span>期限間近[2日以内] ({{ $upcomingTasksForSidebar->count() }})</span>
                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openUpcomingTasks, 'fa-chevron-right': !openUpcomingTasks}"></i>
                </div>
                <ul x-show="openUpcomingTasks" x-transition class="mt-1 space-y-1">
                    @forelse($upcomingTasksForSidebar as $task)
                        <li class="px-3 py-3 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                            data-task-id="{{ $task->id }}"
                            data-project-id="{{ $task->project->id }}"
                            data-progress="{{ $task->progress ?? 0 }}"
                            data-status="{{ $task->status }}">
                            <div class="flex items-start justify-between w-full">
                                {{-- Left part: In-progress checkbox and status icon --}}
                                <div class="flex items-start flex-shrink-0">
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center mr-2 mt-[1px]"> {{-- Wrapper for label + checkbox InProgress --}}
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-0.5" style="font-size: 0.5rem;">進行中</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-in-progress form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                   data-action="set-in-progress"
                                                   title="進行中にする"
                                                   @if($task->status == 'in_progress') checked @endif>
                                        </div>
                                    @else
                                        {{-- Placeholder to maintain alignment if checkbox is not present --}}
                                        {{-- This width should roughly match the "進行中" label and checkbox column --}}
                                        <div class="w-12 mr-2 mt-[1px]"></div> {{-- Adjust w-12 if necessary --}}
                                    @endif
                                    <span class="task-status-icon-wrapper mr-2 mt-[1px] flex-shrink-0">
                                        @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                        @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                        @else
                                            @switch($task->status)
                                                @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                                @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                                @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i> @break
                                                @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                                @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                                            @endswitch
                                        @endif
                                    </span>
                                </div>

                                {{-- Middle part: Project name, Task name, assignee, due date --}}
                                <div class="flex-grow min-w-0 mx-1">
                                    {{-- 1. 案件名 --}}
                                    <p class="text-xs font-semibold truncate dark:text-gray-300" style="color: {{ $task->project->color ?? '#6c757d' }};" title="案件: {{ $task->project->title }}">
                                        {{ $task->project->title }}
                                    </p>
                                    {{-- 2. タスク名 --}}
                                    <span class="font-medium text-gray-800 dark:text-gray-100 whitespace-normal break-words leading-tight" title="タスク: {{ $task->name }}">
                                        {{ $task->name }}
                                    </span>
                                    {{-- 3. 担当者 --}}
                                    @if($task->assignee)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="担当: {{ $task->assignee }}">
                                        担当: {{ $task->assignee }}
                                    </p>
                                    @endif
                                    {{-- 4. 期限 --}}
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">
                                        期限: {{ optional($task->end_date)->format('n/j H:i') }}<br>
                                        @if($task->end_date)
                                            @if($task->end_date->isPast() && $task->status !== 'completed')
                                                <span class="text-red-500">({{ $task->end_date->diffForHumans() }})</span>
                                            @elseif($task->status !== 'completed')
                                                ({{ $task->end_date->diffForHumans() }})
                                            @endif
                                        @endif
                                    </p>
                                </div>

                                {{-- Right part: Completed checkbox --}}
                                <div class="flex-shrink-0 ml-2">
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center mt-[1px]"> {{-- Wrapper for label + checkbox Completed --}}
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-0.5" style="font-size: 0.5rem;">完了</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-completed form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                                   data-action="set-completed"
                                                   title="完了にする"
                                                   @if($task->status == 'completed') checked @endif>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">期限間近の工程はありません</li>
                    @endforelse
                </ul>
            </div>

            {{-- 衣装案件セクション --}}
            <div class="mb-3">
                <div @click="openNormalProjects = !openNormalProjects" class="flex items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer">
                    <span>衣装案件 ({{ $normalProjects->count() }})</span>
                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openNormalProjects, 'fa-chevron-right': !openNormalProjects}"></i>
                </div>
                <div x-show="openNormalProjects" x-transition class="mt-1 space-y-1">
                    @forelse($normalProjects as $project)
                    @php
                        // PHP変数の定義は上記お気に入りと同様なので省略。$projectをループ変数として使用
                        $_projectStatusOptionsSb = ['' => '未設定'] + (\App\Models\Project::PROJECT_STATUS_OPTIONS ?? []);
                        $_projectStatusIconsSb = [
                            'not_started' => 'fa-minus-circle text-gray-400 dark:text-gray-500', 'in_progress' => 'fa-play-circle text-blue-400 dark:text-blue-500',
                            'completed'   => 'fa-check-circle text-green-400 dark:text-green-500', 'on_hold'     => 'fa-pause-circle text-yellow-400 dark:text-yellow-500',
                            'cancelled'   => 'fa-times-circle text-red-400 dark:text-red-500', '' => 'fa-question-circle text-gray-400 dark:text-gray-500',
                        ];
                        $_currentProjectStatusSb = $project->status ?? '';
                        $_projectStatusTooltipSb = $_projectStatusOptionsSb[$_currentProjectStatusSb] ?? $_currentProjectStatusSb;
                        $_projectStatusIconClassSb = $_projectStatusIconsSb[$_currentProjectStatusSb] ?? $_projectStatusIconsSb[''];
                        $_deliveryFlagValueSb = $project->delivery_flag ?? '0';
                        $_deliveryIconSb = $_deliveryFlagValueSb == '1' ? 'fa-check-circle text-green-400 dark:text-green-500' : 'fa-truck text-yellow-400 dark:text-yellow-500';
                        $_deliveryTooltipSb = $_deliveryFlagValueSb == '1' ? '納品済み' : '未納品';
                        $_paymentFlagOptionsSb = ['' => '未設定'] + (\App\Models\Project::PAYMENT_FLAG_OPTIONS ?? []);
                        $_paymentFlagIconsSb = [
                            'Pending'        => 'fa-clock text-yellow-400 dark:text-yellow-500', 'Processing'     => 'fa-hourglass-half text-blue-400 dark:text-blue-500',
                            'Completed'      => 'fa-check-circle text-green-400 dark:text-green-500', 'Partially Paid' => 'fa-adjust text-orange-400 dark:text-orange-500',
                            'Overdue'        => 'fa-exclamation-triangle text-red-400 dark:text-red-500', 'Cancelled'      => 'fa-ban text-gray-400 dark:text-gray-500',
                            'Refunded'       => 'fa-undo text-purple-400 dark:text-purple-500', 'On Hold'        => 'fa-pause-circle text-indigo-400 dark:text-indigo-500',
                            ''               => 'fa-question-circle text-gray-400 dark:text-gray-500',
                        ];
                        $_currentPaymentFlagSb = $project->payment_flag ?? '';
                        $_paymentFlagTooltipSb = $_paymentFlagOptionsSb[$_currentPaymentFlagSb] ?? $_currentPaymentFlagSb;
                        $_paymentFlagIconClassSb = $_paymentFlagIconsSb[$_currentPaymentFlagSb] ?? $_paymentFlagIconsSb[''];
                    @endphp
                    <a href="{{ route('projects.show', $project) }}"
                       class="block px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ (request()->is('projects/'.$project->id.'*') || (isset($currentProject) && $currentProject->id == $project->id)) ? 'bg-blue-500 text-white dark:bg-blue-600' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                        <div class="project-link-content">
                            <div class="project-title-group">
                                <span class="flex items-center justify-center w-5 h-5 mr-2 text-xs font-bold text-white rounded flex-shrink-0" style="background-color: {{ $project->color ?? '#6c757d' }};">
                                    {{ mb_substr($project->title, 0, 1) }}
                                </span>
                                <span class="project-title-text" title="{{ $project->title }}">{{ $project->title }}</span>
                            </div>
                            <div class="project-status-icons-sidebar text-xs">
                                 @if($project->status)<span title="案件ステータス: {{ $_projectStatusTooltipSb }}"><i class="fas {{ $_projectStatusIconClassSb }}"></i></span>@endif
                                <span title="納品状況: {{ $_deliveryTooltipSb }}"><i class="fas {{ $_deliveryIconSb }}"></i></span>
                                @if($project->payment_flag)<span title="支払い状況: {{ $_paymentFlagTooltipSb }}"><i class="fas {{ $_paymentFlagIconClassSb }}"></i></span>@endif
                            </div>
                        </div>
                    </a>
                    @empty
                        <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">衣装案件はありません</p>
                    @endforelse
                </div>
            </div>

        </nav>
    </aside>

        <div class="flex flex-col flex-1 md:ml-64">
            <header class="flex items-center justify-between h-16 px-2 sm:px-4 bg-white border-b dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50">
                 <div class="flex items-center"> {{-- 左側グループ: ハンバーガーメニュー(モバイルのみ) --}}
                    <div class="md:hidden">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-300 focus:outline-none p-2">
                            <i class="fas fa-bars w-6 h-6"></i>
                        </button>
                    </div>
                 </div>

                {{-- 中央グループ: メインナビゲーションリンク --}}
                <nav class="header-main-nav flex items-center overflow-x-auto space-x-1 md:space-x-2 md:flex-grow md:justify-start">
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('home.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('home.index') }}" title="ホーム">
                        <i class="fas fa-home"></i><span class="hidden md:inline ml-2">ホーム</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Task::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('tasks.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('tasks.index') }}" title="工程">
                        <i class="fas fa-tasks"></i><span class="hidden md:inline ml-2">工程</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('gantt.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('gantt.index') }}" title="ガントチャート">
                        <i class="fas fa-chart-gantt"></i><span class="hidden md:inline ml-2">ガント</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('calendar.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('calendar.index') }}" title="カレンダー">
                        <i class="fas fa-calendar-alt"></i><span class="hidden md:inline ml-2">カレンダー</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ (request()->routeIs('projects.*') || (isset($currentProject) && request()->is('projects/*'))) && !request()->routeIs('projects.*.tasks.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('projects.index') }}" title="衣装案件">
                        <i class="fas fa-tshirt"></i><span class="hidden md:inline ml-2">衣装案件</span>
                    </a>
                    @endcan
                </nav>

                {{-- 右側グループ: 管理・ユーザーメニュー --}}
                <div class="flex items-center space-x-1 sm:space-x-2 pl-1 sm:pl-2">
                    @can('viewAny', App\Models\ProcessTemplate::class) {{-- このcanは管理メニュー全体を表示するかどうかの大元の権限チェックとして残す --}}
                    <div x-data="{ adminMenuOpenOnHeader: false }" class="relative">
                        <button @click="adminMenuOpenOnHeader = !adminMenuOpenOnHeader" class="flex items-center px-2 sm:px-3 py-2 text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                            <i class="fas fa-cog"></i>
                            <span class="hidden sm:inline ml-1">管理</span>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>

                        @php
                            $currentUser = Auth::user();
                            $canViewGroup1 = $currentUser->can('viewAny', App\Models\StockOrder::class) ||
                                            $currentUser->can('viewAny', App\Models\ExternalProjectSubmission::class) ||
                                            $currentUser->can('viewAny', App\Models\Feedback::class);

                            $canViewGroup2 = $currentUser->can('viewAny', App\Models\InventoryItem::class);

                            $canViewGroup3 = $currentUser->can('viewAny', App\Models\User::class) || // ユーザー管理
                                            $currentUser->can('viewAny', App\Models\Role::class) || // 権限設定
                                            $currentUser->can('viewAny', App\Models\ProcessTemplate::class) || // 工程テンプレート (この@canが外側の条件なので実質true)
                                            $currentUser->can('viewAny', App\Models\FormFieldDefinition::class); // 案件依頼項目定義

                            $canViewGroup4 = $currentUser->can('viewAny', Spatie\Activitylog\Models\Activity::class); // 操作ログ閲覧

                            $canViewGroup5 = $currentUser->can('viewAny', App\Models\User::class); // 新規ユーザー登録リンク (ユーザー管理権限に依存している場合)

                            // 表示される最初のグループかどうかのフラグ
                            $isFirstVisibleGroup = true;
                        @endphp

                        <div x-show="adminMenuOpenOnHeader"
                            @click.away="adminMenuOpenOnHeader = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            x-data="{ openGroup: null }"
                            class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
                            style="display: none;">

                            @if ($canViewGroup1)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group1' ? null : 'group1')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span>申請・通知</span>
                                    <i class="fas fa-fw" :class="{'fa-chevron-down': openGroup === 'group1', 'fa-chevron-right': openGroup !== 'group1'}"></i>
                                </button>
                                <div x-show="openGroup === 'group1'" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 transform -translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-1"
                                    class="bg-gray-50 dark:bg-gray-800">
                                    @can('viewAny', App\Models\ExternalProjectSubmission::class)
                                    <a href="{{ route('admin.external-submissions.index') }}" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        案件依頼一覧
                                        @if(isset($newExternalSubmissionsCountGlobal) && $newExternalSubmissionsCountGlobal > 0)
                                            <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">{{ $newExternalSubmissionsCountGlobal }}</span>
                                        @endif
                                    </a>
                                    @endcan
                                    @can('viewAny', App\Models\StockOrder::class)
                                    <a href="{{ route('admin.stock-orders.index') }}" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        在庫発注申請
                                        @if(isset($pendingStockOrdersCountGlobal) && $pendingStockOrdersCountGlobal > 0)
                                            <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-orange-500 rounded-full">{{ $pendingStockOrdersCountGlobal }}</span>
                                        @endif
                                    </a>
                                    @endcan
                                    @can('viewAny', App\Models\Feedback::class)
                                        <a href="{{ route('admin.feedbacks.index') }}" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            フィードバック管理 @if(isset($unreadFeedbackCountGlobal) && $unreadFeedbackCountGlobal > 0)
                                            <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-green-600 rounded-full">{{ $unreadFeedbackCountGlobal }}</span>@endif
                                        </a>
                                    @endcan
                                </div>
                            </div>
                            @php if($canViewGroup1) $isFirstVisibleGroup = false; @endphp
                            @endif

                            @if ($canViewGroup2)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group2' ? null : 'group2')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span>データ・在庫</span>
                                    <i class="fas fa-fw" :class="{'fa-chevron-down': openGroup === 'group2', 'fa-chevron-right': openGroup !== 'group2'}"></i>
                                </button>
                                <div x-show="openGroup === 'group2'" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 transform -translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-1"
                                    class="bg-gray-50 dark:bg-gray-800">
                                    @can('viewAny', App\Models\InventoryItem::class)
                                        <a href="{{ route('admin.inventory.index') }}" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            在庫管理
                                            @if(isset($hasInventoryAlertsGlobal) && $hasInventoryAlertsGlobal)
                                                <i class="fas fa-exclamation-triangle text-lg text-yellow-500 dark:text-yellow-400" title="在庫僅少または在庫切れの品目あり"></i>
                                            @endif
                                        </a>
                                    @endcan
                                </div>
                            </div>
                            @php if($canViewGroup2) $isFirstVisibleGroup = false; @endphp
                            @endif

                            @if ($canViewGroup3)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group3' ? null : 'group3')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span>設定</span>
                                    <i class="fas fa-fw" :class="{'fa-chevron-down': openGroup === 'group3', 'fa-chevron-right': openGroup !== 'group3'}"></i>
                                </button>
                                <div x-show="openGroup === 'group3'" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 transform -translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-1"
                                    class="bg-gray-50 dark:bg-gray-800">
                                    @can('viewAny', App\Models\User::class)
                                        <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">ユーザー管理</a>
                                    @endcan
                                    @can('viewAny', App\Models\Role::class)
                                        <a href="{{ route('admin.roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">権限設定</a>
                                    @endcan
                                    @can('viewAny', App\Models\ProcessTemplate::class)
                                        <a href="{{ route('admin.process-templates.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">工程テンプレート</a>
                                    @endcan
                                    @can('manageMeasurements', $project) {{-- 適切なモデル名とPolicyを確認 --}}
                                        <a href="{{ route('admin.measurement-templates.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">採寸テンプレート</a>
                                    @endcan
                                    @can('viewAny', App\Models\FormFieldDefinition::class)
                                        <a href="{{ route('admin.form-definitions.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">案件依頼項目定義</a>
                                    @endcan
                                </div>
                            </div>
                            @php if($canViewGroup3) $isFirstVisibleGroup = false; @endphp
                            @endif

                            @if ($canViewGroup4)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group4' ? null : 'group4')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span>ログ</span>
                                    <i class="fas fa-fw" :class="{'fa-chevron-down': openGroup === 'group4', 'fa-chevron-right': openGroup !== 'group4'}"></i>
                                </button>
                                <div x-show="openGroup === 'group4'" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 transform -translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-1"
                                    class="bg-gray-50 dark:bg-gray-800">
                                    @can('viewAny', Spatie\Activitylog\Models\Activity::class)
                                        <a href="{{ route('admin.logs.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">操作ログ閲覧</a>
                                    @endcan
                                </div>
                            </div>
                            @php if($canViewGroup4) $isFirstVisibleGroup = false; @endphp
                            @endif

                            @if ($canViewGroup5)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group5' ? null : 'group5')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span>リンクコピー</span>
                                    <i class="fas fa-fw" :class="{'fa-chevron-down': openGroup === 'group5', 'fa-chevron-right': openGroup !== 'group5'}"></i>
                                </button>
                                <div x-show="openGroup === 'group5'" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 transform -translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-1"
                                    class="bg-gray-50 dark:bg-gray-800">
                                    @can('viewAny', App\Models\User::class) {{-- 新規ユーザー登録リンクの表示権限 --}}
                                    <a href="javascript:void(0)" onclick="copyLink(this)" data-copy-value="{{ url('/register') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        新規ユーザー登録リンク
                                    </a>
                                    @endcan
                                    @can('viewAny', App\Models\ExternalProjectSubmission::class) {{-- 新規ユーザー登録リンクの表示権限 --}}
                                    <a href="javascript:void(0)" onclick="copyLink(this)" data-copy-value="{{ url('/costume-request') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        案件依頼リンク
                                    </a>
                                    @endcan
                                </div>
                            </div>
                            @php if($canViewGroup5) $isFirstVisibleGroup = false; @endphp
                            @endif

                        </div>
                    </div>
                    @endcan

                    @auth
                    {{-- ユーザーメニュー (変更なし) --}}
                    <div x-data="{ userMenuOpen: false }" class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none p-2">
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <i class="fas fa-user sm:hidden" title="{{ Auth::user()->name }}"></i>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false"
                            x-transition
                            class="absolute right-0 mt-2 w-48 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                            style="display: none;">
                            @can('create', App\Models\Feedback::class)
                            <a href="{{ route('user_feedbacks.create') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">フィードバックを送信</a>
                            @endcan

                            @can('viewAny', App\Models\Task::class)
                                @php
                                    // 現在のルートが 'tasks.index' で、かつ 'assignee' クエリがログインユーザー名と一致するか判定
                                    $isMyTasksActive = request()->routeIs('tasks.index') && request()->query('assignee') === Auth::user()->name;
                                @endphp
                                <a href="{{ route('tasks.index', ['assignee' => Auth::user()->name, 'close_filters' => 1]) }}"
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ $isMyTasksActive ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                    担当工程一覧
                                </a>
                            @endcan

                            @can('tools.viewAnyPage') {{-- ツール一覧ページへのアクセス権限 --}}
                            <a href="{{ route('tools.index') }}"
                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('tools.index') || request()->routeIs('tools.sales.*') ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                {{-- <i class="fas fa-tools fa-fw mr-2 w-4 text-center"></i> --}}
                                ツール一覧
                            </a>
                            @endcan

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    ログアウト
                                </a>
                            </form>
                        </div>
                    </div>
                    @endauth
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6 overflow-y-auto h-full">
                @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600" role="alert">
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600" role="alert">
                    {{ session('error') }}
                </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Global Modals and Tooltips --}}
    <div id="taskDescriptionTooltip"
         class="fixed z-[100] hidden rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white shadow-lg whitespace-pre-wrap dark:bg-gray-700 max-w-xs"
         role="tooltip">
    </div>
    <div id="imagePreviewModalGlobal" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-80 z-[1050] items-center justify-center p-4" style="display: none;">
        <img id="previewImageFullGlobal" src="" alt="Full Image" class="max-w-[90vw] max-h-[90vh] object-contain block">
        <button id="closePreviewModalBtnGlobal" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300 cursor-pointer">&times;</button>
    </div>
    <div id="upload-loading-overlay"
        class="fixed inset-0 z-[10050] flex items-center justify-center flex-col text-white bg-black bg-opacity-75"
        style="display:none;">
        <i class="fas fa-hourglass-half fa-3x mb-3"></i>
        <p>アップロード中です...</p>
    </div>

    @yield('scripts')
    @stack('scripts')

    <script>
        function copyLink(buttonElement) {
            const textToCopy = buttonElement.dataset.copyValue;

            if (textToCopy === undefined || textToCopy === null || textToCopy.trim() === "") {
                console.error("data-copy-value attribute is missing or empty on the clicked element.");
                alert("コピーする内容が指定されていません。");
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    alert("コピーしました : \n" + textToCopy);
                }).catch(err => {
                    console.error('navigator.clipboard.writeText failed: ', err);
                    // フォールバック処理 (textareaを使用)
                    fallbackCopyUsingExecCommand(textToCopy);
                });
            } else {
                // navigator.clipboard APIがサポートされていない場合のフォールバック (textareaを使用)
                console.warn('navigator.clipboard.writeText is not available. Using fallback.');
                fallbackCopyUsingExecCommand(textToCopy);
            }
        }
        // execCommand fallback for copyLink
        function fallbackCopyUsingExecCommand(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert("コピーしました (Fallback): \n" + text);
                } else {
                    alert("コピーに失敗しました (Fallback)。");
                }
            } catch (err) {
                console.error('Fallback copy failed: ', err);
                alert("コピーに失敗しました (Fallback)。");
            }
            document.body.removeChild(textArea);
        }
    </script>

</body>
</html>
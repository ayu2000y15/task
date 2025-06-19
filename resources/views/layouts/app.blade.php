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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

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

        /* ▼▼▼【ここから追加】メンション候補のスタイル ▼▼▼ */
        #mention-suggestions-container .mention-suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
        }
        #mention-suggestions-container .mention-suggestion-item:last-child {
            border-bottom: none;
        }
        /* 通常のアイテムのスタイル */
        .dark #mention-suggestions-container .mention-suggestion-item {
            border-bottom-color: #4b5563;
        }
        /* ホバー時のスタイル */
        #mention-suggestions-container .mention-suggestion-item:hover {
            background-color: #f3f4f6;
        }
        .dark #mention-suggestions-container .mention-suggestion-item:hover {
            background-color: #374151;
        }
        /* 選択中のアイテムのスタイル */
        #mention-suggestions-container .mention-suggestion-item.is-selected {
            background-color: #dbeafe;
        }
        .dark #mention-suggestions-container .mention-suggestion-item.is-selected {
            background-color: #1e40af;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-100 dark:text-gray-100 dark:bg-gray-900"
    data-attendance-status="{{ $currentAttendanceStatus ?? 'clocked_out' }}">
    <div x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') === 'true' }" x-init="$watch('sidebarOpen', value => localStorage.setItem('sidebarOpen', value))">
        <div x-show="sidebarOpen" class="fixed inset-0 z-20 bg-black opacity-50 md:hidden" @click="sidebarOpen = false" style="display: none;"></div>

        <aside
        class="fixed inset-y-0 left-0 z-30 w-72 max-w-66 h-screen overflow-y-auto transition duration-300 ease-in-out transform bg-white shadow-lg dark:bg-gray-800 md:translate-x-0"
        :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
        <div class="flex items-center justify-center h-16 bg-gray-50 dark:bg-gray-700">
            <a href="{{ route('home.index') }}" class="text-xl font-semibold text-gray-700 dark:text-white">衣装案件管理</a>
        </div>

        {{-- サイドバーナビゲーション --}}
        <nav class="px-2 py-4"
            x-data="{
                openProductivity: localStorage.getItem('openProductivity') === null ? true : localStorage.getItem('openProductivity') === 'true',
                openFavorites: localStorage.getItem('openFavorites') === null ? true : localStorage.getItem('openFavorites') === 'true',
                openNormalProjects: localStorage.getItem('openNormalProjects') === null ? true : localStorage.getItem('openNormalProjects') === 'true',
                openUpcomingTasks: localStorage.getItem('openUpcomingTasks') === null ? true : localStorage.getItem('openUpcomingTasks') === 'true',
                openArchivedProjects: localStorage.getItem('openArchivedProjects') === null ? false : localStorage.getItem('openArchivedProjects') === 'true'
            }"
            x-init="
                $watch('openProductivity', value => localStorage.setItem('openProductivity', value));
                $watch('openFavorites', value => localStorage.setItem('openFavorites', value));
                $watch('openNormalProjects', value => localStorage.setItem('openNormalProjects', value));
                $watch('openUpcomingTasks', value => localStorage.setItem('openUpcomingTasks', value));
                $watch('openArchivedProjects', value => localStorage.setItem('openArchivedProjects', value));
            ">
            @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="flex items-center px-3 py-2 mb-3 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md dark:text-gray-200 hover:bg-gray-800 hover:text-white dark:hover:bg-blue-600">
                <i class="fas fa-plus w-5 h-5 mr-2"></i> 新規衣装案件
            </a>
            @endcan

            {{-- 生産性 --}}
            @can('viewOwnProductivity', App\Models\User::class)
            <div>
                {{-- クリックで開閉するヘッダー --}}
                <div @click="openProductivity = !openProductivity" class="flex items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer">
                    <div class="flex items-center">
                        <span>生産性</span>
                        <i class="far fa-question-circle text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help ml-2"
                           title="【生産性バーの見方】
                            ■ バー全体：その日（または月）の総拘束時間（最初の出勤から最後の退勤まで）を表します。
                            ■ 青色（作業）：作業ログに記録された、実際のタスクに費やされた時間です。
                            ■ 黄色（休憩等）：勤怠ログに記録された、休憩や中抜けの時間です。
                            ■ 灰色（空き）：上記のいずれにも分類されない時間です。会議や準備、移動、または記録されていない作業などが含まれます。
                            目標は、この灰色の「空き時間」をできるだけ減らし、全ての業務を青色の「作業時間」として記録することです。"></i>
                    </div>
                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openProductivity, 'fa-chevron-right': !openProductivity}"></i>
                </div>

                {{-- 開閉するコンテンツエリア --}}
                <div x-show="openProductivity" x-transition class="mt-2">
                    <div x-data="{ openOthers: false }">
                        {{-- ログイン中のユーザー（常に表示） --}}
                        @if($currentUserProductivitySummary)
                            <div class="px-2 py-3 bg-gray-100 dark:bg-gray-700/50 rounded-lg">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $currentUserProductivitySummary->user->name }}</h4>
                                {{-- 昨日のバー --}}
                                <div class="mt-3">
                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                        <span>昨日</span>
                                        <span>空き: <strong class="text-red-500">{{ gmdate('H:i', $currentUserProductivitySummary->yesterday->unaccountedSeconds) }}</strong> / {{ gmdate('H:i', $currentUserProductivitySummary->yesterday->totalAttendanceSeconds) }}</span>
                                    </div>
                                    @if($currentUserProductivitySummary->yesterday->totalAttendanceSeconds > 0)
                                        <div class="mt-1 flex w-full h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden"
                                             title="作業:{{ gmdate('H:i', $currentUserProductivitySummary->yesterday->totalWorkLogSeconds) }} | 休憩等:{{ gmdate('H:i', $currentUserProductivitySummary->yesterday->totalBreakSeconds) }} | 空き:{{ gmdate('H:i', $currentUserProductivitySummary->yesterday->unaccountedSeconds) }}">
                                            <div class="bg-blue-500" style="width: {{ $currentUserProductivitySummary->yesterday->workLogPercentage }}%"></div>
                                            <div class="bg-yellow-400" style="width: {{ $currentUserProductivitySummary->yesterday->breakPercentage }}%"></div>
                                        </div>
                                        <div class="flex justify-end space-x-2 text-xs text-gray-500 mt-1">
                                            <span><i class="fas fa-square text-blue-500"></i> 作業</span>
                                            <span><i class="fas fa-square text-yellow-400"></i> 休憩等</span>
                                            <span><i class="fas fa-square text-gray-300 dark:text-gray-500"></i> 空き</span>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">昨日の勤怠記録がありません。</p>
                                    @endif
                                </div>

                                {{-- 今月のバー --}}
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                        <span>今月</span>
                                        <span>総空き: <strong class="text-red-500">{{ floor($currentUserProductivitySummary->month->unaccountedSeconds / 3600) }}h</strong> / {{ floor($currentUserProductivitySummary->month->totalAttendanceSeconds / 3600) }}h</span>
                                    </div>
                                    @if($currentUserProductivitySummary->month->totalAttendanceSeconds > 0)
                                        <div class="mt-1 flex w-full h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden"
                                             title="総作業:{{ floor($currentUserProductivitySummary->month->totalWorkLogSeconds / 3600) }}h | 総休憩等:{{ floor($currentUserProductivitySummary->month->totalBreakSeconds / 3600) }}h | 総空き:{{ floor($currentUserProductivitySummary->month->unaccountedSeconds / 3600) }}h">
                                            <div class="bg-blue-500" style="width: {{ $currentUserProductivitySummary->month->workLogPercentage }}%"></div>
                                            <div class="bg-yellow-400" style="width: {{ $currentUserProductivitySummary->month->breakPercentage }}%"></div>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">今月の勤怠記録がありません。</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endcan


            {{-- 期限間近の工程セクション --}}
            <div>
                <div @click="openUpcomingTasks = !openUpcomingTasks" class="flex items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer">
                    <span>期限切れ・間近 [2日以内] ({{ $upcomingTasksForSidebar->count() }})</span>
                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openUpcomingTasks, 'fa-chevron-right': !openUpcomingTasks}"></i>
                </div>
                <ul x-show="openUpcomingTasks" x-transition class="mt-1 space-y-1">
                    @forelse($upcomingTasksForSidebar as $task)
                        @php
                            // 期限のハイライト表示用に状態を判定
                            if ($task->end_date) {
                                $appTimezone = config('app.timezone');
                                $now = \Carbon\Carbon::now($appTimezone);
                                $endDate = $task->end_date->copy()->setTimezone($appTimezone);
                                $isCompleted = in_array($task->status, ['completed', 'cancelled']);
                                $isPast = $endDate->isPast();
                                $isDueSoon = !$isPast && $endDate->lte($now->copy()->addHours(24));
                            } else {
                                $isCompleted = in_array($task->status, ['completed', 'cancelled']);
                                $isPast = false;
                                $isDueSoon = false;
                            }
                        @endphp
                        <li class="px-3 py-3 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                            data-task-id="{{ $task->id }}"
                            data-project-id="{{ $task->project->id }}"
                            data-progress="{{ $task->progress ?? 0 }}"
                            data-status="{{ $task->status }}">
                            <div class="flex items-start justify-between w-full">
                                <div class="flex items-start flex-shrink-0">
                                    {{-- @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center mr-2 mt-[1px]">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-0.5" style="font-size: 0.5rem;">進行中</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-in-progress form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                   data-action="set-in-progress"
                                                   title="進行中にする"
                                                   @if($task->status == 'in_progress') checked @endif>
                                        </div>
                                    @else
                                        <div class="w-12 mr-2 mt-[1px]"></div>
                                    @endif --}}
                                    <span class="task-status-icon-wrapper mr-2 mt-[1px] flex-shrink-0">
                                        @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                        @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                        @else
                                            @switch($task->status)
                                                @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                                @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                                @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="一時停止中"></i> @break
                                                @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                                @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                                            @endswitch
                                        @endif
                                    </span>
                                </div>

                                {{-- ▼▼▼【変更】表示をホーム画面と統一 ▼▼▼ --}}
                                <div class="flex-grow min-w-0 mx-1">
                                    <p class="text-xs font-semibold truncate dark:text-gray-300" style="color: {{ $task->project->color ?? '#6c757d' }};" title="案件: {{ $task->project->title }}">
                                        {{ $task->project->title }}
                                    </p>
                                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="font-medium text-gray-800 dark:text-gray-100 whitespace-normal break-words leading-tight hover:text-blue-600 dark:hover:text-blue-400" title="タスク: {{ $task->name }}">
                                        {{ $task->name }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                                        <i class="fas fa-dragon fa-fw mr-1 text-gray-400"></i> {{ $task->character->name ?? 'キャラクター未設定' }}
                                    </p>
                                    @if($task->assignees && $task->assignees->isNotEmpty())
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="担当: {{ $task->assignees->pluck('name')->join(', ') }}">
                                        <i class="fas fa-user fa-fw mr-1 text-gray-400"></i>{{ $task->assignees->pluck('name')->join(', ') }}
                                    </p>
                                    @endif
                                    @if($task->end_date)
                                        <p style="border-radius: 20px; padding: 0 3px; font-size:0.7rem;"
                                                @if($isPast && !$isCompleted)
                                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 bg-red-200 font-semibold" title="期限切れ"
                                                @elseif($isDueSoon && !$isCompleted)
                                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 bg-yellow-200 font-semibold" title="期限1日前"
                                                @endif
                                            >
                                            <i class="far fa-clock fa-fw mr-1 text-gray-400"></i>
                                            <span>
                                                {{ $task->end_date->format('n/j H:i') }}
                                            </span>
                                            <span>
                                                ({{ $task->end_date->diffForHumans() }})
                                            </span>
                                        </p>
                                    @endif
                                </div>
                                {{-- ▲▲▲【変更】ここまで ▲▲▲ --}}
                                <div class="mt-2">
                                    @if(!$task->is_folder && !$task->is_milestone)
                                        @if($task->assignees->isNotEmpty())
                                            @php
                                                $isAssigned = $task->assignees->contains('id', Auth::id());
                                                $isSharedAccount = Auth::check() && Auth::user()->status === \App\Models\User::STATUS_SHARED;
                                            @endphp
                                            @if($isAssigned || $isSharedAccount)
                                                <div class="timer-controls"
                                                    data-task-id="{{ $task->id }}"
                                                    data-task-status="{{ $task->status }}"
                                                    data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}"
                                                    data-assignees='{{ json_encode($task->assignees->map->only(['id', 'name'])->values()) }}'
                                                    data-view-mode="compact" {{-- ▼▼▼【この属性を追加】▼▼▼ --}}
                                                    >
                                                    {{-- JavaScriptがこの中身を生成します --}}
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                                {{-- <div class="flex-shrink-0 ml-2">
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center mt-[1px]">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-0.5" style="font-size: 0.5rem;">完了</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-completed form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                                   data-action="set-completed"
                                                   title="完了にする"
                                                   @if($task->status == 'completed') checked @endif>
                                        </div>
                                    @endif
                                </div> --}}
                            </div>
                        </li>
                    @empty
                        <li class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">期限間近の工程はありません</li>
                    @endforelse
                </ul>
            </div>

            {{-- 衣装案件セクション --}}
            <div>
                <div @click="openNormalProjects = !openNormalProjects" class="flex items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer">
                    <span>衣装案件（進行中） ({{ $activeProjects->count() }})</span>
                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openNormalProjects, 'fa-chevron-right': !openNormalProjects}"></i>
                </div>
                <div x-show="openNormalProjects" x-transition class="mt-1 space-y-1">
                    @forelse($activeProjects as $project)
                    @php
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

            {{-- 衣装案件（完了・キャンセル）セクション --}}
            <div>
                <div @click="openArchivedProjects = !openArchivedProjects" class="flex items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer">
                    <span>衣装案件（完了・キャンセル） ({{ $archivedProjects->count() }})</span>
                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openArchivedProjects, 'fa-chevron-right': !openArchivedProjects}"></i>
                </div>
                <div x-show="openArchivedProjects" x-transition class="mt-1 space-y-1">
                    @forelse($archivedProjects as $project)
                    @php
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
                        <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">完了・キャンセルの案件はありません</p>
                    @endforelse
                </div>
            </div>

        </nav>
    </aside>

        <div class="flex flex-col flex-1 md:ml-72">
            <header class="flex items-center justify-between h-16 px-2 sm:px-4 bg-white border-b dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50">
                 <div class="flex items-center">
                    <div class="md:hidden">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-300 focus:outline-none p-2">
                            <i class="fas fa-bars w-6 h-6"></i>
                        </button>
                    </div>
                 </div>

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
                    {{-- @can('create', App\Models\Request::class) Policyは後で作成 --}}
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('requests.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('requests.index') }}" title="作業依頼一覧">
                        <span class="relative">
                            <i class="fas fa-clipboard-check"></i>
                            @if(isset($pendingRequestsCountGlobal) && $pendingRequestsCountGlobal > 0)
                                <span class="absolute -top-1.5 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs text-white">{{ $pendingRequestsCountGlobal }}</span>
                            @endif
                        </span>
                        <span class="hidden md:inline ml-2">依頼</span>
                    </a>
                    {{-- @endcan --}}
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

                    @can('viewAny', App\Models\BoardPost::class)
                    <a class="relative inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('community.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('community.posts.index') }}" title="社内掲示板">
                        <span class="relative">
                            <i class="fas fa-comments"></i>
                            @if(isset($unreadMentionsCountGlobal) && $unreadMentionsCountGlobal > 0)
                                <span class="absolute -top-1.5 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs text-white">{{ $unreadMentionsCountGlobal }}</span>
                            @endif
                        </span>
                        <span class="hidden md:inline ml-2">掲示板</span>
                    </a>
                    @endcan
                </nav>

                {{-- 勤怠登録 --}}
                @auth
                    <div x-data="attendanceTimer({ initialStatus: '{{ $currentAttendanceStatus }}' })" class="relative">
                        <div class="flex items-center space-x-1">
                            <span class="hidden sm:inline-flex items-center px-2 py-1 text-xs font-medium rounded-md"
                                :class="{
                                    'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': status === 'clocked_out',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-blue-100': status === 'working',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-600 dark:text-yellow-100': status === 'on_break',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-600 dark:text-purple-100': status === 'on_away',
                                }" x-text="statusText">
                            </span>

                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center px-2 py-2 text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                                    <i class="fas fa-clock"></i>
                                    <i class="fas fa-chevron-down fa-xs ml-1"></i>
                                </button>

                                <div x-show="open" @click.away="open = false"
                                    x-transition
                                    class="absolute right-0 mt-2 w-40 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                                    style="display: none;">

                                    <template x-if="status === 'clocked_out'">
                                        <a href="#" @click.prevent="clock('clock_in')" class="block px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-600">出勤</a>
                                    </template>

                                    <template x-if="status === 'working'">
                                        <div>
                                            <a href="#" @click.prevent="clock('break_start')"
                                                class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600"
                                                :class="{
                                                    'text-gray-700 dark:text-gray-200': !hasActiveWorkLog,
                                                    'text-gray-400 dark:text-gray-500 cursor-not-allowed': hasActiveWorkLog
                                                }"
                                                :title="hasActiveWorkLog ? '実行中の作業があるため操作できません' : ''">
                                                休憩開始
                                            </a>

                                            <a href="#" @click.prevent="clock('away_start')"
                                                class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600"
                                                :class="{
                                                    'text-gray-700 dark:text-gray-200': !hasActiveWorkLog,
                                                    'text-gray-400 dark:text-gray-500 cursor-not-allowed': hasActiveWorkLog
                                                }"
                                                :title="hasActiveWorkLog ? '実行中の作業があるため操作できません' : ''">
                                                中抜け開始
                                            </a>
                                            <a href="#" @click.prevent="clock('clock_out')"
                                                class="block px-4 py-2 text-sm  hover:bg-gray-100 dark:hover:bg-gray-600"
                                                :class="{
                                                    'text-red-600 dark:text-red-400': !hasActiveWorkLog,
                                                    'text-gray-400 dark:text-gray-500 cursor-not-allowed': hasActiveWorkLog
                                                }"
                                                :title="hasActiveWorkLog ? '実行中の作業があるため退勤できません' : ''"
                                                >退勤</a>
                                        </div>
                                    </template>

                                    <template x-if="status === 'on_break'">
                                        <a href="#" @click.prevent="clock('break_end')" class="block px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-600">休憩終了</a>
                                    </template>

                                    <template x-if="status === 'on_away'">
                                        <a href="#" @click.prevent="clock('away_end')" class="block px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-600">戻り</a>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                @endauth

                <div class="flex items-center space-x-1 sm:space-x-2 pl-1 sm:pl-2">
                    @can('viewAny', App\Models\ProcessTemplate::class)
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

                            $canViewGroupWork = $currentUser->can('viewAny', App\Models\WorkLog::class) ||
                                                $currentUser->can('viewAllSchedules', App\Models\User::class) ||
                                                $currentUser->can('viewAllTransportationExpenses', App\Models\User::class);

                            $canViewGroup3 = $currentUser->can('viewAny', App\Models\User::class) ||
                                            $currentUser->can('viewAny', App\Models\Role::class) ||
                                            $currentUser->can('viewAny', App\Models\ProcessTemplate::class) ||
                                            $currentUser->can('viewAny', App\Models\FormFieldDefinition::class);

                            $canViewGroup4 = $currentUser->can('viewAny', Spatie\Activitylog\Models\Activity::class);

                            $canViewGroup5 = $currentUser->can('viewAny', App\Models\User::class);

                            $isFirstVisibleGroup = true;
                        @endphp

                        <div x-show="adminMenuOpenOnHeader"
                            @click.away="adminMenuOpenOnHeader = false"
                            x-transition
                            x-data="{ openGroup: null }"
                            class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
                            style="display: none;">

                            @if ($canViewGroup1)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group1' ? null : 'group1')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span><i class="fas fa-bell fa-fw mr-2"></i>申請・通知</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openGroup === 'group1', 'fa-chevron-right': openGroup !== 'group1'}"></i>
                                </button>
                                <div x-show="openGroup === 'group1'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
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
                                    <span><i class="fas fa-database fa-fw mr-2"></i>データ・在庫</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openGroup === 'group2', 'fa-chevron-right': openGroup !== 'group2'}"></i>
                                </button>
                                <div x-show="openGroup === 'group2'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
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

                            @if($canViewGroupWork)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'groupWork' ? null : 'groupWork')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span><i class="fas fa-user-clock fa-fw mr-2"></i>勤怠管理</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openGroup === 'groupWork', 'fa-chevron-right': openGroup !== 'groupWork'}"></i>
                                </button>
                                <div x-show="openGroup === 'groupWork'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                                    @can('viewAllSchedules', App\Models\User::class)
                                        <a href="{{ route('admin.schedule.calendar') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">全員のスケジュール</a>
                                    @endcan
                                    @can('viewAllTransportationExpenses', App\Models\User::class)
                                        <a href="{{ route('admin.transportation-expenses.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">交通費一覧</a>
                                    @endcan
                                    @can('viewAny', App\Models\WorkLog::class)
                                        <a href="{{ route('admin.work-records.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">作業実績一覧</a>
                                    @endcan
                                </div>
                            </div>
                            @php if($canViewGroupWork) $isFirstVisibleGroup = false; @endphp
                            @endif

                            @if ($canViewGroup3)
                            <div class="group-item @if(!$isFirstVisibleGroup) border-t border-gray-200 dark:border-gray-600 @endif">
                                <button @click="openGroup = (openGroup === 'group3' ? null : 'group3')"
                                        class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span><i class="fas fa-cogs fa-fw mr-2"></i>設定</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openGroup === 'group3', 'fa-chevron-right': openGroup !== 'group3'}"></i>
                                </button>
                                <div x-show="openGroup === 'group3'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                                    @can('viewAny', App\Models\User::class)
                                        <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">ユーザー管理</a>
                                    @endcan
                                    @can('viewAny', App\Models\Role::class)
                                        <a href="{{ route('admin.roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">権限設定</a>
                                    @endcan
                                    @can('viewAny', App\Models\ProcessTemplate::class)
                                        <a href="{{ route('admin.process-templates.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">工程テンプレート</a>
                                    @endcan
                                    @can('manageMeasurements', $project)
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
                                    <span><i class="fas fa-history fa-fw mr-2"></i>ログ</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openGroup === 'group4', 'fa-chevron-right': openGroup !== 'group4'}"></i>
                                </button>
                                <div x-show="openGroup === 'group4'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
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
                                    <span><i class="fas fa-link fa-fw mr-2"></i>リンクコピー</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openGroup === 'group5', 'fa-chevron-right': openGroup !== 'group5'}"></i>
                                </button>
                                <div x-show="openGroup === 'group5'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                                    @can('viewAny', App\Models\User::class)
                                    <a href="javascript:void(0)" onclick="copyLink(this)" data-copy-value="{{ url('/register') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        新規ユーザー登録リンク
                                    </a>
                                    @endcan
                                    @can('viewAny', App\Models\ExternalProjectSubmission::class)
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
                    <div x-data="{ userMenuOpen: false, openUserGroup: null }" class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none p-2">
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <i class="fas fa-user sm:hidden" title="{{ Auth::user()->name }}"></i>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false"
                            x-transition
                            class="absolute right-0 mt-2 w-56 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
                            style="display: none;">

                            {{-- 勤務・勤怠グループ --}}
                            <div class="group-item">
                                <button @click="openUserGroup = (openUserGroup === 'work' ? null : 'work')" class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span><i class="fas fa-briefcase fa-fw mr-2"></i>勤務・勤怠</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openUserGroup === 'work', 'fa-chevron-right': openUserGroup !== 'work'}"></i>
                                </button>
                                <div x-show="openUserGroup === 'work'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                                    <a href="{{ route('schedule.monthly') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('shifts.*') ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                        シフト登録
                                    </a>
                                    <a href="{{ route('transportation-expenses.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('transportation-expenses.*') ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                        交通費登録
                                    </a>
                                    @can('viewOwn', App\Models\WorkLog::class)
                                    <a href="{{ route('work-records.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('work-records.index') ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                        作業実績
                                    </a>
                                    @endcan

                                </div>
                            </div>

                            {{-- 依頼・ツールグループ --}}
                            <div class="group-item border-t border-gray-200 dark:border-gray-600">
                                <button @click="openUserGroup = (openUserGroup === 'tools' ? null : 'tools')" class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span><i class="fas fa-pencil-ruler fa-fw mr-2"></i>依頼・ツール</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openUserGroup === 'tools', 'fa-chevron-right': openUserGroup !== 'tools'}"></i>
                                </button>
                                <div x-show="openUserGroup === 'tools'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                                    @can('viewAny', App\Models\Task::class)
                                        <a href="{{ route('tasks.index', ['assignee_id' => Auth::id(), 'close_filters' => 1]) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('tasks.index') && request()->query('assignee_id') == Auth::id() ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                            担当工程一覧
                                        </a>
                                    @endcan
                                    <a href="{{ route('requests.create') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        作業依頼をする
                                    </a>
                                    @can('tools.viewAnyPage')
                                    <a href="{{ route('tools.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('tools.*') ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                                        ツール一覧
                                    </a>
                                    @endcan
                                    <a href="{{ url('/contact-register') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        企業登録
                                    </a>
                                </div>
                            </div>

                            {{-- コミュニケーショングループ --}}
                            <div class="group-item border-t border-gray-200 dark:border-gray-600">
                                <button @click="openUserGroup = (openUserGroup === 'comms' ? null : 'comms')" class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                    <span><i class="fas fa-comments fa-fw mr-2"></i>コミュニケーション</span>
                                    <i class="fas fa-fw text-xs" :class="{'fa-chevron-down': openUserGroup === 'comms', 'fa-chevron-right': openUserGroup !== 'comms'}"></i>
                                </button>
                                <div x-show="openUserGroup === 'comms'" x-transition class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                                    @can('viewAny', App\Models\BoardPost::class)
                                        <a href="{{ route('community.posts.index') }}" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <span>社内掲示板</span>
                                            @if(isset($unreadMentionsCountGlobal) && $unreadMentionsCountGlobal > 0)
                                                <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">{{ $unreadMentionsCountGlobal }}</span>
                                            @endif
                                        </a>
                                    @endcan
                                    @can('create', App\Models\Feedback::class)
                                    <a href="{{ route('user_feedbacks.create') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">フィードバックを送信</a>
                                    @endcan
                                </div>
                            </div>

                            {{-- ログアウト --}}
                            <div class="border-t border-gray-200 dark:border-gray-600">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="block px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <i class="fas fa-sign-out-alt fa-fw mr-2"></i>ログアウト
                                    </a>
                                </form>
                            </div>
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

        {{-- ログインユーザー情報をJSに渡すための要素を追加 --}}
    <div id="user-data-container"
        data-user='{{ Auth::check() ? json_encode(Auth::user()->only(['id', 'status'])) : 'null' }}' class="hidden">
    </div>


    @yield('scripts')
    @stack('scripts')

    @php
        $activeWorkLogsForScript = Auth::check() ? Auth::user()->activeWorkLogs()->with('task')->get() : collect();
    @endphp
    <script id="running-work-logs-data" type="application/json">
        {!! $activeWorkLogsForScript->toJson() !!}
    </script>

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
                    fallbackCopyUsingExecCommand(textToCopy);
                });
            } else {
                console.warn('navigator.clipboard.writeText is not available. Using fallback.');
                fallbackCopyUsingExecCommand(textToCopy);
            }
        }
        function fallbackCopyUsingExecCommand(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
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
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>

<div id="mention-suggestions-container" class="fixed z-[10000] border bg-white dark:bg-gray-700 shadow-lg rounded-md" style="display: none;">
    {{-- 候補リストがここに動的に挿入されます --}}
</div>

</body>
</html>

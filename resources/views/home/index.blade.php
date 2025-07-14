@extends('layouts.app')

@section('title', 'ホーム')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">ホーム</h1>
            <div class="flex-shrink-0">
                @can('create', App\Models\Project::class)
                    <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i
                            class="fas fa-plus mr-1"></i>新規案件</x-primary-button>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- ▼▼▼ 左カラム（メインコンテンツ） ▼▼▼ --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- (左カラムは変更なし) --}}
                @if($runningWorkLogs->isNotEmpty())
                    <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                        <div @click="open = !open"
                            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <i class="fas fa-tasks mr-2 text-blue-500"></i>
                                現在進行中の工程
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold text-white bg-blue-500 rounded-full">{{ $runningWorkLogs->count() }}</span>
                            </h5>
                            <button aria-label="進行中の工程を展開/折りたたむ">
                                <i class="fas fa-fw transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </div>
                        <div x-show="open" x-transition class="divide-y divide-gray-200 dark:divide-gray-700" style="display: none;">
                            @foreach ($runningWorkLogs as $workLog)
                                @php $task = $workLog->task; @endphp
                                @if ($task)
                                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 border-l-4" style="border-left-color: {{ $task->project->color ?? '#6c757d' }};">
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                            <div class="flex-grow min-w-0">
                                                <p class="text-xs font-semibold truncate" style="color: {{ $task->project->color ?? '#6c757d' }};" title="案件: {{ $task->project->title }}">
                                                    <a href="{{ route('projects.show', $task->project) }}" class="hover:underline">
                                                        {{ $task->project->title }}
                                                    </a>
                                                </p>
                                                <p class="text-lg font-medium text-gray-800 dark:text-gray-100 whitespace-normal break-words">
                                                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                                        {{ $task->name }}
                                                    </a>
                                                </p>
                                                @if($task->assignees->isNotEmpty())
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1" title="担当: {{ $task->assignees->pluck('name')->join(', ') }}">
                                                    <i class="fas fa-users fa-fw mr-1 text-gray-400"></i>担当: {{ $task->assignees->pluck('name')->join(', ') }}
                                                </p>
                                                @endif
                                                @if(isset($task->workingUsers) && $task->workingUsers->isNotEmpty())
                                                <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate mt-1" title="作業中: {{ $task->workingUsers->pluck('name')->join(', ') }}">
                                                    <i class="fas fa-play-circle fa-fw mr-1 animate-pulse"></i>作業中: {{ $task->workingUsers->pluck('name')->join(', ') }}
                                                </p>
                                                @endif
                                                @if($task->end_date)
                                                    @php
                                                        $now = \Carbon\Carbon::now();
                                                        $isPast = $task->end_date->isPast();
                                                    @endphp
                                                    <p class="text-xs mt-1 {{ $isPast ? 'text-red-500 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                                        <i class="far fa-clock fa-fw mr-1"></i>
                                                        期限: {{ $task->end_date->format('n/j H:i') }} ({{ $task->end_date->diffForHumans() }})
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex-shrink-0">
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
                                                                data-assignees='{{ json_encode($task->assignees->map->only(['id', 'name'])->values()) }}'>
                                                            </div>
                                                        @else
                                                            <div class="timer-display-only"
                                                                data-task-id="{{ $task->id }}"
                                                                data-task-status="{{ $task->status }}"
                                                                data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}">
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @can('viewAllProductivity', App\Models\User::class)
                <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg mt-6">
                    <div @click="open = !open"
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-purple-500"></i>
                            各ユーザーの生産性
                            <div x-data="{
                                tooltipOpen: false,
                                tooltipStyles: { top: '0px', left: '0px' }
                             }"
                             @click.away="tooltipOpen = false"
                             @click.stop
                             class="relative ml-2">

                            <button @click="
                                        tooltipOpen = !tooltipOpen;
                                        if (tooltipOpen) {
                                            $nextTick(() => {
                                                const trigger = $el;
                                                const tooltip = $refs.tooltip;
                                                const rect = trigger.getBoundingClientRect();
                                                let top = rect.top - tooltip.offsetHeight - 8;
                                                let left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2);
                                                if (left < 0) left = 4;
                                                if ((left + tooltip.offsetWidth) > window.innerWidth) left = window.innerWidth - tooltip.offsetWidth - 4;
                                                tooltipStyles.top = `${top}px`;
                                                tooltipStyles.left = `${left}px`;
                                            });
                                        }
                                    "
                                    type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" aria-label="ヘルプ">
                                <i class="far fa-question-circle cursor-help"></i>
                            </button>

                            <template x-teleport="body">
                                <div x-ref="tooltip"
                                     x-show="tooltipOpen"
                                     :style="tooltipStyles"
                                     x-transition
                                     class="fixed z-[9999] w-72 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-xl"
                                     style="display: none;">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 normal-case mb-2">
                                        生産性バーの見方
                                    </p>
                                    <ul class="text-xs font-normal text-gray-600 dark:text-gray-300 normal-case space-y-2 list-none">
                                        <li class="flex items-start"><i class="fas fa-square text-gray-400 mt-1 mr-2"></i><span><strong>バー全体:</strong> その日の総拘束時間（最初の出勤から最後の退勤まで）を表します。</span></li>
                                        <li class="flex items-start"><i class="fas fa-square text-blue-500 mt-1 mr-2"></i><span><strong>作業:</strong> タスクに費やされた実際の作業時間です。</span></li>
                                        <li class="flex items-start"><i class="fas fa-square text-yellow-400 mt-1 mr-2"></i><span><strong>休憩等:</strong> 休憩や中抜けの時間です。</span></li>
                                        <li class="flex items-start"><i class="fas fa-square text-gray-300 dark:text-gray-500 mt-1 mr-2"></i><span><strong>その他:</strong> 上記以外の時間です（会議、準備、記録外作業など）。</span></li>
                                    </ul>
                                </div>
                            </template>
                        </div>
                        </h5>
                        <button aria-label="生産性を展開/折りたたむ">
                            <i class="fas fa-fw transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>

                    <div x-show="open" x-transition class="p-4 space-y-4" style="display: none;">
                        <div class="text-center pt-2">
                            <div class="flex justify-center space-x-4 text-xs text-gray-500 mt-1">
                                <span><i class="fas fa-square text-blue-500"></i> 作業</span>
                                <span><i class="fas fa-square text-yellow-400"></i> 休憩等</span>
                                <span><i class="fas fa-square text-gray-300 dark:text-gray-500"></i> その他</span>
                            </div>
                        </div>
                        @foreach($productivitySummaries as $summary)
                            <div class="px-3 py-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">{{ $summary->user->name }}</h4>
                                <div class="mt-3">
                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                        <span>昨日</span>
                                        <span>その他: <strong class="text-red-500">{{ gmdate('H:i', $summary->yesterday->unaccountedSeconds) }}</strong></span>
                                    </div>
                                    @if($summary->yesterday->totalAttendanceSeconds > 0)
                                        <div class="mt-1 flex w-full h-2.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden" title="作業:{{ gmdate('H:i', $summary->yesterday->totalWorkLogSeconds) }} | 休憩等:{{ gmdate('H:i', $summary->yesterday->totalBreakSeconds) }}">
                                            <div class="bg-blue-500" style="width: {{ $summary->yesterday->workLogPercentage }}%"></div>
                                            <div class="bg-yellow-400" style="width: {{ $summary->yesterday->breakPercentage }}%"></div>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">記録なし</p>
                                    @endif
                                </div>
                                <div class="mt-3">
                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                        <span>今月</span>
                                        <span>総その他: <strong class="text-red-500">{{ floor($summary->month->unaccountedSeconds / 3600) }}h</strong></span>
                                    </div>
                                    @if($summary->month->totalAttendanceSeconds > 0)
                                        <div class="mt-1 flex w-full h-2.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden" title="総作業:{{ floor($summary->month->totalWorkLogSeconds / 3600) }}h | 総休憩等:{{ floor($summary->month->totalBreakSeconds / 3600) }}h">
                                            <div class="bg-blue-500" style="width: {{ $summary->month->workLogPercentage }}%"></div>
                                            <div class="bg-yellow-400" style="width: {{ $summary->month->breakPercentage }}%"></div>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">記録なし</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                @endcan

                {{-- ▼▼▼【ここから修正】今日のやることリスト ▼▼▼ --}}
                <div x-data="{ open: true }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div @click="open = !open"
                            class="flex-grow flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 cursor-pointer">
                            <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                {{ $targetDate->isToday() ? '今日の' : $targetDate->isoFormat('M月D日(ddd)') . ' ' }}やることリスト
                            </h5>

                            <form @click.stop action="{{ route('home.index') }}" method="GET"
                                class="flex items-center gap-2">
                                <x-secondary-button as="a" href="{{ route('home.index') }}">今日</x-secondary-button>

                                <input type="date" name="date" id="date-picker" value="{{ $targetDate->format('Y-m-d') }}"
                                    class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <x-primary-button type="submit">表示</x-primary-button>
                            </form>
                        </div>
                        <button @click="open = !open" class="ml-4 flex-shrink-0"
                            aria-label="やることリストを展開/折りたたむ">
                            <i class="fas fa-fw transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>

                    <div x-show="open" x-transition class="divide-y divide-gray-200 dark:divide-gray-700">
                        @if(empty($workItemsByAssignee))
                            <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">本日の作業はありません</p>
                        @else
                            @foreach($workItemsByAssignee as $assigneeData)
                                <div x-data="{ userOpen: {{ $assigneeData['assignee']->id === Auth::id() ? 'true' : 'false' }} }"
                                    class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                    <div @click="userOpen = !userOpen"
                                        class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition {{ $assigneeData['assignee']->id === Auth::id() ? 'bg-blue-50 dark:bg-blue-900/50' : '' }}">
                                        @php
                                            $holidayForUser = $todaysHolidays->firstWhere('user_id', $assigneeData['assignee']->id);
                                            $holidayBadgeText = null;
                                            $holidayBadgeTextStyle = "";

                                            if ($holidayForUser) {
                                                $type = $holidayForUser->type;
                                                if ($type === 'full_day_off') {
                                                    $holidayBadgeText = '休暇中 (全休)';
                                                } elseif ($type === 'am_off' && now()->hour < 12) {
                                                    $holidayBadgeText = '休暇中 (午前)';
                                                } elseif ($type === 'pm_off' && now()->hour >= 12) {
                                                    $holidayBadgeText = '休暇中 (午後)';
                                                } else {
                                                    $holidayBadgeTextStyle = "display: none;";
                                                }
                                            }
                                        @endphp
                                        <div class="flex justify-between items-center">
                                            <h3 class="font-semibold flex items-center gap-x-2 {{ $assigneeData['assignee']->id === Auth::id() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-800 dark:text-gray-200' }}">
                                                <i class="fas fa-user mr-1 {{ $assigneeData['assignee']->id === Auth::id() ? 'text-blue-500' : 'text-gray-400' }}"></i>
                                                <span>{{ $assigneeData['assignee']->name }}</span>
                                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold text-white bg-gray-500 rounded-full">{{ $assigneeData['items']->count() }}</span>
                                                @if($todaysHolidays->contains('user_id', $assigneeData['assignee']->id))
                                                    <span
                                                        class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 rounded-full"
                                                        style="{{ $holidayBadgeTextStyle }}">{{ $holidayBadgeText }}</span>
                                                @endif
                                            </h3>
                                            <button aria-label="{{ $assigneeData['assignee']->name }}のタスクを展開/折りたたむ">
                                                <i class="fas fa-fw transition-transform" :class="userOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="userOpen" x-transition
                                        class="px-4 pb-4 {{ $assigneeData['assignee']->id === Auth::id() ? 'bg-blue-50 dark:bg-blue-900/50' : '' }}">
                                        <ul class="ml-6 divide-y space-y-2 divide-gray-200 dark:divide-gray-700">
                                            @foreach($assigneeData['items'] as $item)
                                                @if($item instanceof \App\Models\Task)
                                                @include('home.partials.home-task-item', [
                                                    'task' => $item,
                                                    'isCurrentUserSection' => $assigneeData['assignee']->id === Auth::id()
                                                ])
                                                @elseif($item instanceof \App\Models\RequestItem)
                                                    @include('home.partials.home-request-item', ['item' => $item])
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                {{-- ▲▲▲【修正ここまで】▲▲▲ --}}
            </div>

            {{-- ▼▼▼ 右カラム（サイド情報） ▼▼▼ --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                            <i class="fas fa-satellite-dish text-green-500 mr-2"></i>
                            オンラインのメンバー
                        </h5>
                    </div>
                    @if($onlineUsers->isEmpty())
                        <p class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">現在出勤中のメンバーはいません</p>
                    @else
                        @php
                            $statusLabels = [
                                'working'     => '作業進行中',
                                'on_break'    => '休憩中',
                                'on_away'     => '中抜け中',
                                'not_working' => '作業未着手',
                            ];
                            $statusClasses = [
                                'working'     => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                                'on_break'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                'on_away'     => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                'not_working' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                            ];
                        @endphp
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($onlineUsers as $log)
                                @php
                                    $displayStatusKey = $log->current_status;
                                    if ($log->current_status === 'working' && !in_array($log->user->id, $workingUserIds)) {
                                        $displayStatusKey = 'not_working';
                                    }
                                @endphp
                                <li class="px-6 py-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        {{-- 勤務場所アイコン --}}
                                        <span class="w-5 text-center">
                                            @if(optional($log->user)->todays_location === 'remote')
                                                {{-- 在宅の場合 --}}
                                                <i class="fas fa-home text-blue-500" title="在宅勤務"></i>
                                            @elseif(optional($log->user)->todays_location === 'office')
                                                {{-- 出勤の場合 --}}
                                                <i class="fas fa-building text-green-500" title="出勤"></i>
                                            @else
                                                <i class="fas fa-question-circle text-gray-400" title="勤務場所未設定"></i>
                                            @endif
                                        </span>
                                        {{-- ユーザー名 --}}
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $log->user->name }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$displayStatusKey] ?? '' }}">
                                        {{ $statusLabels[$displayStatusKey] ?? '不明' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">本日の休日取得者</h5>
                    </div>
                    @if($todaysHolidays->isEmpty())
                        <p class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">本日の休日取得者はいません</p>
                    @else
                        @php
                            $typeLabels = ['full_day_off' => '全休', 'am_off' => '午前休', 'pm_off' => '午後休'];
                            $typeClasses = [
                                'full_day_off' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'am_off' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'pm_off' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                            ];
                        @endphp
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($todaysHolidays as $holiday)
                                <li class="px-6 py-3 flex items-center justify-between">
                                    <div>
                                        <span
                                            class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $holiday->user->name }}</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $holiday->name }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeClasses[$holiday->type] ?? $typeClasses['full_day_off'] }}">
                                        {{ $typeLabels[$holiday->type] ?? '全休' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                            <i class="far fa-calendar-alt text-blue-500 mr-2"></i>
                            今後の予定 (1週間以内)
                        </h5>
                    </div>
                    @if(empty($upcomingSchedulesByAssignee))
                        <p class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">1週間以内の予定はありません</p>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                            {{-- ▼▼▼【ここから修正】自分の予定の背景色を変更 ▼▼▼ --}}
                            @foreach($upcomingSchedulesByAssignee as $data)
                                <li class="px-6 py-4 {{ $data['assignee']->id === Auth::id() ? 'bg-blue-50 dark:bg-blue-900/50' : '' }}">
                                    <h6 class="text-sm font-semibold mb-2 {{ $data['assignee']->id === Auth::id() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-800 dark:text-gray-200' }}">
                                        <i class="fas fa-user fa-fw mr-1 {{ $data['assignee']->id === Auth::id() ? 'text-blue-500' : 'text-gray-400' }}"></i>
                                        {{ $data['assignee']->name }}
                                    </h6>
                                    <ul class="ml-4 space-y-3">
                                        @foreach($data['schedules'] as $schedule)
                                            <li class="text-xs space-y-1 pt-3 border-t border-gray-300 dark:border-gray-700 first:border-t-0 first:pt-0">
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $schedule->title }}
                                                </p>
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    <i class="far fa-clock fa-fw mr-1"></i>
                                                    {{ \Carbon\Carbon::parse($schedule->start_at)->isoFormat('M/D(ddd) H:mm') }}
                                                    @if($schedule->end_at)
                                                        ～ {{ \Carbon\Carbon::parse($schedule->end_at)->isoFormat('H:mm') }}
                                                    @endif
                                                </p>
                                                @if($schedule->project)
                                                    <p class="text-gray-600 dark:text-gray-400">
                                                        <i class="fas fa-folder-open fa-fw mr-1 text-gray-400"></i>
                                                        {{ $schedule->project->title }}
                                                    </p>
                                                @endif
                                                @if($schedule->category)
                                                    <p class="text-gray-600 dark:text-gray-400">
                                                        <i class="fas fa-tag fa-fw mr-1 text-gray-400"></i>
                                                        {{ $schedule->category->name }}
                                                    </p>
                                                @endif
                                                @if($schedule->notes)
                                                    <div class="mt-2 p-2 text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                                        {!! nl2br(e($schedule->notes)) !!}
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                            {{-- ▲▲▲【修正ここまで】▲▲▲ --}}
                        </ul>
                    @endif
                </div>


                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">期限切れ・間近の工程 (2日以内)</h5>
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @if($upcomingTasks->isEmpty())
                            <li class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">期限間近の工程はありません</li>
                        @else
                            @foreach($upcomingTasks as $task)
                                <li class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 border-l-4"
                                    style="border-left-color: {{ $task->project->color ?? '#6c757d' }};">
                                    @include('home.partials.upcoming-task-item', ['task' => $task])
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">案件概要</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">全案件数:</span>
                            <span
                                class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-700 dark:text-blue-200">{{ $projectCount }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">進行中の案件:</span>
                            <span
                                class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-200">{{ $activeProjectCount }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">全工程数:</span>
                            <span
                                class="px-2 py-1 text-xs font-semibold text-indigo-800 bg-indigo-100 rounded-full dark:bg-indigo-700 dark:text-indigo-200">{{ $taskCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function initializeRequestCheckboxes(container) {
                const checkboxes = container.querySelectorAll('.request-item-checkbox');
                checkboxes.forEach(checkbox => {
                    if (checkbox.dataset.initialized) return;
                    checkbox.dataset.initialized = true;

                    checkbox.addEventListener('change', function () {
                        const itemId = this.dataset.itemId;
                        const isCompleted = this.checked;
                        const listItem = this.closest('li');

                        fetch(`/requests/items/${itemId}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ is_completed: isCompleted })
                        })
                            .then(response => response.ok ? response.json() : Promise.reject('Update failed'))
                            .then(data => {
                                if (data.success) {
                                    listItem.classList.toggle('opacity-50', isCompleted);
                                    listItem.querySelector('.item-content').classList.toggle('line-through', isCompleted);
                                } else {
                                    this.checked = !isCompleted;
                                }
                            })
                            .catch(error => {
                                alert('更新に失敗しました。');
                                this.checked = !isCompleted;
                            });
                    });
                });
            }
            initializeRequestCheckboxes(document.body);
        });
    </script>
@endpush

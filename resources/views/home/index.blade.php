@extends('layouts.app')

@section('title', 'ホーム')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">ホーム</h1>
            <div class="flex-shrink-0">
                @can('create', App\Models\Project::class)
                    <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i
                            class="fas fa-plus mr-1"></i>新規衣装案件</x-primary-button>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- ▼▼▼ 左カラム（メインコンテンツ） ▼▼▼ --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- ▼▼▼【ここから追加】現在進行中の工程セクション ▼▼▼ --}}
                @if($runningWorkLogs->isNotEmpty())
                    <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                        {{-- アコーディオンヘッダー --}}
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

                        {{-- アコーディオンコンテンツ --}}
                        <div x-show="open" x-transition class="divide-y divide-gray-200 dark:divide-gray-700" style="display: none;">
                            @foreach ($runningWorkLogs as $workLog)
                                @php $task = $workLog->task; @endphp
                                @if ($task)
                                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 border-l-4" style="border-left-color: {{ $task->project->color ?? '#6c757d' }};">
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                            <div class="flex-grow min-w-0">
                                                {{-- 案件名 --}}
                                                <p class="text-xs font-semibold truncate" style="color: {{ $task->project->color ?? '#6c757d' }};" title="案件: {{ $task->project->title }}">
                                                    <a href="{{ route('projects.show', $task->project) }}" class="hover:underline">
                                                        {{ $task->project->title }}
                                                    </a>
                                                </p>
                                                {{-- 工程名 --}}
                                                <p class="text-lg font-medium text-gray-800 dark:text-gray-100 whitespace-normal break-words">
                                                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                                        {{ $task->name }}
                                                    </a>
                                                </p>
                                                {{-- 担当者 --}}
                                                @if($task->assignees->isNotEmpty())
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1" title="担当: {{ $task->assignees->pluck('name')->join(', ') }}">
                                                    <i class="fas fa-users fa-fw mr-1 text-gray-400"></i>担当: {{ $task->assignees->pluck('name')->join(', ') }}
                                                </p>
                                                @endif
                                                {{-- 作業中のメンバー --}}
                                                @if(isset($task->workingUsers) && $task->workingUsers->isNotEmpty())
                                                <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate mt-1" title="作業中: {{ $task->workingUsers->pluck('name')->join(', ') }}">
                                                    <i class="fas fa-play-circle fa-fw mr-1 animate-pulse"></i>作業中: {{ $task->workingUsers->pluck('name')->join(', ') }}
                                                </p>
                                                @endif
                                                {{-- 期限 --}}
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
                                            {{-- タイマーコントロール --}}
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
                                                                {{-- JavaScriptがタイマーボタンをここに生成します --}}
                                                            </div>
                                                        @else
                                                            {{-- 表示専用のタイマー状況コンテナ --}}
                                                            <div class="timer-display-only"
                                                                data-task-id="{{ $task->id }}"
                                                                data-task-status="{{ $task->status }}"
                                                                data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}">
                                                                {{-- JavaScriptがこの中身を生成します --}}
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

                {{-- 全ユーザーの生産性トラッカー  --}}
                @can('viewAllProductivity', App\Models\User::class) {{-- 権限があるユーザーのみ表示 --}}
                <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg mt-6">
                    {{-- アコーディオンヘッダー --}}
                    <div @click="open = !open"
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-purple-500"></i>
                            全ユーザーの生産性
                        <i class="far fa-question-circle text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help ml-2"
                               title="【生産性バーの見方】
                                ■ バー全体：その日（または月）の総拘束時間（最初の出勤から最後の退勤まで）を表します。
                                ■ 青色（作業）：作業ログに記録された、実際のタスクに費やされた時間です。
                                ■ 黄色（休憩等）：勤怠ログに記録された、休憩や中抜けの時間です。
                                ■ 灰色（その他）：上記のいずれにも分類されない時間です。会議や準備、移動、または記録されていない作業などが含まれます。
                                "></i>
                        </h5>
                        <button aria-label="生産性を展開/折りたたむ">
                            <i class="fas fa-fw transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>

                    {{-- アコーディオンコンテンツ --}}
                    <div x-show="open" x-transition class="p-4 space-y-4" style="display: none;">
                        @foreach($productivitySummaries as $summary)
                            <div class="px-3 py-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">{{ $summary->user->name }}</h4>
                                {{-- 昨日のバー --}}
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
                                {{-- 今月のバー --}}
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
                        <div class="text-center pt-2">
                            <div class="flex justify-center space-x-4 text-xs text-gray-500 mt-1">
                                <span><i class="fas fa-square text-blue-500"></i> 作業</span>
                                <span><i class="fas fa-square text-yellow-400"></i> 休憩等</span>
                                <span><i class="fas fa-square text-gray-300 dark:text-gray-500"></i> その他</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            {{ $targetDate->isToday() ? '今日の' : $targetDate->isoFormat('M月D日(ddd)') . ' ' }}やることリスト
                        </h5>

                        <form action="{{ route('home.index') }}" method="GET" class="flex items-center gap-2">
                            <x-secondary-button as="a" href="{{ route('home.index') }}">今日</x-secondary-button>

                            <input type="date" name="date" id="date-picker" value="{{ $targetDate->format('Y-m-d') }}"
                                class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <x-primary-button type="submit">表示</x-primary-button>
                        </form>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @if(empty($workItemsByAssignee))
                            <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">本日の作業はありません</p>
                        @else
                            @foreach($workItemsByAssignee as $assigneeData)
                                <div class="p-4 {{ $assigneeData['assignee']->id === Auth::id() ? 'bg-blue-50 dark:bg-blue-900/50' : '' }}">
                                    @php
                                        // 表示中の担当者の本日の休日情報を取得
                                        $holidayForUser = $todaysHolidays->firstWhere('user_id', $assigneeData['assignee']->id);
                                        $holidayBadgeText = null;
                                        $holidayBadgeTextStyle = "";

                                        if ($holidayForUser) {
                                            $type = $holidayForUser->type; // period_type から type に変更

                                            if ($type === 'full_day_off') { // 'full' から 'full_day_off' に変更
                                                $holidayBadgeText = '休暇中 (全休)';
                                            } elseif ($type === 'am_off' && now()->hour < 12) { // 'am' から 'am_off' に変更
                                                $holidayBadgeText = '休暇中 (午前)';
                                            } elseif ($type === 'pm_off' && now()->hour >= 12) { // 'pm' から 'pm_off' に変更
                                                $holidayBadgeText = '休暇中 (午後)';
                                            } else {
                                                $holidayBadgeTextStyle = "display: none;";
                                            }
                                        }
                                    @endphp
                                    <h3 class="font-semibold mb-2 flex items-center gap-x-2 {{ $assigneeData['assignee']->id === Auth::id() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-800 dark:text-gray-200' }}">
                                        <i class="fas fa-user mr-1 {{ $assigneeData['assignee']->id === Auth::id() ? 'text-blue-500' : 'text-gray-400' }}"></i>
                                        <span>{{ $assigneeData['assignee']->name }}</span>
                                        @if($todaysHolidays->contains('user_id', $assigneeData['assignee']->id))
                                        <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 rounded-full" style="{{ $holidayBadgeTextStyle }}">{{ $holidayBadgeText }}</span>
                                        @endif
                                    </h3>
                                    <ul class="ml-6 divide-y space-y-2 divide-gray-200 dark:divide-gray-700">
                                        @foreach($assigneeData['items'] as $item)
                                            @if($item instanceof \App\Models\Task)
                                                @include('home.partials.home-task-item', ['task' => $item])
                                            @elseif($item instanceof \App\Models\RequestItem)
                                                @include('home.partials.home-request-item', ['item' => $item])
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- ▼▼▼ 右カラム（サイド情報） ▼▼▼ --}}
            <div class="space-y-6">
                {{-- オンラインのメンバー --}}
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
                            // ステータスごとのラベルとスタイルを定義
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
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $log->user->name }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$displayStatusKey] ?? '' }}">
                                        {{ $statusLabels[$displayStatusKey] ?? '不明' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                {{-- 本日の休日取得者 --}}
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

                {{-- 期限間近の工程 --}}
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

                {{-- 衣装案件概要 --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">衣装案件概要</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">全衣装案件数:</span>
                            <span
                                class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-700 dark:text-blue-200">{{ $projectCount }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">進行中の衣装案件:</span>
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
    {{-- ▼▼▼【ここを追加】依頼項目のチェックボックスを機能させるためのJS ▼▼▼ --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // この関数は、新しい要素がDOMに追加されたときにも呼び出せるように定義
            function initializeRequestCheckboxes(container) {
                const checkboxes = container.querySelectorAll('.request-item-checkbox');
                checkboxes.forEach(checkbox => {
                    // 重複してイベントリスナーが登録されるのを防ぐ
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
                                    this.checked = !isCompleted; // 失敗したら元に戻す
                                }
                            })
                            .catch(error => {
                                alert('更新に失敗しました。');
                                this.checked = !isCompleted;
                            });
                    });
                });
            }

            // 初期表示の要素にイベントリスナーを適用
            initializeRequestCheckboxes(document.body);
        });
    </script>
@endpush
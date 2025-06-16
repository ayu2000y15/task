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
                                        $holidayBadgeText = null; // 表示するバッジのテキスト（デフォルトは非表示）

                                        if ($holidayForUser) {
                                            $periodType = $holidayForUser->period_type;

                                            if ($periodType === 'full') {
                                                // 全休の場合は常に表示
                                                $holidayBadgeText = '休暇中 (全休)';
                                            } elseif ($periodType === 'am' && now()->hour < 12) {
                                                // 午前休で、現在の時刻が正午より前の場合のみ表示
                                                $holidayBadgeText = '休暇中 (午前)';
                                            } elseif ($periodType === 'pm' && now()->hour >= 12) {
                                                // 午後休で、現在の時刻が正午以降の場合のみ表示
                                                $holidayBadgeText = '休暇中 (午後)';
                                            }
                                        }
                                    @endphp
                                    <h3 class="font-semibold mb-2 flex items-center gap-x-2 {{ $assigneeData['assignee']->id === Auth::id() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-800 dark:text-gray-200' }}">
                                        <i class="fas fa-user mr-1 {{ $assigneeData['assignee']->id === Auth::id() ? 'text-blue-500' : 'text-gray-400' }}"></i>
                                        <span>{{ $assigneeData['assignee']->name }}</span>
                                        @if($todaysHolidays->contains('user_id', $assigneeData['assignee']->id))
                                            <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 rounded-full">休暇中</span>
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
                {{-- 本日の休日取得者 --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">本日の休日取得者</h5>
                    </div>
                    @if($todaysHolidays->isEmpty())
                        <p class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">本日の休日取得者はいません</p>
                    @else
                        @php
                            $periodTypes = ['full' => '全休', 'am' => '午前休', 'pm' => '午後休'];
                            $periodClasses = [
                                'full' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'am' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'pm' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
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
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $periodClasses[$holiday->period_type] ?? $periodClasses['full'] }}">
                                        {{ $periodTypes[$holiday->period_type] ?? '全休' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- 期限間近の工程 --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">期限間近の工程 (2日以内)</h5>
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
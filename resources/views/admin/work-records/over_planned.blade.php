@extends('layouts.app')

@section('title', '計画時間超過一覧')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
                            filtersOpen: {{ count(array_filter(request()->except(['page']))) > 0 ? 'true' : 'false' }}
                            }">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">計画時間超過一覧</h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
                <x-secondary-button onclick="if (history.length > 1) { history.back(); } else { window.location.href='{{ route('admin.work-records.index') }}'; }">
                    <i class="fas fa-arrow-left mr-1"></i>戻る
                </x-secondary-button>
            </div>
        </div>

        {{-- フィルターフォーム --}}
        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.work-records.over-planned') }}" method="GET">
                <div class="space-y-4">
                    {{-- 1行目: 作業者・案件・案件ステータス --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">作業者</label>
                            <select name="user_id" id="user_id" class="tom-select mt-1 block w-full">
                                <option value="">すべての作業者</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">案件</label>
                            <select name="project_id" id="project_id" class="tom-select mt-1 block w-full">
                                <option value="">すべての案件</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>
                                        {{ $project->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="project_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">案件ステータス</label>
                            <select name="project_status[]" id="project_status" multiple class="tom-select mt-1 block w-full">
                                @php
                                    $selectedStatuses = request('project_status', ['not_started', 'in_progress', 'on_hold']);
                                    if (!is_array($selectedStatuses)) {
                                        $selectedStatuses = [$selectedStatuses];
                                    }
                                @endphp
                                @foreach($projectStatusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(in_array($key, $selectedStatuses))>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 2行目: 開始日・終了日 --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始日</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了日</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.work-records.over-planned') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        リセット
                    </a>
                    <x-primary-button type="submit">
                        <i class="fas fa-search mr-2"></i> 絞り込む
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- フィルター適用状況の表示 --}}
        @if(count(array_filter(request()->except(['page']))) > 0)
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                <div class="flex items-center text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-filter mr-2"></i>
                    <span class="font-medium">フィルター適用中:</span>
                    @if(request('user_id'))
                        <span class="ml-2 px-2 py-1 bg-blue-200 dark:bg-blue-800 rounded-md">
                            作業者: {{ $users->firstWhere('id', request('user_id'))->name ?? '不明' }}
                        </span>
                    @endif
                    @if(request('project_id'))
                        <span class="ml-2 px-2 py-1 bg-blue-200 dark:bg-blue-800 rounded-md">
                            案件: {{ $projects->firstWhere('id', request('project_id'))->title ?? '不明' }}
                        </span>
                    @endif
                    @if(request('project_status'))
                        <span class="ml-2 px-2 py-1 bg-blue-200 dark:bg-blue-800 rounded-md">
                            ステータス:
                            @php
                                $statuses = is_array(request('project_status')) ? request('project_status') : [request('project_status')];
                                $statusLabels = collect($statuses)->map(fn($status) => $projectStatusOptions[$status] ?? $status)->join(', ');
                            @endphp
                            {{ $statusLabels }}
                        </span>
                    @else
                        <span class="ml-2 px-2 py-1 bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 rounded-md text-xs">
                            ステータス: 未着手・進行中・保留中 (デフォルト)
                        </span>
                    @endif
                    @if(request('start_date') || request('end_date'))
                        <span class="ml-2 px-2 py-1 bg-blue-200 dark:bg-blue-800 rounded-md">
                            期間: {{ request('start_date') ?? '開始日未指定' }} 〜 {{ request('end_date') ?? '終了日未指定' }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        {{-- サマリー情報 --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <dt class="text-sm font-medium text-red-800 dark:text-red-300">超過工程数</dt>
                        <dd class="text-2xl font-semibold text-red-900 dark:text-red-100">{{ $overTasks->count() }}件</dd>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-orange-600 dark:text-orange-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <dt class="text-sm font-medium text-orange-800 dark:text-orange-300">総超過時間</dt>
                        <dd class="text-2xl font-semibold text-orange-900 dark:text-orange-100">{{ format_seconds_to_hms($totalOverageSeconds) }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-percentage text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <dt class="text-sm font-medium text-yellow-800 dark:text-yellow-300">平均超過率</dt>
                        <dd class="text-2xl font-semibold text-yellow-900 dark:text-yellow-100">{{ number_format($averageOveragePercentage, 1) }}%</dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- 超過一覧テーブル --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                案件
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                工程名
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                キャラクター
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                実作業者（時間）
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                計画時間
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                実績時間
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                超過時間
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                超過率
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300  tracking-wider">
                                作業ログ数
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($overTasks as $index => $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" onclick="toggleWorkLogs({{ $index }})">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center">
                                        <i id="toggle-icon-{{ $index }}" class="fas fa-chevron-right text-gray-400 mr-2 transition-transform duration-200"></i>
                                        @if($item['task']->project)
                                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $item['task']->project->color ?? '#cccccc' }}"></div>
                                            {{ $item['task']->project->title }}
                                        @else
                                            <span class="text-gray-400">案件なし</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 duration-200">
                                    <div class="max-w-xs truncate" title="{{ $item['task']->name }}">{{ $item['task']->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item['task']->character->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    @if($item['actual_workers']->isNotEmpty())
                                        @foreach($item['actual_workers'] as $worker)
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                                <span class="font-medium">{{ $worker['user']->name }}</span>
                                                <span class="text-xs opacity-75">({{ format_seconds_to_hms($worker['total_seconds']) }})</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400">作業実績なし</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ format_seconds_to_hms($item['planned_seconds']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ format_seconds_to_hms($item['actual_seconds']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600 dark:text-red-400">
                                    {{ format_seconds_to_hms($item['overage_seconds']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @php
                                        $percentage = $item['overage_percentage'];
                                        $colorClass = '';
                                        if ($percentage >= 100) {
                                            $colorClass = 'text-red-600 dark:text-red-400';
                                        } elseif ($percentage >= 50) {
                                            $colorClass = 'text-orange-600 dark:text-orange-400';
                                        } else {
                                            $colorClass = 'text-yellow-600 dark:text-yellow-400';
                                        }
                                    @endphp
                                    <span class="{{ $colorClass }}">
                                        +{{ number_format($percentage, 1) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item['work_logs_count'] }}件
                                </td>
                            </tr>
                            {{-- 作業ログ詳細行（初期は非表示） --}}
                            <tr id="work-logs-{{ $index }}" class="hidden bg-gray-50 dark:bg-gray-900">
                                <td colspan="9" class="px-6 py-4">
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-t-lg">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                <i class="fas fa-list-ul mr-2"></i>作業ログ詳細 - {{ $item['task']->name }}
                                            </h4>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="bg-gray-50 dark:bg-gray-600">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">作業者</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">開始時刻</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">終了時刻</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">作業時間</th>
                                                        {{-- 時給・給与は非表示 --}}
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">メモ</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach(($item['relevant_work_logs'] ?? $item['task']->workLogs->where('status', 'stopped'))->sortBy('start_time') as $log)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                                {{ $log->user->name }}
                                                                @if($log->is_manually_edited)
                                                                    <i class="fas fa-pencil-alt text-xs text-orange-500 ml-1" title="手動修正済み"></i>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                                {{ optional($log->display_start_time)->format('n/j H:i:s') }}
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                                {{ optional($log->display_end_time)->format('n/j H:i:s') }}
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                                {{ format_seconds_to_hms($log->effective_duration) }}
                                                            </td>
                                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                                                @if($log->memo)
                                                                    <div class="truncate" title="{{ $log->memo }}">
                                                                        {{ $log->memo }}
                                                                    </div>
                                                                @else
                                                                    <span class="text-gray-400 italic">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    {{-- 合計行 --}}
                                                    <tr class="bg-blue-50 dark:bg-blue-900/20 font-medium">
                                                        <td class="px-4 py-2 text-sm text-blue-900 dark:text-blue-100">合計</td>
                                                        <td class="px-4 py-2"></td>
                                                        <td class="px-4 py-2"></td>
                                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-blue-900 dark:text-blue-100">
                                                            {{ format_seconds_to_hms($item['actual_seconds']) }}
                                                        </td>
                                                        <td class="px-4 py-2"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-check-circle text-green-500 text-4xl mb-2"></i>
                                        <p>計画時間を超過している工程はありません。</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    <script>
        function toggleWorkLogs(index) {
            const detailRow = document.getElementById(`work-logs-${index}`);
            const icon = document.getElementById(`toggle-icon-${index}`);

            if (detailRow.classList.contains('hidden')) {
                detailRow.classList.remove('hidden');
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
                icon.style.transform = 'rotate(90deg)';
            } else {
                detailRow.classList.add('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // 全て展開/全て折り畳みボタンの追加
        document.addEventListener('DOMContentLoaded', function() {
            // ページロード時に全展開/全折り畳みボタンを追加
            const header = document.querySelector('h1');
            if (header && {{ $overTasks->count() }} > 0) {
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'flex items-center space-x-2 mt-2';

                const expandAllBtn = document.createElement('button');
                expandAllBtn.className = 'text-xs px-3 py-1 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-md transition-colors';
                expandAllBtn.innerHTML = '<i class="fas fa-expand-alt mr-1"></i>全て展開';
                expandAllBtn.onclick = function() {
                    for (let i = 0; i < {{ $overTasks->count() }}; i++) {
                        const detailRow = document.getElementById(`work-logs-${i}`);
                        const icon = document.getElementById(`toggle-icon-${i}`);
                        if (detailRow && detailRow.classList.contains('hidden')) {
                            toggleWorkLogs(i);
                        }
                    }
                };

                const collapseAllBtn = document.createElement('button');
                collapseAllBtn.className = 'text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md transition-colors';
                collapseAllBtn.innerHTML = '<i class="fas fa-compress-alt mr-1"></i>全て折り畳み';
                collapseAllBtn.onclick = function() {
                    for (let i = 0; i < {{ $overTasks->count() }}; i++) {
                        const detailRow = document.getElementById(`work-logs-${i}`);
                        if (detailRow && !detailRow.classList.contains('hidden')) {
                            toggleWorkLogs(i);
                        }
                    }
                };

                buttonContainer.appendChild(expandAllBtn);
                buttonContainer.appendChild(collapseAllBtn);
                header.parentNode.insertBefore(buttonContainer, header.nextSibling);
            }
        });
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // フィルター用のTomSelect
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, {
                    plugins: ['clear_button'],
                    create: false,
                });
            });
        });
    </script>
@endpush

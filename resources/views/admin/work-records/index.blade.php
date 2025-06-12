@extends('layouts.app')

@section('title', '作業実績一覧')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
    x-data="{
    filtersOpen: {{ count(array_filter(request()->except('page'))) > 0 ? 'true' : 'false' }},
    rateFormOpen: false
    }">

    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業実績一覧 (管理者用)</h1>
        <div class="flex items-center space-x-2">
            <x-secondary-button @click="rateFormOpen = !rateFormOpen">
                <i class="fas fa-yen-sign mr-1"></i>時給登録
                <span x-show="rateFormOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                <span x-show="!rateFormOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
            </x-secondary-button>
            <x-secondary-button @click="filtersOpen = !filtersOpen">
                <i class="fas fa-filter mr-1"></i>フィルター
                <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
            </x-secondary-button>
            <x-secondary-button
                onclick="window.location.href='{{ route('admin.work-records.by-project') }}'"
                class="!bg-green-600 hover:!bg-green-700 dark:!bg-green-700 dark:hover:!bg-green-800 !text-white !border-transparent">
                <i class="fas fa-briefcase mr-1"></i>案件別明細
            </x-secondary-button>
        </div>
    </div>

    {{-- ▼▼▼【ここから修正】時給登録フォームを開閉式に ▼▼▼ --}}
    <div x-show="rateFormOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 border-b dark:border-gray-700 pb-2">ユーザー時給登録</h2>
        <form action="{{ route('admin.work-records.update-rate') }}" method="POST">
            @csrf
            @method('POST')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="rate_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">対象ユーザー</label>
                    <select name="user_id" id="rate_user_id" class="tom-select-rate mt-1 block w-full" required>
                        <option value="">ユーザーを選択...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" data-rate="{{ $user->hourly_rate ?? 0 }}">
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="hourly_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">新しい時給</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm"> ¥ </span>
                        </div>
                        <input type="number" name="hourly_rate" id="hourly_rate"
                                class="pl-10 block w-full border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="1000" step="0.01" required>
                    </div>
                </div>
                <div>
                    <x-primary-button type="submit">時給を登録/更新</x-primary-button>
                </div>
            </div>
        </form>
        </div>

        {{-- ▼▼▼【ここから修正】検索フォームを開閉式に ▼▼▼ --}}
        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
        <form action="{{ route('admin.work-records.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
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
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始日</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了日</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                    </div>
                </div>
                <div class="flex space-x-2">
                    {{-- このdivはボタンの縦位置を揃えるために残します --}}
                </div>
            </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.work-records.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                    リセット
                </a>
                <x-primary-button type="submit">
                    <i class="fas fa-search mr-2"></i> 絞り込む
                </x-primary-button>
            </div>
        </form>
    </div>

        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4">期間別サマリー</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <h3 class="font-semibold text-lg">本日</h3>
                        <span class="text-sm font-bold text-green-700 dark:green-blue-400">　({{ $summaryDateStrings['today'] }})</span>
                    </div>
                    @include('admin.work-records.partials.summary-table', ['summary' => $todaySummary])
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <h3 class="font-semibold text-lg">今週</h3>
                        <span class="text-sm font-bold text-green-700 dark:green-blue-400">　({{ $summaryDateStrings['week'] }})</span>
                    </div>
                    @include('admin.work-records.partials.summary-table', ['summary' => $weekSummary])
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <h3 class="font-semibold text-lg">今月</h3>
                        <span class="text-sm font-bold text-green-700 dark:green-blue-400">　({{ $summaryDateStrings['month'] }})</span>
                    </div>
                    @include('admin.work-records.partials.summary-table', ['summary' => $monthSummary])
                </div>
            </div>
        </div>

        {{-- 集計結果 --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden mb-6">
            <div class="p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold">
                    合計作業時間 (フィルタ後):
                    <span class="text-blue-600 dark:text-blue-400">
                        {{ gmdate('H:i:s', $totalSeconds) }}
                    </span>
                </h3>
            </div>
        </div>

        {{-- 実績一覧テーブル --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                作業者</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                案件</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    キャラクター</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                工程</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                開始日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                終了日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                作業時間</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                時給</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                概算給与</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                メモ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($workLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $log->user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->task->project->title ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ optional($log->task->character)->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->task->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->start_time->format('m/d H:i:s') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ optional($log->end_time)->format('m/d H:i:s') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @php
                                        $duration = $log->effective_duration;
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        $seconds = $duration % 60;
                                    @endphp
                                    {{ gmdate('H:i:s', $log->effective_duration) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($log->user->hourly_rate)
                                        ¥{{ number_format($log->user->hourly_rate) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($log->user->hourly_rate)
                                        ¥{{ number_format(($duration / 3600) * $log->user->hourly_rate, 0) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-wrap">
                                    {{ $log->memo }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    該当する作業実績がありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $workLogs->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, {
                    plugins: ['clear_button'],
                    create: false,
                });
            });

            const rateUserSelect = new TomSelect('#rate_user_id', {
                create: false,
            });

            const hourlyRateInput = document.getElementById('hourly_rate');

            rateUserSelect.on('change', function(userId) {
                if (userId) {
                    const selectedOption = this.getOption(userId);
                    const currentRate = selectedOption.dataset.rate || 0;
                    hourlyRateInput.value = parseFloat(currentRate).toFixed(2);
                } else {
                    hourlyRateInput.value = '';
                }
            });
        });
    </script>
@endpush
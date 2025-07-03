@extends('layouts.app')

@section('title', '作業実績一覧')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
                                                                                filtersOpen: {{ count(array_filter(request()->except(['page', 'sort', 'direction']))) > 0 ? 'true' : 'false' }},
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
                <x-secondary-button onclick="window.location.href='{{ route('admin.work-records.daily-log') }}'"
                    class="!bg-orange-600 hover:!bg-orange-700 dark:!bg-orange-700 dark:hover:!bg-orange-800 !text-white !border-transparent">
                    <i class="fas fa-clipboard-list mr-1"></i>日別 作業ログ
                </x-secondary-button>
                <x-secondary-button onclick="window.location.href='{{ route('admin.work-records.by-project') }}'"
                    class="!bg-green-600 hover:!bg-green-700 dark:!bg-green-700 dark:hover:!bg-green-800 !text-white !border-transparent">
                    <i class="fas fa-briefcase mr-1"></i>案件別 作業ログ
                </x-secondary-button>
            </div>
        </div>

        {{-- 時給登録フォーム --}}
        <div x-show="rateFormOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2 border-b dark:border-gray-700 pb-2">
                ユーザー時給一括登録</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                新しい時給と適用日を入力した行のみが登録・更新されます。入力しない行は無視されます。
            </p>
            <form action="{{ route('admin.work-records.update-rates') }}" method="POST">
                @csrf
                @method('POST')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    ユーザー名</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    過去の時給履歴 (最大2件)</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    新しい時給</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    適用日</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($users as $user)
                                <tr>
                                    <input type="hidden" name="rates[{{ $loop->index }}][user_id]" value="{{ $user->id }}">
                                    <td
                                        class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @forelse($user->hourlyRates as $rate)
                                            <div class="{{ !$loop->first ? 'mt-1' : '' }}">
                                                ¥{{ number_format($rate->rate) }}
                                                <span class="text-xs">({{ $rate->effective_date->format('Y/m/d') }})</span>
                                            </div>
                                        @empty
                                            <span class="text-xs italic">未登録</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm"> ¥ </span>
                                            </div>
                                            <input type="number" name="rates[{{ $loop->index }}][rate]"
                                                class="pl-10 block w-full text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="1200" step="1">
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input type="date" name="rates[{{ $loop->index }}][effective_date]"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <x-primary-button type="submit">一括で登録/更新する</x-primary-button>
                </div>
            </form>
        </div>


        {{-- フィルターフォーム --}}
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
                        <label for="project_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">案件</label>
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
                            <label for="start_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始日</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label for="end_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了日</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
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

        {{-- 月の給与 --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">月の給与</h2>
                {{-- 【追加】計算方法のヒント用ツールチップ --}}
                <div x-data="{ showHint: false }" class="relative flex items-center">
                    <i @mouseenter="showHint = true" @mouseleave="showHint = false"
                        class="fas fa-info-circle text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"></i>
                    <div x-show="showHint" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-2" style="display: none;"
                        class="absolute top-full left-1/2 -translate-x-1/2 mb-2 w-72 p-3 bg-gray-800 text-white text-xs rounded-lg shadow-lg z-50">
                        <p class="font-semibold border-b border-gray-600 pb-1 mb-1">給与の計算方法:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>勤怠明細ページの「月の合計」と一致します。</li>
                            <li>手動編集された日と、打刻ログから自動計算された日の両方が含まれます。</li>
                            <li>給与は (拘束時間 - 休憩時間) × 時給 で計算されます。</li>
                        </ul>
                        {{-- ツールチップの矢印 --}}
                        <div
                            class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-x-4 border-x-transparent border-t-4 border-t-gray-800">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                {{-- 「今月」のサマリー --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <h3 class="font-semibold text-lg">今月</h3>
                        <span
                            class="text-sm font-bold text-green-700 dark:green-blue-400">　({{ $summaryDateStrings['month'] }})</span>
                    </div>
                    @include('admin.work-records.partials.monthly-attendance-summary-table', ['summary' => $monthSummary])
                </div>

            </div>
        </div>

        {{-- 集計結果 --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden mb-6">
            <div class="p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold">
                    合計作業時間 (フィルタ後):
                    <span class="text-blue-600 dark:text-blue-400">
                        {{ format_seconds_to_hms($totalSeconds) }}
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
                            @include('admin.work-records.partials.sortable-th', ['label' => '作業者', 'sortKey' => 'user', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.work-records.partials.sortable-th', ['label' => '案件', 'sortKey' => 'project', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.work-records.partials.sortable-th', ['label' => 'キャラクター', 'sortKey' => 'character', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.work-records.partials.sortable-th', ['label' => '工程', 'sortKey' => 'task', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.work-records.partials.sortable-th', ['label' => '開始日時', 'sortKey' => 'start_time', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.work-records.partials.sortable-th', ['label' => '終了日時', 'sortKey' => 'end_time', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.work-records.partials.sortable-th', ['label' => '作業時間', 'sortKey' => 'duration', 'currentSort' => $sort, 'currentDirection' => $direction])
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                時給</th>
                            @include('admin.work-records.partials.sortable-th', ['label' => '概算給与', 'sortKey' => 'salary', 'currentSort' => $sort, 'currentDirection' => $direction])
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-[180px]:">
                                メモ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($workLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $log->user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->task->project->title ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ optional($log->task->character)->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->task->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->start_time->format('m/d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ optional($log->end_time)->format('m/d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ format_seconds_to_hms($log->effective_duration) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($log->current_rate)
                                        ¥{{ number_format($log->current_rate) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($log->current_rate)
                                        ¥{{ number_format($log->calculated_salary, 0) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 ">
                                    {!! nl2br($log->memo) !!}
                                </td>
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
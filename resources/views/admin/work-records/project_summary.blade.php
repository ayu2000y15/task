@extends('layouts.app')

@section('title', '案件別 作業ログ')

@push('styles')
    <style>
        .details-row { display: none; }
        .details-row.is-open { display: table-row; }
        .summary-row { cursor: pointer; }
        .details-icon { transition: transform 0.2s ease-in-out; }
        .details-icon.is-rotated { transform: rotate(-180deg); }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                <a href="{{ route('admin.work-records.index') }}" class="text-blue-600 hover:underline">作業実績一覧</a>
                <i class="fas fa-chevron-right fa-xs mx-2"></i>
                案件別 作業ログ (案件管理者用)
            </h1>
            <a href="{{ route('admin.work-records.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-arrow-left mr-2"></i>実績一覧に戻る
            </a>
        </div>

        {{-- 総合計サマリー --}}
        <div class="mb-8 bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="p-6">
                    <h2 class="text-base font-semibold text-gray-600 dark:text-gray-300 mb-2 flex items-center"><i class="fas fa-stream fa-fw mr-2 text-gray-400"></i>合計作業時間</h2>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200">{{ gmdate('H:i:s', $grandTotalSeconds) }}</p>
                </div>
                <div class="p-6 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-600 dark:text-gray-300 mb-2 flex items-center"><i class="fas fa-clock fa-fw mr-2 text-gray-400"></i>実働時間</h2>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ gmdate('H:i:s', $grandTotalActualSeconds) }}</p>
                </div>
            </div>
        </div>

        {{-- 注釈 --}}
        <div
            class="p-2 my-2 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-500">
            <i class="fas fa-info-circle mr-1"></i>
            同担当者で作業時間が重複している場合、重複して計算されていますのでご注意ください。<br>
            　例、<br>
            　　　6/12　デザイン　　10:00～15:00　担当者①　　作業時間→00:05:00<br>
            　　　6/12　縫製仕様　12:00～18:00　担当者①　　作業時間→00:06:00<br>
            　　　合計作業時間：00:11:00<br>
            　※12:00～15:00の間は作業時間が重複していますが、工程別に集計しているため重複して計算されてしまいます。<br>
            　　案件タイトルの「実働時間」は案件内で重複する作業時間を考慮した合計時間です。
        </div>

        {{-- 案件別カード --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            @forelse ($summary as $project)
                <div
                    class="bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 flex flex-col">
                    {{-- 案件ヘッダー --}}
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center min-w-0">
                            <div class="flex-shrink-0 h-8 w-8 rounded flex items-center justify-center text-white font-bold text-sm"
                                style="background-color: {{ $project['color'] }};">
                                {{ mb_substr($project['name'], 0, 1) }}
                            </div>
                            <div class="ml-4 min-w-0">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $project['name'] }}
                                </h2>
                            </div>
                            @php
                                $statusColors = [
                                    'not_started' => 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                ];
                                $colorClasses = $statusColors[$project['status_key']] ?? 'bg-gray-200 text-gray-700';
                            @endphp
                            <span
                                class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 {{ $colorClasses }}">
                                {{ $project['status_text'] }}
                            </span>
                        </div>
                        <div class="text-right flex-shrink-0 flex items-start space-x-4">
                            <div class="space-y-1">
                                <div class="text-xs font-bold text-gray-500 dark:text-gray-400">合計</div>
                                <div class="text-sm">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ gmdate('H:i:s', $project['project_total_seconds']) }}</span>
                                </div>
                            </div>
                            <div class="border-l border-gray-300 dark:border-gray-600 h-8"></div>
                            <div class="space-y-1">
                                <div class="text-xs font-bold text-gray-500 dark:text-gray-400">実働</div>
                                <div class="text-sm">
                                    <span class="font-bold text-green-600 dark:text-green-400">{{ gmdate('H:i:s', $project['project_actual_work_seconds']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- キャラクターと工程のテーブル --}}
                    <div class="overflow-x-auto flex-grow">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-900/50">
                                <tr>
                                    <th class="pl-4 w-10"></th>
                                    <th class="pl-6 pr-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">工程</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">実績担当者</th>
                                    <th class="pl-3 pr-6 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">作業時間</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($project['characters'] as $character)
                                    <tr class="border-t-2 border-gray-200 dark:border-gray-700">
                                        <td class="px-4 py-2 font-semibold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/20" colspan="4">
                                            <div class="flex gap-3 items-center w-full">
                                                <div class="flex items-center">
                                                    <i class="fas fa-user fa-fw mr-2 {{ $character['name'] === 'キャラクターなし' ? 'text-gray-400' : '' }}"></i>
                                                    {{ $character['name'] }}
                                                </div>
                                                <div class="text-right flex items-center space-x-3 text-xs">
                                                    <div>
                                                        <span class="text-gray-500 dark:text-gray-400">合計作業時間: </span>
                                                        <span class="font-medium text-green-600 dark:text-green-400">{{ gmdate('H:i:s', $character['character_total_seconds']) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @forelse ($character['tasks'] as $task)
                                        <tr class="summary-row border-t border-gray-200 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20" data-details-target="details-{{ $project['id'] }}-{{ $character['id'] }}-{{ $task['id'] }}">
                                            <td class="pl-4 text-center text-gray-400"><i class="details-icon fas fa-chevron-down fa-xs"></i></td>
                                            <td class="pl-8 pr-2 py-2 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $task['name'] }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ implode(', ', $task['workers']) }}</td>
                                            <td class="pl-3 pr-6 py-2 whitespace-nowrap font-mono text-right text-green-700 dark:text-green-400">{{ gmdate('H:i:s', $task['total_seconds']) }}</td>
                                        </tr>
                                        <tr class="details-row bg-gray-50 dark:bg-gray-900/20" id="details-{{ $project['id'] }}-{{ $character['id'] }}-{{ $task['id'] }}">
                                            <td class="p-0" colspan="4">
                                                <div class="p-4">
                                                    <table class="min-w-full text-xs">
                                                        <thead class="border-b-2 border-gray-300 dark:border-gray-600">
                                                            <tr>
                                                                <th class="py-1 px-2 text-left font-semibold">担当者</th>
                                                                <th class="py-1 px-2 text-left font-semibold">開始日時</th>
                                                                <th class="py-1 px-2 text-left font-semibold">終了日時</th>
                                                                <th class="py-1 px-2 text-left font-semibold">作業時間</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($task['logs'] as $log)
                                                                <tr class="border-b border-gray-200 dark:border-gray-600/50">
                                                                    <td class="py-1 px-2">{{ $log['worker_name'] }}</td>
                                                                    <td class="py-1 px-2">{{ $log['start_time'] }}</td>
                                                                    <td class="py-1 px-2">{{ $log['end_time'] }}</td>
                                                                    <td class="py-1 px-2 font-mono">{{ $log['duration_formatted'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="border-t border-gray-200 dark:border-gray-700/50">
                                            <td class="pl-12 pr-6 py-3 text-sm text-gray-400 italic" colspan="4">このグループの作業記録はありません。</td>
                                        </tr>
                                    @endforelse
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="lg:col-span-2 text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <i class="fas fa-box-open fa-3x text-gray-300 dark:text-gray-500 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">作業実績のある案件はありません。</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

{{-- ▼▼▼【ここから修正】アコーディオン用のJavaScriptを追加 ▼▼▼ --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const summaryRows = document.querySelectorAll('.summary-row');

        summaryRows.forEach(row => {
            row.addEventListener('click', () => {
                const targetId = row.dataset.detailsTarget;
                const detailsRow = document.getElementById(targetId);
                const icon = row.querySelector('.details-icon');

                if (detailsRow) {
                    detailsRow.classList.toggle('is-open');
                }
                if (icon) {
                    icon.classList.toggle('is-rotated');
                }
            });
        });
    });
</script>
@endpush
{{-- ▲▲▲ 修正ここまで ▲▲▲ --}}
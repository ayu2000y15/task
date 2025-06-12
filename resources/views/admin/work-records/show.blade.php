@extends('layouts.app')

@section('title', $user->name . 'さんの作業明細')

@push('styles')
    <style>
        .details-row {
            display: none;
        }

        .details-row.is-open {
            display: table-row;
        }

        .summary-row {
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                <a href="{{ route('admin.work-records.index') }}" class="text-blue-600 hover:underline">作業実績一覧</a>
                <i class="fas fa-chevron-right fa-xs mx-2"></i>
                {{ $user->name }}さんの作業明細
            </h1>
            {{-- 月ナビゲーション --}}
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.work-records.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-arrow-left mr-2"></i>実績一覧に戻る
                </a>
                <a href="{{ route('admin.work-records.show', ['user' => $user, 'month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                    <i class="fas fa-chevron-left"></i> 前月
                </a>
                <span class="font-semibold text-lg">{{ $targetMonth->format('Y年n月') }}</span>
                <a href="{{ route('admin.work-records.show', ['user' => $user, 'month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                    次月 <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div
            class="p-2 my-1 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-500">
            <i class="fas fa-info-circle mr-1"></i>
            日給合計は「実働時間 × 時給」で計算されます。
        </div>

        {{-- 月次リスト --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto h-[75vh] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 relative">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                日付</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                詳細</th>

                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                出勤</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                退勤</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                拘束時間</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                実働時間</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                作業時間合計</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                休憩時間</th>
                            <th
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                日給合計</th>

                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $weekMap = ['日', '月', '火', '水', '木', '金', '土'];
                        @endphp
                        @forelse ($monthlyReport as $day)
                            @if($day['type'] === 'workday')
                                {{-- 作業日の行 --}}
                                <tr class="summary-row hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                    data-details-target="details-{{ $day['date']->format('Y-m-d') }}">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $day['date']->format('n/j') }}
                                        ({{ $weekMap[$day['date']->dayOfWeek] }})</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><i class="fas fa-chevron-down"></i></td>

                                    <td class="px-6 py-4 whitespace-nowrap">{{ $day['first_start_time']->format('H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $day['last_end_time']->format('H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ gmdate('H:i:s', $day['attendance_seconds']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                        {{ gmdate('H:i:s', $day['actual_work_seconds']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ gmdate('H:i:s', $day['total_work_seconds']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ gmdate('H:i:s', $day['total_break_seconds']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">¥{{ number_format($day['daily_salary'], 0) }}</td>
                                </tr>
                                {{-- 日次明細 (デフォルトで非表示) --}}
                                <tr class="details-row" id="details-{{ $day['date']->format('Y-m-d') }}">
                                    <td colspan="9" class="p-0">
                                        <div class="bg-gray-100 dark:bg-gray-900/50 p-4">
                                            <table class="min-w-full text-sm">
                                                <thead>
                                                    <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                                                        <th class="py-2 px-3 text-left">案件</th>
                                                        <th class="py-2 px-3 text-left">工程</th>
                                                        <th class="py-2 px-3 text-left">開始</th>
                                                        <th class="py-2 px-3 text-left">終了</th>
                                                        <th class="py-2 px-3 text-left">作業時間</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($day['logs'] as $log)
                                                        <tr class="border-b dark:border-gray-600">
                                                            <td class="py-2 px-3">{{ $log->task->project->title ?? '-' }}</td>
                                                            <td class="py-2 px-3">{{ $log->task->name ?? '-' }}</td>
                                                            <td class="py-2 px-3">{{ $log->start_time->format('H:i') }}</td>
                                                            <td class="py-2 px-3">{{ $log->end_time->format('H:i') }}</td>
                                                            <td class="py-2 px-3">{{ gmdate('H:i:s', $log->effective_duration) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                {{-- 休みの日の行 --}}
                                <tr class="bg-gray-50/50 dark:bg-gray-800/30">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $day['date']->format('n/j') }}
                                        ({{ $weekMap[$day['date']->dayOfWeek] }})</td>
                                    <td colspan="8" class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm">休み</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">この月の作業実績はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                    {{-- 月の合計フッター --}}
                    <tfoot class="bg-gray-100 dark:bg-gray-700 sticky bottom-0">
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                            <td class="px-6 py-3 text-left" colspan="4">月の合計</td>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3 text-left whitespace-nowrap">
                                {{ gmdate('H:i:s', $monthTotalActualWorkSeconds) }}
                            </td>
                            <td class="px-6 py-3 text-left whitespace-nowrap">
                                {{ gmdate('H:i:s', $monthTotalEffectiveSeconds) }}
                            </td>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3 text-left whitespace-nowrap">¥{{ number_format($monthTotalSalary, 0) }}
                            </td>
                            <td class="px-6 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const summaryRows = document.querySelectorAll('.summary-row');
            summaryRows.forEach(row => {
                row.addEventListener('click', () => {
                    const targetId = row.dataset.detailsTarget;
                    const detailsRow = document.getElementById(targetId);
                    if (detailsRow) {
                        detailsRow.classList.toggle('is-open');
                        const icon = row.querySelector('.fa-chevron-down, .fa-chevron-up');
                        if (icon) {
                            icon.classList.toggle('fa-chevron-down');
                            icon.classList.toggle('fa-chevron-up');
                        }
                    }
                });
            });
        });
    </script>
@endpush
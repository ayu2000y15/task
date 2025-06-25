@extends('layouts.app')

@section('title', '日別 作業ログ')

@push('styles')
    <style>
        /* [is-open]属性ではなく、クラスで制御 */
        .details-body {
            display: none;
            transition: all 0.3s ease-in-out;
            overflow: hidden;
        }

        .details-body.is-open {
            display: block;
        }

        .summary-header {
            cursor: pointer;
        }

        .details-icon {
            transition: transform 0.2s ease-in-out;
        }

        .details-icon.is-rotated {
            transform: rotate(-180deg);
        }
    </style>
@endpush


@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                @can('viewProjectSummary', App\Models\WorkLog::class)
                <a href="{{ route('admin.work-records.index') }}" class="text-blue-600 hover:underline">作業実績一覧</a>
                <i class="fas fa-chevron-right fa-xs mx-2"></i>
                @endcan

                日別 作業ログ
            </h1>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.work-records.by-project') }}"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    <i class="fas fa-folder-tree mr-2"></i>案件別サマリーに切替
                </a>
                @can('viewProjectSummary', App\Models\WorkLog::class)
                <a href="{{ route('admin.work-records.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-arrow-left mr-2"></i>実績一覧に戻る
                </a>
                @endcan
            </div>
        </div>

        {{-- ▼▼▼【変更】ここからカードベースのレイアウトに変更 ▼▼▼ --}}
        <div class="space-y-8">
            @php
                $weekMap = ['日', '月', '火', '水', '木', '金', '土'];
            @endphp
            @forelse ($dailySummary as $date => $tasksOnDate)
                {{-- 日付ごとのカード --}}
                <div
                    class="bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- 日付ヘッダー --}}
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        @php
                            $carbonDate = \Carbon\Carbon::parse($date);
                        @endphp
                        <h2 class="flex items-center text-lg font-bold text-gray-800 dark:text-gray-200">
                            <i class="fas fa-calendar-alt mr-3 text-gray-500 dark:text-gray-400"></i>
                            {{ $carbonDate->format('Y年n月j日') }} ({{ $weekMap[$carbonDate->dayOfWeek] }})
                        </h2>
                    </div>

                    {{-- 工程リスト --}}
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($tasksOnDate as $task)
                            <div class="task-block" data-task-id="{{ $task['id'] }}">
                                {{-- 工程サマリーヘッダー (クリックで開閉) --}}
                                <div
                                    class="summary-header p-4 lg:px-6 grid grid-cols-12 gap-4 items-center hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    {{-- 工程名・案件名 --}}
                                    <div class="col-span-12 md:col-span-5">
                                        <p class="font-bold text-base text-gray-800 dark:text-gray-200">{{ $task['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $task['project_name'] ?? 'N/A' }}
                                            @if($task['character_name']) ({{ $task['character_name'] }}) @endif
                                        </p>
                                    </div>
                                    {{-- 計画工数 --}}
                                    <div class="col-span-4 md:col-span-2 text-right">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">計画</p>
                                        <p class="font-mono text-gray-700 dark:text-gray-300">
                                            @if(!empty($task['planned_duration_minutes']))
                                                {{ format_seconds_to_hms($task['planned_duration_minutes'] * 60) }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                    {{-- 実績時間 --}}
                                    <div class="col-span-4 md:col-span-2 text-right">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">実績(日合計)</p>
                                        <p class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                                            {{ format_seconds_to_hms($task['total_seconds_on_day']) }}</p>
                                    </div>
                                    {{-- 差異 --}}
                                    <div class="col-span-3 md:col-span-2 text-right">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">差異</p>
                                        <p class="font-mono text-lg">
                                            @php
                                                $plannedSeconds = ($task['planned_duration_minutes'] ?? 0) * 60;
                                                $actualSeconds = $task['total_seconds_on_day'];
                                                if ($plannedSeconds > 0 && $actualSeconds > 0) {
                                                    $diff = $actualSeconds - $plannedSeconds;
                                                    $color = $diff > 0 ? 'text-red-500' : 'text-green-500';
                                                    $prefix = $diff > 0 ? '+' : '';
                                                    echo "<span class='{$color}'>" . $prefix . format_seconds_to_hms(abs($diff)) . "</span>";
                                                } else {
                                                    echo "<span class='text-gray-400'>-</span>";
                                                }
                                            @endphp
                                        </p>
                                    </div>
                                    {{-- 開閉アイコン --}}
                                    <div class="col-span-1 text-right text-gray-400 dark:text-gray-500">
                                        <i class="details-icon fas fa-chevron-down fa-lg is-rotated"></i>
                                    </div>
                                </div>
                                {{-- 詳細ログ (デフォルトで表示) --}}
                                <div class="details-body bg-gray-50 dark:bg-gray-800/50 is-open">
                                    <div class="py-3 px-4 lg:px-6">
                                        <table class="min-w-full text-xs">
                                            <thead class="border-b-2 border-gray-300 dark:border-gray-600">
                                                <tr>
                                                    <th class="py-2 px-3 text-left font-semibold">実績担当者</th>
                                                    <th class="py-2 px-3 text-left font-semibold">開始</th>
                                                    <th class="py-2 px-3 text-left font-semibold">終了</th>
                                                    <th class="py-2 px-3 text-right font-semibold">作業時間</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($task['logs'] as $log)
                                                    <tr class="border-b border-gray-200 dark:border-gray-600/50 last:border-b-0">
                                                        <td class="py-2 px-3">{{ $log->user->name }}</td>
                                                        <td class="py-2 px-3">{{ $log->start_time->format('H:i') }}</td>
                                                        <td class="py-2 px-3">{{ optional($log->end_time)->format('H:i') }}</td>
                                                        <td class="py-2 px-3 font-mono text-right">
                                                            {{ format_seconds_to_hms($log->effective_duration) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <i class="fas fa-box-open fa-3x text-gray-300 dark:text-gray-500 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">表示できる作業ログがありません。</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const headers = document.querySelectorAll('.summary-header');

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const block = header.closest('.task-block');
                    const body = block.querySelector('.details-body');
                    const icon = header.querySelector('.details-icon');

                    if (body) {
                        body.classList.toggle('is-open');
                    }
                    if (icon) {
                        icon.classList.toggle('is-rotated');
                    }
                });
            });
        });
    </script>
@endpush
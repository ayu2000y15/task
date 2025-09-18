@extends('layouts.app')

@section('title', '日別 作業ログ')

@push('styles')
    <style>
        .details-body {
            display: none;
            transition: all 0.3s ease-in-out;
            overflow: hidden;
        }

        .details-body.is-open {
            display: block;
        }

        .summary-header,
        .date-summary-header,
        .project-summary-header {
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
                @can('viewAny', App\Models\WorkLog::class)
                    <a href="{{ route('admin.work-records.index') }}" class="text-blue-600 hover:underline">作業実績一覧</a>
                    <i class="fas fa-chevron-right fa-xs mx-2"></i>
                @endcan

                日別 作業ログ
            </h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button onclick="window.location.href='{{ route('admin.work-records.by-project') }}'"
                    class="!bg-green-600 hover:!bg-green-700 dark:!bg-green-700 dark:hover:!bg-green-800 !text-white !border-transparent">
                    <i class="fas fa-briefcase mr-1"></i>案件別 作業ログへ切替
                </x-secondary-button>
                @can('viewAny', App\Models\WorkLog::class)
                    <a href="{{ route('admin.work-records.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-arrow-left mr-2"></i>実績一覧に戻る
                    </a>
                @endcan
            </div>
        </div>

        <div class="space-y-8">
            @php
                $weekMap = ['日', '月', '火', '水', '木', '金', '土'];
                $today_str = \Carbon\Carbon::today()->format('Y-m-d');
            @endphp
            @forelse ($dailySummary as $date => $projectsOnDate)
                @php
                    $isToday = ($date === $today_str);
                @endphp
                {{-- 日付ごとのカード --}}
                <div
                    class="bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- 日付ヘッダー (クリックで開閉) --}}
                    <div
                        class="date-summary-header px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center hover:bg-gray-100 dark:hover:bg-gray-700">
                        @php
                            $carbonDate = \Carbon\Carbon::parse($date);
                        @endphp
                        <h2 class="flex items-center text-lg font-bold text-gray-800 dark:text-gray-200">
                            <i class="fas fa-calendar-alt mr-3 text-gray-500 dark:text-gray-400"></i>
                            {{ $carbonDate->format('Y年n月j日') }} ({{ $weekMap[$carbonDate->dayOfWeek] }})
                        </h2>
                        <i
                            class="details-icon fas fa-chevron-down fa-lg text-gray-400 dark:text-gray-500 @if($isToday) is-rotated @endif"></i>
                    </div>

                    {{-- 日付以下のコンテンツ (デフォルトで当日以外は非表示) --}}
                    <div class="details-body @if($isToday) is-open @endif">
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($projectsOnDate as $project)
                                <div class="project-block">
                                    {{-- ▼▼▼【変更】案件ヘッダーをクリック可能に ▼▼▼ --}}
                                    <div
                                        class="project-summary-header px-6 py-3 bg-gray-50/50 dark:bg-gray-800/20 flex justify-between items-center hover:bg-gray-100/50 dark:hover:bg-gray-700/20">
                                        <h3 class="flex items-center font-semibold text-gray-700 dark:text-gray-300">
                                            <span class="flex-shrink-0 h-4 w-4 rounded-full mr-3"
                                                style="background-color: {{ $project['color'] }};"></span>
                                            {{ $project['name'] }}
                                        </h3>
                                        {{-- 案件ごとの開閉アイコン --}}
                                        <i class="details-icon fas fa-chevron-down text-gray-400 dark:text-gray-500 is-rotated"></i>
                                    </div>

                                    {{-- ▼▼▼【変更】工程リストを折りたたみ対象のボディで囲む ▼▼▼ --}}
                                    <div class="details-body is-open">
                                        @foreach ($project['tasks'] as $task)
                                            <div class="task-block border-t border-gray-200 dark:border-gray-700/50"
                                                data-task-id="{{ $task['id'] }}">
                                                {{-- 工程サマリーヘッダー --}}
                                                <div
                                                    class="summary-header p-4 lg:px-6 grid grid-cols-12 gap-4 items-center hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                    <div class="col-span-11 md:col-span-5">
                                                        <p class="font-bold text-base text-gray-800 dark:text-gray-200">
                                                            {{ $task['name'] }}</p>
                                                        @if($task['character_name'])
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                <i class="fas fa-user-circle fa-fw mr-1"></i>{{ $task['character_name'] }}
                                                            </p>
                                                        @endif
                                                        @if(!empty($task['assignees']))
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                <i
                                                                    class="fas fa-user-tag fa-fw mr-1"></i>{{ implode(', ', $task['assignees']) }}
                                                            </p>
                                                        @endif
                                                    </div>
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
                                                    <div class="col-span-4 md:col-span-2 text-right">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">実績(日合計)</p>
                                                        <p class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                                                            {{ format_seconds_to_hms($task['total_seconds_on_day']) }}</p>
                                                    </div>
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
                                                    <div class="col-span-1 text-right text-gray-400 dark:text-gray-500">
                                                        <i class="details-icon fas fa-chevron-down fa-lg"></i>
                                                    </div>
                                                </div>
                                                {{-- 詳細ログ --}}
                                                <div class="details-body bg-gray-50 dark:bg-gray-800/50">
                                                    <div class="py-3 px-6 lg:px-8 border-t border-gray-200 dark:border-gray-700/50">
                                                        <table class="min-w-full text-xs">
                                                            <thead class="border-b-2 border-gray-300 dark:border-gray-600">
                                                                <tr>
                                                                    <th class="py-2 px-3 text-left font-semibold">ログID</th>
                                                                    <th class="py-2 px-3 text-left font-semibold">実績担当者</th>
                                                                    <th class="py-2 px-3 text-left font-semibold">開始</th>
                                                                    <th class="py-2 px-3 text-left font-semibold">終了</th>
                                                                    <th class="py-2 px-3 text-right font-semibold">作業時間</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($task['logs'] as $log)
                                                                    <tr class="border-b border-gray-200 dark:border-gray-600/50 last:border-b-0">
                                                                        <td class="py-2 px-3">{{ $log->id }}</td>
                                                                        <td class="py-2 px-3">
                                                                            {{ $log->user->name }}
                                                                            @if($log->is_manually_edited)
                                                                                <i class="fas fa-pencil-alt text-xs text-orange-500 ml-1" title="手動修正済み"></i>
                                                                            @endif
                                                                        </td>
                                                                        <td class="py-2 px-3">{{ $log->start_time_formatted }}</td>
                                                                        <td class="py-2 px-3">{{ $log->end_time_formatted }}</td>
                                                                        <td class="py-2 px-3 font-mono text-right">
                                                                            {{ format_seconds_to_hms($log->effective_duration) }}
                                                                        </td>
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
                            @endforeach
                        </div>
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
            // ▼▼▼【変更】3階層の開閉を制御するJavaScript ▼▼▼

            // 日付ごとの開閉
            document.querySelectorAll('.date-summary-header').forEach(header => {
                header.addEventListener('click', (event) => {
                    const body = header.nextElementSibling;
                    const icon = header.querySelector('.details-icon');
                    toggleAccordion(body, icon);
                });
            });

            // 案件ごとの開閉
            document.querySelectorAll('.project-summary-header').forEach(header => {
                header.addEventListener('click', (event) => {
                    event.stopPropagation(); // 親（日付）のクリックイベントを発火させない
                    const body = header.nextElementSibling;
                    const icon = header.querySelector('.details-icon');
                    toggleAccordion(body, icon);
                });
            });

            // 工程ごとの開閉
            document.querySelectorAll('.task-block .summary-header').forEach(header => {
                header.addEventListener('click', (event) => {
                    event.stopPropagation(); // 親（案件、日付）のクリックイベントを発火させない
                    const block = header.closest('.task-block');
                    const body = block.querySelector('.details-body');
                    const icon = header.querySelector('.details-icon');
                    toggleAccordion(body, icon);
                });
            });

            // 開閉を制御する共通関数
            function toggleAccordion(body, icon) {
                if (body && body.classList.contains('details-body')) {
                    body.classList.toggle('is-open');
                }
                if (icon) {
                    icon.classList.toggle('is-rotated');
                }
            }
        });
    </script>
@endpush

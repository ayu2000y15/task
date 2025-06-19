{{-- 日付ナビゲーション (変更なし) --}}
<div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-4">
    <form action="{{ route('work-records.index') }}" method="GET"
        class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <input type="hidden" name="view" value="timeline">
        <a href="{{ route('work-records.index', ['view' => 'timeline', 'date' => $currentDate->copy()->subDay()->format('Y-m-d')]) }}"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
            <i class="fas fa-chevron-left"></i> 前日
        </a>
        <input type="date" name="date" value="{{ $currentDate->format('Y-m-d') }}" onchange="this.form.submit()"
            class="border-gray-300 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
        <a href="{{ route('work-records.index', ['view' => 'timeline', 'date' => $currentDate->copy()->addDay()->format('Y-m-d')]) }}"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
            翌日 <i class="fas fa-chevron-right"></i>
        </a>
    </form>
</div>

{{-- タイムライン本体 --}}
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
    {{-- ヘッダー (変更なし) --}}
    <div class="p-6 border-b dark:border-gray-700">
        @php
            $dayOfWeekMap = ['日', '月', '火', '水', '木', '金', '土'];
        @endphp
        <h3 class="text-lg font-semibold">
            <span class="font-mono">{{ $currentDate->format('Y/m/d') }}
                ({{ $dayOfWeekMap[$currentDate->dayOfWeek] }})</span> の合計作業時間: <span
                class="text-blue-600 dark:text-blue-400 font-mono">{{ gmdate('H:i:s', $totalSeconds) }}</span>
        </h3>
    </div>

    {{-- ▼▼▼【ここから変更】タイムラインのループ構造をレスポンシブ対応に修正 ▼▼▼ --}}
    <div class="p-4 md:p-6">
        @forelse ($timelineItems as $item)
            <div class="md:flex">
                <div class="hidden md:block w-28 text-right pr-4 flex-shrink-0">
                    <p class="font-mono text-sm text-gray-800 dark:text-gray-200 pt-3">{{ $item->timestamp->format('H:i') }}
                    </p>
                </div>

                <div class="relative px-4">
                    <div class="absolute top-0 left-1/2 w-0.5 h-full bg-gray-200 dark:bg-gray-700 -translate-x-1/2"></div>
                    <div
                        class="relative z-10 top-3 left-1/2 w-5 h-5 bg-white dark:bg-gray-800 rounded-full border-2 flex items-center justify-center -translate-x-1/2">
                        @if($item->type === 'work_log') <i class="fas fa-briefcase text-xs text-blue-500"></i> @else <i
                        class="fas fa-user-clock text-xs text-green-500"></i> @endif
                    </div>
                </div>

                <div class="flex-grow pb-10">
                    <p class="md:hidden font-mono text-sm text-gray-800 dark:text-gray-200 mb-2">
                        {{ $item->timestamp->format('H:i') }}</p>

                    @if ($item->type === 'work_log')
                        @include('work-records.partials.timeline-item-work', ['log' => $item->model])
                    @else
                        @include('work-records.partials.timeline-item-attendance', ['log' => $item->model])
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">表示する記録がありません。</p>
            </div>
        @endforelse
    </div>
    {{-- ▲▲▲【変更ここまで】▲▲▲ --}}
</div>
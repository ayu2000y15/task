{{-- ▼▼▼【ここから変更】月ナビゲーションフィルター ▼▼▼ --}}
<div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-4">
    <form action="{{ route('work-records.index') }}" method="GET"
        class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <input type="hidden" name="view" value="list">

        {{-- 前の月へのリンク --}}
        <a href="{{ route('work-records.index', ['view' => 'list', 'month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 font-semibold">
            <i class="fas fa-chevron-left"></i> 前の月
        </a>

        {{-- 月選択フォーム --}}
        <input type="month" name="month" value="{{ $currentMonth->format('Y-m') }}" onchange="this.form.submit()"
            class="border-gray-300 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm text-lg font-semibold">

        {{-- 次の月へのリンク --}}
        <a href="{{ route('work-records.index', ['view' => 'list', 'month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 font-semibold">
            次の月 <i class="fas fa-chevron-right"></i>
        </a>
    </form>
</div>
{{-- ▲▲▲【変更ここまで】▲▲▲ --}}


{{-- ▼▼▼【これ以降のテーブル部分は変更ありません】▼▼▼ --}}
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            {{-- thead は変更なし --}}
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="w-10"></th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        日付</th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        出勤</th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        退勤</th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        休憩(合計)</th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        中抜け(合計)</th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        作業(合計)</th>
                </tr>
            </thead>
            {{-- tbody のループ処理も変更なし --}}
            @php
                $dayOfWeekMap = ['日', '月', '火', '水', '木', '金', '土'];
            @endphp
            @forelse ($dailySummaries as $day)
                <tbody x-data="{ open: false }" class="divide-y divide-gray-200 dark:divide-gray-600">
                    <tr @click="open = !open" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-4 text-center text-gray-400">
                            <i class="fas fa-fw" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $day->date->format('Y/m/d') }} ({{ $dayOfWeekMap[$day->date->dayOfWeek] }})</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                            {{ optional($day->clockInTime)->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                            {{ optional($day->clockOutTime)->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                            {{ $day->totalBreakSeconds > 0 ? gmdate('H:i:s', $day->totalBreakSeconds) : '-' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                            {{ $day->totalAwaySeconds > 0 ? gmdate('H:i:s', $day->totalAwaySeconds) : '-' }}</td>
                        <td
                            class="px-4 py-4 whitespace-nowrap text-sm font-mono font-semibold text-blue-600 dark:text-blue-400">
                            {{ $day->totalWorkSeconds > 0 ? gmdate('H:i:s', $day->totalWorkSeconds) : '-' }}</td>
                    </tr>
                    <tr x-show="open" x-transition>
                        <td colspan="7" class="p-0">
                            <div class="p-4 bg-gray-100 dark:bg-gray-800/50">
                                <div class="space-y-4">
                                    @foreach ($day->details as $item)
                                        <div class="flex items-start">
                                            <p class="w-20 font-mono text-sm text-gray-600 dark:text-gray-400">
                                                {{ $item->timestamp->format('H:i') }}</p>
                                            <div class="flex-1">
                                                @if ($item->type === 'work_log')
                                                    @include('work-records.partials.timeline-item-work', ['log' => $item->model])
                                                @else
                                                    @include('work-records.partials.timeline-item-attendance', ['log' => $item->model])
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            表示する記録がありません。</td>
                    </tr>
                </tbody>
            @endforelse
        </table>
    </div>
</div>

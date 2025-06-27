<tr class="details-row"
    id="details-{{ $date->format('Y-m-d') }}{{ is_null($sessionIndex) ? '' : '-' . $sessionIndex }}">
    <td colspan="10" class="p-0">
        <div class="bg-gray-100 dark:bg-gray-900/50 p-4 border-l-4 border-blue-400">
            {{-- 作業ログ詳細 --}}
            <h6 class="font-semibold text-sm mb-2">
                作業ログ詳細
                @if($startTime)
                    <span class="font-normal text-gray-600 dark:text-gray-400">
                        ({{ $startTime->format('H:i') }} - {{ optional($endTime)->format('H:i') ?? '現在' }})
                    </span>
                @endif
            </h6>
            <table class="min-w-full text-sm">
                <thead class="border-b-2 border-gray-300 dark:border-gray-600">
                    <tr>
                        <th class="py-2 px-3 text-left">案件</th>
                        <th class="py-2 px-3 text-left">キャラクター</th>
                        <th class="py-2 px-3 text-left">工程</th>
                        <th class="py-2 px-3 text-left">開始</th>
                        <th class="py-2 px-3 text-left">終了</th>
                        <th class="py-2 px-3 text-left">作業時間</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-b dark:border-gray-600/50">
                            <td class="py-2 px-3">{{ $log->task->project->title ?? '-' }}</td>
                            <td class="py-2 px-3">{{ optional($log->task->character)->name ?? '-' }}</td>
                            <td class="py-2 px-3">{{ $log->task->name ?? '-' }}</td>
                            <td class="py-2 px-3">{{ $log->start_time->format('H:i') }}</td>
                            <td class="py-2 px-3">{{ optional($log->end_time)->format('H:i') }}</td>
                            <td class="py-2 px-3 font-mono">{{ gmdate('H:i:s', $log->effective_duration) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 px-3 text-center text-gray-500">この時間帯の作業ログはありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- 休憩・中抜け詳細の表示エリア --}}
            @php
                $breakDetails = [];
                if (isset($manualBreaks)) { // 手動編集された休憩データ
                    $breakDetails = $manualBreaks->map(fn($b) => [
                        'type' => $b->type === 'break' ? '休憩' : '中抜け',
                        'start_time' => $b->start_time,
                        'end_time' => $b->end_time,
                        'duration' => $b->start_time->diffInSeconds($b->end_time)
                    ]);
                } elseif (isset($sessionBreaks)) { // 自動計算された休憩データ
                    $breakDetails = collect($sessionBreaks)->map(fn($b) => [
                        'type' => $b['type'],
                        'start_time' => $b['start_time'],
                        'end_time' => $b['end_time'],
                        'duration' => $b['duration']
                    ]);
                }
            @endphp

            @if($breakDetails->isNotEmpty())
                <h6 class="font-semibold text-sm my-2 pt-2 border-t border-gray-300 dark:border-gray-600">
                    休憩・中抜け詳細
                </h6>
                <table class="min-w-full text-sm">
                    <thead class="border-b-2 border-gray-300 dark:border-gray-600">
                        <tr>
                            <th class="py-2 px-3 text-left">種別</th>
                            <th class="py-2 px-3 text-left">開始</th>
                            <th class="py-2 px-3 text-left">終了</th>
                            <th class="py-2 px-3 text-left">時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakDetails as $break)
                            <tr class="border-b dark:border-gray-600/50">
                                <td class="py-2 px-3">{{ $break['type'] }}</td>
                                <td class="py-2 px-3">{{ $break['start_time']->format('H:i') }}</td>
                                <td class="py-2 px-3">{{ $break['end_time']->format('H:i') }}</td>
                                <td class="py-2 px-3 font-mono">{{ gmdate('H:i:s', $break['duration']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </td>
</tr>
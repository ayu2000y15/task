{{-- 勤務日 (ログから自動計算) の行 --}}
@foreach ($report['sessions'] as $index => $session)
    @php
        $hasLogs = $session['logs']->isNotEmpty();
        $dayHasAnyLogs = collect($report['sessions'])->some(fn($s) => $s['logs']->isNotEmpty());
        $holidayName = optional($report['public_holiday'])->name;
        if (isset($report['work_shift']) && in_array($report['work_shift']->type, ['full_day_off', 'am_off', 'pm_off'])) {
            // ユーザー設定の休日名があれば、それで祝日名を上書きする
            $holidayName = $report['work_shift']->name ?? $holidayName;
        }
    @endphp
    <tr class="{{ $rowClass }} @if($index > 0) !border-t-0 @else border-t-2 border-gray-400 dark:border-gray-500 @endif @if($hasLogs) summary-row hover:bg-gray-50 dark:hover:bg-gray-700/50 @endif"
        @if($hasLogs) data-details-target="details-{{ $date->format('Y-m-d') }}-{{$index}}" @endif>

        <td class="px-2 py-3 text-center">
            @if($hasLogs)
                <i class="fas fa-chevron-down details-icon text-gray-400"></i>
            @endif
        </td>

        <td class="px-2 py-3 whitespace-nowrap @if($index > 0) border-t-0 !py-1 @endif">
            @if($index === 0)
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})</div>
                        @if($holidayName)
                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $holidayName }}</div> @endif
                    </div>
                </div>
            @endif
        </td>

        <td class="px-2 py-3">@if($index === 0)<span title="自動計算"><i class="fas fa-magic text-blue-500"></i></span>@endif
        </td>
        <td class="px-2 py-3 font-mono text-sm">{{ $session['start_time']->format('H:i') }}</td>
        <td class="px-2 py-3 font-mono text-sm">{{ optional($session['end_time'])->format('H:i') ?? '未退勤' }}</td>
        <td class="px-2 py-3 font-mono text-sm">
            {{ $session['end_time'] ? gmdate('H:i:s', $session['detention_seconds']) : '-' }}</td>
        <td class="px-2 py-3 font-mono text-sm">{{ gmdate('H:i:s', $session['break_seconds']) }}</td>
        <td class="px-2 py-3 font-mono text-sm font-semibold">{{ gmdate('H:i:s', $session['actual_work_seconds']) }}</td>
        <td class="px-2 py-3 font-mono text-sm">
            @if(is_null($session['end_time']))
                <span class="text-xs font-semibold text-yellow-600 dark:text-yellow-400"
                    title="退勤打刻が行われていないため、給与が計算できません。勤怠編集を行ってください。">勤怠未完了</span>
            @else
                ¥{{ number_format($session['daily_salary']) }}
            @endif
        </td>
        <td class="px-2 py-3 text-center">
            @if($index === 0)
                <button
                    @click="openEditModal('{{ $date->format('Y-m-d') }}', '{{ $report['sessions'][0]['start_time']->format('H:i') }}', '{{ optional(collect($report['sessions'])->last()['end_time'])->format('H:i') }}', '{{ floor(collect($report['sessions'])->sum('break_seconds') / 60) }}', '')"
                    class="text-blue-500 hover:text-blue-700 text-xs">編集</button>
            @endif
        </td>
    </tr>
    @if($hasLogs)
        @include('admin.attendances.partials.details-row', [
            'date' => $date,
            'logs' => $session['logs'],
            'sessionIndex' => $index,
            'startTime' => $session['start_time'],
            'endTime' => $session['end_time']
        ])
    @endif
@endforeach
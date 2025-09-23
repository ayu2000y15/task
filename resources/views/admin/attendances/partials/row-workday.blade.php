{{-- 勤務日 (ログから自動計算) の行 --}}
@foreach ($report['sessions'] as $index => $session)
    @php
        $hasLogs = $session['logs']->isNotEmpty();
        $hasBreakDetails = !empty($session['break_details']);
        $hasDetails = $hasLogs || $hasBreakDetails;

        $dayHasAnyLogs = collect($report['sessions'])->some(fn($s) => $s['logs']->isNotEmpty());
        $holidayName = optional($report['public_holiday'])->name;
        if (isset($report['work_shift']) && in_array($report['work_shift']->type, ['full_day_off', 'am_off', 'pm_off'])) {
            $holidayName = $report['work_shift']->name ?? $holidayName;
        }
    @endphp
    <tr class="{{ $rowClass }} @if($index > 0) border-t-0 @endif @if($hasDetails) summary-row hover:bg-gray-50 dark:hover:bg-gray-700/50 @endif"
        @if($hasDetails) data-details-target="details-{{ $date->format('Y-m-d') }}-{{$index}}" @endif>

        <td class="px-2 py-3 text-center">
            @if($hasDetails)
                <i class="fas fa-chevron-down details-icon text-gray-400"></i>
            @endif
        </td>

        <td class="px-2 py-3 whitespace-nowrap @if($index > 0) border-t-0 !py-1 @endif">
            @if($index === 0)
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})
                            @if(!empty($report['location']))
                                <span class="text-xs ml-2">@if($report['location'] === 'remote') <i
                                    class="fas fa-home text-blue-500" title="在宅勤務"></i>
                                @elseif($report['location'] === 'office') <i class="fas fa-building text-green-500"
                                    title="出勤"></i> @else {{ $report['location'] }} @endif</span>
                            @endif
                        </div>
                        @if($holidayName)
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{ $holidayName }}</div>
                        @endif
                        @if(!empty($report['transportation']) && $report['transportation'] > 0)
                            <div class="text-xs text-gray-700 dark:text-gray-300"
                                title="{{ e($report['transportation_tooltip']) }}">交通費:
                                ¥{{ number_format($report['transportation'], 0) }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </td>
        <td class="px-2 py-3">
            @if($index === 0)
                <span title="自動計算"><i class="fas fa-magic text-blue-500"></i></span>
            @endif
        </td>
        <td class="px-2 py-3 font-mono text-sm">{{ $session['start_time']->format('H:i') }}
        </td>
        <td class="px-2 py-3 font-mono text-sm">{{ optional($session['end_time'])->format('H:i') ?? '未退勤' }}</td>
        <td class="px-2 py-3 font-mono text-sm">
            {{ $session['end_time'] ? format_seconds_to_hms($session['detention_seconds']) : '-' }}
        </td>
        <td class="px-2 py-3 font-mono text-sm">{{ format_seconds_to_hms($session['break_seconds']) }}</td>
        {{-- 支払対象時間 (拘束時間 - 休憩等) --}}
        <td class="px-2 py-3 font-mono text-sm font-bold text-blue-600 dark:text-blue-400">
            {{ format_seconds_to_hms($session['payable_work_seconds']) }}
        </td> {{-- 実働時間 (WorkLogの合計) --}} <td
            class="px-2 py-3 font-mono text-sm font-semibold text-green-600 dark:text-green-400">
            {{ format_seconds_to_hms($session['actual_work_seconds']) }}
        </td>
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
                    @click="openEditModal('{{ $date->format('Y-m-d') }}', '{{ $session['start_time']->format('H:i') }}', '{{ optional($session['end_time'])->format('H:i') }}', '[]', '')"
            class="text-blue-500 hover:text-blue-700 text-xs">編集</button> @endif
        </td>
    </tr>
    @if($hasDetails)
        @include('admin.attendances.partials.details-row', [
            'date' => $date,
            'logs' => $session['logs'],
            'sessionIndex' => $index,
            'startTime' => $session['start_time'],
            'endTime' => $session['end_time'],
            'sessionBreaks' => $session['break_details'] ?? [],
        ])
    @endif
@endforeach

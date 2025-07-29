{{-- resources/views/admin/attendances/partials/row-edited.blade.php --}}

{{-- 手動編集済みの行 --}}
@php
    $summary = $report['summary'];
    $hasLogs = $report['logs']->isNotEmpty();
    $holidayName = optional($report['public_holiday'])->name;
    if ($report['work_shift'] && in_array($report['work_shift']->type, ['full_day_off', 'am_off', 'pm_off'])) {
        $holidayName = $report['work_shift']->name;
    }
@endphp
<tr class="{{ $rowClass }} @if($hasLogs || $summary->breaks->isNotEmpty()) summary-row hover:bg-gray-50 dark:hover:bg-gray-700/50 @endif"
    @if($hasLogs || $summary->breaks->isNotEmpty()) data-details-target="details-{{ $date->format('Y-m-d') }}" @endif>
    <td class="px-2 py-3 text-center">
        @if($hasLogs || $summary->breaks->isNotEmpty())
            <i class="fas fa-chevron-down details-icon text-gray-400"></i>
        @endif
    </td>
    <td class="px-2 py-3 whitespace-nowrap">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})</div>
                @if($holidayName)
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ $holidayName }}</div>
                @endif
            </div>
        </div>
    </td>

    <td class="px-2 py-3"><span title="手動編集"><i class="fas fa-pencil-alt text-gray-500"></i></span></td>
    <td class="px-2 py-3 font-mono text-sm">{{ optional($summary->start_time)->format('H:i') }}</td>
    <td class="px-2 py-3 font-mono text-sm">
        @if($summary->end_time)
            @if(!$summary->end_time->isSameDay($date))
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">(翌日)</span>
            @endif
            {{ $summary->end_time->format('H:i') }}
        @endif
    </td>
    <td class="px-2 py-3 font-mono text-sm">
        {{ $summary->end_time ? format_seconds_to_hms($summary->detention_seconds) : '-' }}
    </td>
    <td class="px-2 py-3 font-mono text-sm">{{ format_seconds_to_hms($summary->break_seconds) }}</td>
    {{-- 支払対象時間 (拘束時間 - 休憩等) --}}
    <td class="px-2 py-3 font-mono text-sm font-bold text-blue-600 dark:text-blue-400">
        {{ format_seconds_to_hms($summary->actual_work_seconds) }}
    </td>
    {{-- 実働時間 (WorkLogの合計) --}}
    <td class="px-2 py-3 font-mono text-sm font-semibold text-green-600 dark:text-green-400">
        {{ format_seconds_to_hms($report['worklog_total_seconds']) }}
    </td>
    <td class="px-2 py-3 font-mono text-sm">¥{{ number_format($summary->daily_salary) }}</td>
    <td class="px-2 py-3 text-center">
        @php
            $breaksForModal = $summary->breaks->map(fn($b) => [
                'type' => $b->type,
                'start_time' => $b->start_time->format('H:i'),
                'end_time' => $b->end_time->format('H:i'),
            ]);
        @endphp
        <button
            @click="openEditModal('{{ $date->format('Y-m-d') }}', '{{ optional($summary->start_time)->format('H:i') }}', '{{ optional($summary->end_time)->format('H:i') }}', '{{ json_encode($breaksForModal) }}', '{{ e($summary->note) }}')"
            class="text-blue-500 hover:text-blue-700 text-xs">編集</button>
    </td>
</tr>
@if($hasLogs || $summary->breaks->isNotEmpty())
    @include('admin.attendances.partials.details-row', [
        'date' => $date,
        'logs' => $report['logs'],
        'sessionIndex' => null,
        'startTime' => $summary->start_time,
        'endTime' => $summary->end_time,
        'manualBreaks' => $summary->breaks,
    ])
@endif

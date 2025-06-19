{{-- 手動編集済みの行 --}}
@php
    $summary = $report['summary'];
    $hasLogs = $report['logs']->isNotEmpty();
    $holidayName = optional($report['public_holiday'])->name ?? optional($report['user_holiday'])->name;
@endphp
<tr class="{{ $rowClass }} @if($hasLogs) summary-row hover:bg-gray-50 dark:hover:bg-gray-700/50 @endif" @if($hasLogs)
data-details-target="details-{{ $date->format('Y-m-d') }}" @endif>

    <td class="px-2 py-3 text-center">
        @if($hasLogs)
            <i class="fas fa-chevron-down details-icon text-gray-400"></i>
        @endif
    </td>

    <td class="px-2 py-3 whitespace-nowrap">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})</div>
                @if($holidayName)
                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $holidayName }}</div> @endif
            </div>
        </div>
    </td>

    <td class="px-2 py-3"><span title="手動編集"><i class="fas fa-pencil-alt text-gray-500"></i></span></td>
    <td class="px-2 py-3 font-mono text-sm">{{ optional($summary->start_time)->format('H:i') }}</td>
    <td class="px-2 py-3 font-mono text-sm">{{ optional($summary->end_time)->format('H:i') }}</td>
    <td class="px-2 py-3 font-mono text-sm">{{ gmdate('H:i:s', $summary->break_seconds) }}</td>
    <td class="px-2 py-3 font-mono text-sm font-semibold">{{ gmdate('H:i:s', $summary->actual_work_seconds) }}</td>
    <td class="px-2 py-3 font-mono text-sm">¥{{ number_format($summary->daily_salary) }}</td>
    <td class="px-2 py-3 text-center">
        <button
            @click="openEditModal('{{ $date->format('Y-m-d') }}', '{{ optional($summary->start_time)->format('H:i') }}', '{{ optional($summary->end_time)->format('H:i') }}', '{{ floor($summary->break_seconds / 60) }}', '{{ e($summary->note) }}')"
            class="text-blue-500 hover:text-blue-700 text-xs">編集</button>
    </td>
</tr>
@if($hasLogs)
    @include('admin.attendances.partials.details-row', [
        'date' => $date,
        'logs' => $report['logs'],
        'sessionIndex' => null,
        'startTime' => $summary->start_time,
        'endTime' => $summary->end_time
    ])
@endif
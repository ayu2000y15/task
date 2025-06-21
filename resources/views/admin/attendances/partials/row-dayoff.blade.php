{{-- 休日の行 --}}
@php
    $workShift = $report['work_shift']; // 変数名を変更
    $publicHoliday = $report['public_holiday'];
    $holidayName = optional($publicHoliday)->name;

    // ユーザー休日設定があれば、それが優先される
    if ($workShift && in_array($workShift->type, ['full_day_off', 'am_off', 'pm_off'])) {
        $holidayName = $workShift->name;
    }
@endphp
<tr class="{{ $rowClass }}">
    <td></td>
    <td class="px-2 py-3 whitespace-nowrap">
        <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})</div>
    </td>
    <td class="px-2 py-3"><i class="fas fa-bed text-gray-400"></i></td>
    <td colspan="6" class="px-2 py-3 text-sm text-gray-500">
        {{-- ▼▼▼【ここから修正】'work_shift'を参照するように変更 ▼▼▼ --}}
        @if ($workShift && in_array($workShift->type, ['full_day_off', 'am_off', 'pm_off']))
            @php
                $typeLabels = ['full_day_off' => '全休', 'am_off' => '午前休', 'pm_off' => '午後休'];
            @endphp
            <span
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/70 dark:text-yellow-200">
                {{ $typeLabels[$workShift->type] ?? '休日' }}
            </span>
            <span class="ml-2">{{ $workShift->name }}</span>
        @elseif ($publicHoliday)
            {{ $publicHoliday->name }}
        @else
            -
        @endif
        {{-- ▲▲▲【ここまで修正】▲▲▲ --}}
    </td>
    <td class="px-2 py-3 text-center">
        <button @click="openEditModal('{{ $date->format('Y-m-d') }}', '', '', '0', '')"
            class="text-blue-500 hover:text-blue-700 text-xs">編集</button>
    </td>
</tr>
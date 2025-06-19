{{-- 休日の行 --}}
<tr class="{{ $rowClass }}">
    {{-- ▼▼▼【追加】レイアウト用の空セル ▼▼▼ --}}
    <td></td>

    <td class="px-2 py-3 whitespace-nowrap">
        <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})</div>
    </td>
    <td class="px-2 py-3"><i class="fas fa-bed text-gray-400"></i></td>
    <td colspan="5" class="px-2 py-3 text-sm text-gray-500">
        {{ optional($report['public_holiday'])->name ?? optional($report['user_holiday'])->name ?? '-' }}
    </td>
    <td class="px-2 py-3 text-center">
        <button @click="openEditModal('{{ $date->format('Y-m-d') }}', '', '', '0', '')"
            class="text-blue-500 hover:text-blue-700 text-xs">編集</button>
    </td>
</tr>
{{-- resources/views/admin/work-records/partials/monthly-attendance-summary-table.blade.php --}}
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b dark:border-gray-700">
                <th class="py-2 px-1 text-left font-medium text-gray-600 dark:text-gray-300">担当者</th>
                <th class="py-2 px-1 text-right font-medium text-gray-600 dark:text-gray-300">総勤務時間</th>
                <th class="py-2 px-1 text-right font-medium text-gray-600 dark:text-gray-300">総休憩時間</th>
                <th class="py-2 px-1 text-right font-medium text-gray-600 dark:text-gray-300">実働時間</th>
                <th class="py-2 px-1 text-right font-medium text-gray-600 dark:text-gray-300">適用時給</th>
                <th class="py-2 px-1 text-right font-medium text-gray-600 dark:text-gray-300">給与合計</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['by_user'] as $userSummary)
                <tr class="border-b dark:border-gray-700">
                    <td class="py-2 px-2 whitespace-nowrap">
                        <a href="{{ route('admin.attendances.show', $userSummary['user']) }}"
                            class="text-blue-600 hover:underline dark:text-blue-400">
                            {{ $userSummary['user']->name }}
                        </a>
                    </td>
                    <td class="py-2 px-1 text-right whitespace-nowrap">
                        {{ format_seconds_to_hms($userSummary['total_detention_seconds']) }}
                    </td>
                    <td class="py-2 px-1 text-right whitespace-nowrap">
                        {{ format_seconds_to_hms($userSummary['total_break_seconds']) }}
                    </td>
                    <td class="py-2 px-1 text-right whitespace-nowrap font-semibold">
                        {{ format_seconds_to_hms($userSummary['total_actual_work_seconds']) }}
                    </td>
                    <td class="py-2 px-1 text-right whitespace-nowrap">
                        @if($userSummary['rate'] > 0)
                            ¥{{ number_format($userSummary['rate']) }}
                        @else
                            <span class="text-xs italic">未登録</span>
                        @endif
                    </td>
                    <td class="py-2 px-1 text-right whitespace-nowrap">
                        ¥{{ number_format($userSummary['total_salary'], 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">勤怠実績がありません。</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 dark:border-gray-500 font-bold">
                <td class="py-2 px-1">全員合計</td>
                <td class="py-2 px-1 text-right whitespace-nowrap">
                    {{ format_seconds_to_hms($summary['totals']['detention_seconds']) }}
                </td>
                <td class="py-2 px-1 text-right whitespace-nowrap">
                    {{ format_seconds_to_hms($summary['totals']['break_seconds']) }}
                </td>
                <td class="py-2 px-1 text-right whitespace-nowrap">
                    {{ format_seconds_to_hms($summary['totals']['actual_work_seconds']) }}
                </td>
                <td class="py-2 px-1"></td>
                <td class="py-2 px-1 text-right whitespace-nowrap">
                    ¥{{ number_format($summary['totals']['salary'], 0) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
{{-- resources/views/admin/work-records/partials/summary-table.blade.php --}}
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b dark:border-gray-700">
                <th class="py-2 px-1 text-left font-medium text-gray-600 dark:text-gray-300">担当者</th>
                <th class="py-2 px-1 text-right font-medium text-gray-600 dark:text-gray-300">合計時間</th>
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
                        @php
                            $sec = $userSummary['total_seconds'];
                            echo sprintf('%d:%02d:%02d', floor($sec / 3600), floor(($sec / 60) % 60), $sec % 60);
                        @endphp
                    </td>
                    <td class="py-2 px-1 text-right whitespace-nowrap">
                        ¥{{ number_format($userSummary['total_salary'], 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-4 text-center text-gray-500">実績がありません。</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 dark:border-gray-500 font-bold">
                <td class="py-2 px-1">全員合計</td>
                <td class="py-2 px-1 text-right whitespace-nowrap">
                    @php
                        $sec = $summary['total_seconds'];
                        echo sprintf('%d:%02d:%02d', floor($sec / 3600), floor(($sec / 60) % 60), $sec % 60);
                    @endphp
                </td>
                <td class="py-2 px-1 text-right whitespace-nowrap">
                    ¥{{ number_format($summary['total_salary'], 0) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
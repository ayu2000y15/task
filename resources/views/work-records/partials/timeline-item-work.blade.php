{{-- resources/views/work-records/partials/timeline-item-work.blade.php --}}

<div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
    <p class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
        <i class="fas fa-tools fa-fw mr-1 text-gray-400"></i> 作業ログ
    </p>

    {{-- ▼▼▼【ここから変更】Null安全演算子(?->)を使って安全にプロパティを呼び出す ▼▼▼ --}}
    <div class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">

        {{-- 案件 --}}
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">案件:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->task?->project?->title ?? '（案件情報なし）' }}</span>

        {{-- キャラクター --}}
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">キャラクター:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->task?->character?->name ?? 'なし' }}</span>

        {{-- 工程 --}}
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">工程:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->task?->name ?? '（工程情報なし）' }}</span>

        {{-- 時間 --}}
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">時間:</strong>
        <span class="text-gray-700 dark:text-gray-200">
            <span class="font-mono">{{ gmdate('H:i:s', $log->effective_duration) }}</span>
            <span class="text-xs">({{ $log->start_time->format('H:i') }} -
                {{ optional($log->end_time)->format('H:i') ?? '継続中' }})</span>
        </span>

        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">メモ:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->memo ?? 'なし' }}</span>

    </div>
    {{-- ▲▲▲【変更ここまで】▲▲▲ --}}
</div>
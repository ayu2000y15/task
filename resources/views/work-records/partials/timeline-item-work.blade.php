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

    {{-- 手修正申請モーダルボタン --}}
    <div class="mt-4 text-right">
        <button type="button"
            class="inline-block px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600"
            onclick="openManualEditModal({{ $log->id }})">
            <i class="fas fa-pen"></i> 時間を修正
        </button>
    </div>

    {{-- 手修正申請モーダル --}}
    <div id="manualEditModal-{{ $log->id }}" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-lg p-6 relative">
            <button class="absolute top-2 right-2 text-gray-400 hover:text-gray-700" onclick="closeManualEditModal({{ $log->id }})">
                <i class="fas fa-times fa-lg"></i>
            </button>
            <h2 class="text-lg font-bold mb-4">作業ログ手修正申請</h2>
            <form method="POST" action="{{ route('work-logs.manual-edit-request', $log->id) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">開始時刻</label>
                    <input type="datetime-local" name="start_time" value="{{ old('start_time', $log->start_time ? $log->start_time->format('Y-m-d\\TH:i') : '') }}" class="form-input w-full" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">終了時刻</label>
                    <input type="datetime-local" name="end_time" value="{{ old('end_time', $log->end_time ? $log->end_time->format('Y-m-d\\TH:i') : '') }}" class="form-input w-full" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">メモ</label>
                    <textarea name="memo" class="form-textarea w-full" rows="2">{{ old('memo', $log->memo) }}</textarea>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">申請する</button>
            </form>
        </div>
    </div>

    <script>
        function openManualEditModal(logId) {
            const modal = document.getElementById('manualEditModal-' + logId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeManualEditModal(logId) {
            const modal = document.getElementById('manualEditModal-' + logId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</div>
{{-- resources/views/work-records/partials/timeline-item-work.blade.php --}}

<div x-data="{
        showModal: false,
        logId: {{ $log->id }},
        taskName: `{{ $log->task?->name ?? '（工程情報なし）' }}`,
        originalStartTimeFormatted: '{{ $log->start_time ? $log->start_time->format('n/j H:i') : '---' }}',
        originalEndTimeFormatted: '{{ $log->end_time ? $log->end_time->format('n/j H:i') : '---' }}',

        // モーダル編集用のデータ
        startTime: '{{ $log->display_start_time ? $log->display_start_time->format('H:i') : '' }}',
        endTime: '{{ $log->display_end_time ? $log->display_end_time->format('H:i') : '' }}',
        // 表示用データ
        isManuallyEdited: {{ $log->is_manually_edited ? 'true' : 'false' }},
        durationFormatted: '{{ $log->effective_duration > 0 ? gmdate('H:i:s', $log->effective_duration) : '00:00:00' }}',
        startTimeFormatted: '{{ optional($log->display_start_time)->format('H:i') ?? '?' }}',
        endTimeFormatted: '{{ optional($log->display_end_time)->format('H:i') ?? '継続中' }}',
        memo: `{{ $log->memo ?? 'なし' }}`,
        // 通信状態
        errorMessage: '',
        isLoading: false,

        // JS関数部分は変更なし
        handleSuccess(logData) {
            this.isManuallyEdited = logData.is_manually_edited;
            this.durationFormatted = logData.effective_duration_formatted;
            this.startTimeFormatted = logData.display_start_time_formatted || '?';
            this.endTimeFormatted = logData.display_end_time_formatted || '継続中';
            this.memo = logData.memo || 'なし';
            if (logData.display_start_time) {
                this.startTime = logData.display_start_time.substring(11, 16);
            }
            if (logData.display_end_time) {
                this.endTime = logData.display_end_time.substring(11, 16);
            } else {
                this.endTime = '';
            }
        },
        updateTime() {
            this.isLoading = true;
            this.errorMessage = '';
            fetch(`{{ route('work-records.update-time', $log->id) }}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ start_time: this.startTime, end_time: this.endTime })
            })
            .then(res => res.ok ? res.json() : res.json().then(err => Promise.reject(err)))
            .then(data => {
                if (data.success) {
                    this.showModal = false;
                    this.handleSuccess(data.log);
                }
            })
            .catch(error => {
                this.errorMessage = error.message || '更新に失敗しました。';
                if (error.errors) { this.errorMessage = Object.values(error.errors).join(' '); }
            })
            .finally(() => { this.isLoading = false; });
        },
        resetTime() {
            if (!confirm('手動での修正をリセットして元の時間に戻しますか？')) return;
            this.isLoading = true;
            fetch(`{{ route('work-records.reset-time', $log->id) }}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.handleSuccess(data.log);
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => { this.isLoading = false; });
        }
    }" class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 relative">

    {{-- ヘッダー・本体表示部分は変更なし --}}
    <div class="flex justify-between items-start mb-3">
        <p class="font-semibold text-gray-800 dark:text-gray-100">
            <i class="fas fa-tools fa-fw mr-1 text-gray-400"></i>
            作業ログ
            <template x-if="isManuallyEdited">
                <i class="fas fa-pencil-alt fa-fw text-xs text-orange-500" title="手動で修正されています"></i>
            </template>
        </p>
        <div class="flex items-center gap-2 flex-shrink-0">
            <template x-if="isManuallyEdited">
                <button @click="resetTime" class="text-xs text-gray-500 hover:text-red-500 transition" title="修正をリセット">
                    <i class="fas fa-undo"></i> <span>リセット</span>
                </button>
            </template>
            <button @click="showModal = true" class="text-xs text-gray-500 hover:text-blue-500 transition"
                title="作業時間を修正">
                <i class="fas fa-edit"></i> <span>修正</span>
            </button>
        </div>
    </div>
    <div class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">ログID:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->id }}</span>
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">案件:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->task?->project?->title ?? '（案件情報なし）' }}</span>
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">キャラクター:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->task?->character?->name ?? 'なし' }}</span>
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">工程:</strong>
        <span class="text-gray-700 dark:text-gray-200">{{ $log->task?->name ?? '（工程情報なし）' }}</span>
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">時間:</strong>
        <span class="text-gray-700 dark:text-gray-200">
            <span class="font-mono" x-text="durationFormatted"></span>
            <span class="text-xs">
                (<span x-text="startTimeFormatted"></span> - <span x-text="endTimeFormatted"></span>)
            </span>
        </span>
        <strong class="font-medium text-gray-600 dark:text-gray-400 text-right">メモ:</strong>
        <span class="text-gray-700 dark:text-gray-200" x-text="memo"></span>
    </div>

    <div x-show="showModal" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div @click.away="showModal = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4"
            x-trap.noscroll="showModal">
            <div class="p-6">
                {{-- モーダルタイトル --}}
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                    作業時間の修正 (<span class="font-medium" x-text="taskName"></span>)
                </h3>

                {{-- 変更前の時間を表示 --}}
                <div class="mb-4 p-3 bg-gray-100 dark:bg-gray-700/60 rounded-md text-sm">
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-semibold">変更前の時間:</span>
                        <span class="font-mono ml-2" x-text="originalStartTimeFormatted"></span>
                        <span> - </span>
                        <span class="font-mono" x-text="originalEndTimeFormatted"></span>
                    </p>
                </div>

                {{-- 時刻入力フォーム --}}
                <div class="space-y-4">
                    <div>
                        <label :for="'start_time_' + logId"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始時刻</label>
                        <input type="time" :id="'start_time_' + logId" x-model="startTime"
                            class="mt-1 block w-full border-gray-300 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label :for="'end_time_' + logId"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了時刻</label>
                        <input type="time" :id="'end_time_' + logId" x-model="endTime"
                            class="mt-1 block w-full border-gray-300 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    </div>
                    <div x-show="errorMessage" class="text-sm text-red-600 dark:text-red-400" x-text="errorMessage"
                        x-transition></div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-3 flex justify-end gap-3 rounded-b-lg">
                <button type="button" @click="showModal = false"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">キャンセル</button>
                <button type="button" @click="updateTime()" :disabled="isLoading"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed flex items-center justify-center w-28">
                    <i x-show="isLoading" class="fas fa-spinner fa-spin"></i>
                    <span x-show="!isLoading">保存</span>
                </button>
            </div>
        </div>
    </div>
</div>
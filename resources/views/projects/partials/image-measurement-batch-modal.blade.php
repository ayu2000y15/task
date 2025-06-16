{{-- file: resources/views/projects/partials/image-measurement-batch-modal.blade.php --}}
<div x-data="{
    show: false,
    view: '',
    points: {},
    imageSrc: '',
    characterId: null,
    projectId: null,
    formData: {},
    isSubmitting: false,
    isInputMode: false, // ▼▼▼ 変更点 ▼▼▼ モードを管理する変数を追加

    // --- 動的スケール計算用のロジック ---
    originalWidth: 400,
    originalHeight: 600,
    scale: 1,
    offsetX: 0,
    offsetY: 0,

    updateScale() {
        if (!this.$refs.imageDisplay) return;
        const imageEl = this.$refs.imageDisplay;
        const containerEl = imageEl.parentElement;
        if (!containerEl || imageEl.offsetWidth === 0 || imageEl.offsetHeight === 0) return;

        let newScale;
        if (imageEl.offsetWidth / imageEl.offsetHeight > this.originalWidth / this.originalHeight) {
            newScale = imageEl.offsetHeight / this.originalHeight;
        } else {
            newScale = imageEl.offsetWidth / this.originalWidth;
        }
        this.scale = newScale;

        const scaledWidth = this.originalWidth * this.scale;
        const scaledHeight = this.originalHeight * this.scale;
        this.offsetX = (containerEl.offsetWidth - scaledWidth) / 2;
        this.offsetY = (containerEl.offsetHeight - scaledHeight) / 2;
    },

    handleOpen(event) {
        if (event.detail.name !== 'image-measurement-batch-modal') return;

        this.view = event.detail.view;
        this.points = event.detail.points;
        this.imageSrc = event.detail.imageSrc;
        this.characterId = event.detail.characterId;
        this.projectId = event.detail.projectId;
        this.isSubmitting = false;

        // ▼▼▼ 変更点 ▼▼▼ イベントから渡された値でモードを設定
        this.isInputMode = event.detail.imageInputMode;
        this.originalWidth = event.detail.originalWidth || 400;
        this.originalHeight = event.detail.originalHeight || 600;
        // ▲▲▲ 変更点 ▲▲▲

        let initialFormData = {};
        const currentMeasurements = event.detail.measurements;
        for (const key in this.points) {
            initialFormData[key] = {
                value: currentMeasurements[key]?.value || '',
                notes: currentMeasurements[key]?.notes || '',
                label: this.points[key].label
            };
        }
        this.formData = initialFormData;
        this.show = true;

        this.$nextTick(() => {
            this.updateScale();
        });
    },

    submitForm() {
        this.isSubmitting = true;
        const url = `/projects/${this.projectId}/characters/${this.characterId}/measurements/batch`;

        axios.post(url, { measurements: this.formData })
            .then(response => {
                if (response.data.success) {
                    this.$dispatch('measurements-batch-updated', {
                        characterId: this.characterId,
                        updatedMeasurements: response.data.updatedMeasurements,
                    });
                    this.show = false;
                }
            })
            .catch(error => console.error('Batch save error:', error))
            .finally(() => this.isSubmitting = false);
    }
}" x-show="show" x-on:open-modal.window="handleOpen($event)" x-on:keydown.escape.window="show = false"
    x-on:resize.window.debounce.200ms="updateScale()" style="display: none;"
    class="fixed inset-0 z-50 flex flex-col items-center justify-center" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">

    {{-- オーバーレイ --}}
    <div x-show="show" x-transition.opacity.duration.300ms @click="show = false" class="fixed inset-0 bg-black/75"
        aria-hidden="true"></div>

    {{-- 右上の閉じるボタン --}}
    <button x-show="show" x-transition @click="show = false"
        class="fixed top-0 right-0 m-4 p-2 z-50 rounded-full text-white hover:bg-white/20 transition-colors">
        <span class="sr-only">閉じる</span>
        <i class="fas fa-times fa-2x fa-fw"></i>
    </button>

    {{-- コンテンツ（画像と入力欄）--}}
    <div x-show="show" x-transition class="relative z-10 w-full h-full flex items-center justify-center p-4 sm:p-8">
        <form @submit.prevent="submitForm" class="flex flex-col items-center justify-center w-full h-full">
            {{-- 画像と入力欄のコンテナ --}}
            <div class="relative flex-grow w-full flex items-center justify-center">
                {{-- コンテナを追加して、この中で画像を中央揃えにする --}}
                <div class="relative w-full h-full flex items-center justify-center">
                    <img :src="imageSrc" x-ref="imageDisplay" @load="updateScale()" class="block object-contain"
                        :style="`max-width: 85vw; max-height: 80vh;`" alt="採寸用画像">

                    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
                        <template x-for="(point, key) in points" :key="key">
                            <div class="absolute pointer-events-auto"
                                :style="`left: ${offsetX + (point.x * scale)}px; top: ${offsetY + (point.y * scale)}px; transform: translate(-50%, 0);`">
                                <div class="flex flex-col items-center space-y-1">
                                    {{-- 入力モードの場合：入力欄を表示 --}}
                                    <template x-if="isInputMode">
                                        <input type="text" x-model="formData[key].value"
                                            class="w-12 h-6 text-sm p-1 rounded-md border-gray-300 dark:border-gray-600 bg-white/80 dark:bg-gray-800/80 focus:ring-indigo-500 focus:border-indigo-500 text-center"
                                            :placeholder="point.label" :title="point.label">
                                    </template>

                                    {{-- 表示モードの場合：ラベルを表示 --}}
                                    <template x-if="!isInputMode">
                                        <div class="px-2 py-0.5 rounded-md bg-white/80 dark:bg-gray-800/80 text-xs font-semibold text-gray-800 dark:text-gray-200 shadow select-none"
                                            :title="point.label">
                                            <span x-text="point.label"></span>
                                            <span>(<span x-text="formData[key]?.value || '--'"></span>)</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 下部のボタン --}}
            <div x-show="isInputMode" class="flex-shrink-0 pt-4">
                <x-secondary-button type="button" @click="show = false">キャンセル</x-secondary-button>
                <x-primary-button class="ml-3" x-bind:disabled="isSubmitting">
                    <span x-show="isSubmitting"><i class="fas fa-spinner fa-spin mr-2"></i>保存中...</span>
                    <span x-show="!isSubmitting">一括保存</span>
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
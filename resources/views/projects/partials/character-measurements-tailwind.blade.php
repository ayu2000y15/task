<div class="space-y-4" id="character-measurements-section-{{ $character->id }}" data-character-id="{{ $character->id }}" data-project-id="{{ $project->id }}">
    <div
        class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
        <i class="fas fa-info-circle mr-1"></i>
        採寸テンプレートを適用する場合は、まず採寸テンプレートを作成してください。<br>
        　採寸テンプレートを作成すると、採寸テンプレートの項目が自動的に採寸データに適用されます。<br>
    </div>

    {{-- ▼▼▼ キャラクター採寸備考セクション ▼▼▼ --}}
    <div x-data='{
            editing: false,
            notes: @json($character->measurement_notes ?? ""),
            originalNotes: @json($character->measurement_notes ?? ""),
            status: "",
            isSubmitting: false,
            characterId: {{ $character->id }},
            get displayableNotes() {
                if (!this.originalNotes || !this.originalNotes.trim()) {
                    return "<span class=\"text-gray-400\">備考はありません。</span>";
                }
                const div = document.createElement("div");
                div.textContent = this.originalNotes;
                return div.innerHTML.replace(/\r\n|\n\r|\r|\n/g, "<br>");
            },
            updateNotes() {
                if(this.isSubmitting) return;
                this.isSubmitting = true;
                this.status = "保存中...";
                const csrfToken = document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content");

                axios.patch(`/characters/${this.characterId}/measurement-notes`, {
                    measurement_notes: this.notes
                }, {
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json"
                    }
                })
                .then(response => {
                    this.status = "保存しました！";
                    this.originalNotes = this.notes;
                    this.editing = false;
                    setTimeout(() => this.status = "", 3000);
                })
                .catch(error => {
                    this.status = "エラーが発生しました。詳細はコンソールを確認してください。";
                    console.error("Error updating measurement notes:", error.response?.data?.message || error.message);
                })
                .finally(() => {
                    this.isSubmitting = false;
                });
            },
            cancelEdit() {
                this.notes = this.originalNotes;
                this.editing = false;
                this.status = "";
            }
        }' class="p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="flex justify-between items-center mb-2">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center">
                <i class="fas fa-sticky-note mr-2"></i>採寸に関する備考
            </h6>
            <button type="button" @click="editing = true" x-show="!editing" class="inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-500 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-600 hover:bg-gray-50 dark:hover:bg-gray-500">
                <i class="fas fa-edit mr-1"></i>編集
            </button>
        </div>
        {{-- 表示モード --}}
        <div x-show="!editing" class="prose prose-sm dark:prose-invert max-w-none">
            <p class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap" x-html="displayableNotes"></p>
        </div>
        {{-- 編集モード --}}
        <div x-show="editing" style="display: none;" x-collapse>
            <x-textarea-input x-model="notes" class="mt-1 block w-full text-sm leading-tight" rows="5"></x-textarea-input>
            <div class="flex justify-end items-center mt-2 space-x-3">
                <span x-text="status" class="text-xs text-gray-500 dark:text-gray-400 mr-auto transition-opacity" :class="{'opacity-0': !status}"></span>
                <x-secondary-button @click="cancelEdit()" type="button" class="text-xs">キャンセル</x-secondary-button>
                <x-primary-button @click="updateNotes()" type="button" class="text-xs" x-bind:disabled="isSubmitting">
                    <span x-show="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i>保存中</span>
                    <span x-show="!isSubmitting"><i class="fas fa-save mr-1"></i>保存する</span>
                </x-primary-button>
            </div>
        </div>
    </div>


    <div x-data="{
        imageInputMode: false,
        measurements: {},
        allPoints: {},
        // 3つのビューのスケールとオフセットをまとめて管理
        viewStates: {
            front: { scale: 1, offsetX: 0, offsetY: 0 },
            side:  { scale: 1, offsetX: 0, offsetY: 0 },
            back:  { scale: 1, offsetX: 0, offsetY: 0 }
        },
        originalWidth: 400,
        originalHeight: 600,

        init() {
            try {
                this.measurements = JSON.parse(this.$el.dataset.measurements);
                this.allPoints = JSON.parse(this.$el.dataset.points);
            } catch (e) {
                console.error('Failed to parse data attributes', e);
                this.measurements = {};
                this.allPoints = {};
            }

            window.addEventListener('measurements-batch-updated', event => {
                if (parseInt(event.detail.characterId) === parseInt(this.$el.dataset.characterId)) {
                    event.detail.updatedMeasurements.forEach(m => {
                        this.measurements[m.item] = m;
                    });
                }
            });

            // ResizeObserverをセットアップ
            const observer = new ResizeObserver(() => {
                // コンテナのサイズが変更されたら、すべてのビューのスケールを再計算
                ['front', 'side', 'back'].forEach(view => {
                    this.updateScale(view);
                });
            });

            // DOM要素が描画された後に、各画像の「コンテナ」を監視対象に追加
            this.$nextTick(() => {
                ['front', 'side', 'back'].forEach(view => {
                    const imageEl = this.$refs[`displayImage_${view}`];
                    if (imageEl && imageEl.parentElement) {
                        observer.observe(imageEl.parentElement);
                    }
                });
            });
        },

        getMeasurementValue(itemKey) {
            return this.measurements[itemKey]?.value || '--';
        },

        updateScale(view) {
            const imageEl = this.$refs[`displayImage_${view}`];
            if (!imageEl) return;
            const containerEl = imageEl.parentElement;

            if (imageEl.offsetWidth === 0 || imageEl.offsetHeight === 0) return;

            let newScale;
            if (imageEl.offsetWidth / imageEl.offsetHeight > this.originalWidth / this.originalHeight) {
                newScale = imageEl.offsetHeight / this.originalHeight;
            } else {
                newScale = imageEl.offsetWidth / this.originalWidth;
            }

            this.viewStates[view].scale = newScale;
            this.viewStates[view].offsetX = (containerEl.offsetWidth - (this.originalWidth * newScale)) / 2;
            this.viewStates[view].offsetY = (containerEl.offsetHeight - (this.originalHeight * newScale)) / 2;
        }
    }"
    x-init="init()"
    @resize.window.debounce.250ms="['front', 'side', 'back'].forEach(view => updateScale(view))"
    class="space-y-4"
    id="character-measurements-section-{{ $character->id }}"
    data-character-id="{{ $character->id }}"
    data-project-id="{{ $project->id }}"
    data-measurements='@json($character->measurements->keyBy('item'))'
    @php
        // メインビューに表示するための座標
        $gender_specific_points = [
            'female' => [
                'front' => ['身長' => ['label' => '身長', 'x' => 50, 'y' => -80],
                            '肩幅' => ['label' => '肩幅', 'x' => 50, 'y' => 65],
                            'AH' => ['label' => 'AH', 'x' => 340, 'y' => 125],
                            'B' => ['label' => 'B', 'x' => 200, 'y' => 125],
                            'UB' => ['label' => 'UB', 'x' => 200, 'y' => 195],
                            'W' => ['label' => 'W', 'x' => 200, 'y' => 260],
                            'H' => ['label' => 'H', 'x' => 200, 'y' => 325],
                            '太もも' => ['label' => '太もも', 'x' => 85, 'y' => 420],
                            'ひざ' => ['label' => 'ひざ', 'x' => 105, 'y' => 465],
                            'ふくらはぎ' => ['label' => 'ふくらはぎ', 'x' => 100, 'y' => 540],
                            '足首' => ['label' => '足首', 'x' => 100, 'y' => 605],
                            '足' => ['label' => '足', 'x' => 50, 'y' => 660],
                        ],
                'side' => [ 'カラー' => ['label' => 'カラー', 'x' => 285, 'y' => 60],
                            '二の腕' => ['label' => '二の腕', 'x' => 300, 'y' => 115],
                            '肘' => ['label' => '肘', 'x' => 270, 'y' => 185],
                            'ヨーク' => ['label' => 'ヨーク', 'x' => 310, 'y' => 270],
                            '手首' => ['label' => '手首', 'x' => 110, 'y' => 330],
                            'ひざ下' => ['label' => 'ひざ下', 'x' => 100, 'y' => 520],
                        ],
                'back' => [ '首の長さ' => ['label' => '首の長さ', 'x' => 90, 'y' => 55],
                            'BNL~BL' => ['label' => 'BNL~BL', 'x' => 10, 'y' => 80],
                            '背幅' => ['label' => '背幅', 'x' => 185, 'y' => 170],
                            '袖丈' => ['label' => '袖丈', 'x' => 55, 'y' => 180],
                            '着丈' => ['label' => '着丈', 'x' => 300, 'y' => 175],
                            '総丈' => ['label' => '総丈', 'x' => 360, 'y' => 140],
                            '股下' => ['label' => '股下', 'x' => 40, 'y' => 450],
                        ]
            ],
            'male' => [
                'front' => ['身長' => ['label' => '身長', 'x' => 50, 'y' => -10],
                            '背肩幅' => ['label' => '背肩幅', 'x' => 310, 'y' => 40],
                            '袖丈' => ['label' => '袖丈', 'x' => 80, 'y' => 150],
                            '二の腕' => ['label' => '二の腕', 'x' => 320, 'y' => 140],
                            'ウエスト' => ['label' => 'ウエスト', 'x' => 190, 'y' => 180],
                            '肘下周り' => ['label' => '肘下周り', 'x' => 315, 'y' => 220],
                            '手首' => ['label' => '手首', 'x' => 310, 'y' => 315],
                            'ヒップ' => ['label' => 'ヒップ', 'x' => 170, 'y' => 340],
                            '太もも' => ['label' => '太もも', 'x' => 285, 'y' => 420],
                            'ひざ周り' => ['label' => 'ひざ周り', 'x' => 100, 'y' => 420],
                            'ふくらはぎ' => ['label' => 'ふくらはぎ', 'x' => 80, 'y' => 500],
                            '足首' => ['label' => '足首', 'x' => 280, 'y' => 505],
                            '足' => ['label' => '足', 'x' => 50, 'y' => 600],
                        ],
                'side' => [ 'ネック' => ['label' => 'ネック', 'x' => 290, 'y' => 50],
                            'バスト' => ['label' => 'バスト', 'x' => 105, 'y' => 165],
                            '天突～股ぐり' => ['label' => '天突～股ぐり', 'x' => 95, 'y' => 260],
                            'ひざ下' => ['label' => 'ひざ下', 'x' => 130, 'y' => 450],
                        ],
                'back' => [ 'AH' => ['label' => 'AH', 'x' => 45, 'y' => 50],
                            '総丈' => ['label' => '総丈', 'x' => 360, 'y' => 90],
                            '背丈' => ['label' => '背丈', 'x' => 295, 'y' => 100],
                            '背幅' => ['label' => '背幅', 'x' => 30, 'y' => 170],
                            '股下' => ['label' => '股下', 'x' => 360, 'y' => 370],
                            'パンツ丈' => ['label' => 'パンツ丈', 'x' => 60, 'y' => 400],
                        ]
            ]
        ];

        // キャラクターの性別を取得（未設定なら 'female' をデフォルトにする）
        $gender = in_array($character->gender, ['male', 'female']) ? $character->gender : 'female';

        // 性別に基づいて使用する座標を決定
        $points = $gender_specific_points[$gender];
    @endphp
    data-points='@json($points)'>


    {{-- 画像入力セクション --}}
    <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-3 sm:p-4 bg-gray-50 dark:bg-gray-700/50 rounded-t-lg border-b dark:border-gray-600">
            <div class="flex items-center mb-2 sm:mb-0">
                <i class="fas fa-male mr-2 text-gray-600 dark:text-gray-300"></i>
                <h6 class="text-md font-semibold text-gray-800 dark:text-gray-100">画像から採寸登録</h6>
            </div>
            <button @click="imageInputMode = !imageInputMode" type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-500 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-600 hover:bg-gray-50 dark:hover:bg-gray-500">
                <span x-show="!imageInputMode" class="flex items-center"><i class="fas fa-edit mr-2"></i>画像入力を開始</span>
                <span x-show="imageInputMode"  class="flex items-center text-blue-600 dark:text-blue-400"><i class="fas fa-check-circle mr-2"></i>入力モード中 (完了)</span>
            </button>
        </div>

        <div class="p-4">
            @php
                $gender = $character->gender ?? 'female';
                $imagePaths = [
                    'front' => asset('storage/measurements/' . $gender . '_1.png'),
                    'side'  => asset('storage/measurements/' . $gender . '_2.png'),
                    'back'  => asset('storage/measurements/' . $gender . '_3.png'),
                ];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(['front' => '正面', 'side' => '側面', 'back' => '背面'] as $view => $viewName)
                <div>
                    <h6 class="text-center text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ $viewName }}</h6>
                    {{-- :class と x-on:click の条件を削除し、常にクリック可能にする --}}
                    <div class="relative border rounded-md bg-gray-50 dark:bg-gray-900 overflow-hidden h-96 flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                         x-on:click="$dispatch('open-modal', {
                            name: 'image-measurement-batch-modal',
                            view: '{{ $view }}',
                            points: allPoints['{{ $view }}'] || {},
                            imageSrc: '{{ $imagePaths[$view] }}',
                            characterId: {{ $character->id }},
                            projectId: {{ $project->id }},
                            measurements: measurements,
                            imageInputMode: imageInputMode, /* モーダルに現在のモードを渡す */
                            originalWidth: originalWidth,   /* モーダルに基準サイズを渡す */
                            originalHeight: originalHeight  /* モーダルに基準サイズを渡す */
                         })">
                    <img src="{{ $imagePaths[$view] }}"
                             :key="'{{ $view }}'"
                             x-ref="displayImage_{{ $view }}"
                             class="block object-contain max-w-full max-h-full" alt="{{ $viewName }}図">

                        <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
                            @if(isset($points[$view]))
                            <template x-for="(pointData, key) in allPoints['{{ $view }}']" :key="key">
                                <div class="absolute"
                                     :style="`left: ${viewStates['{{ $view }}'].offsetX + (pointData.x * viewStates['{{ $view }}'].scale)}px; top: ${viewStates['{{ $view }}'].offsetY + (pointData.y * viewStates['{{ $view }}'].scale)}px; transform: translate(-50%, 0);`">
                                     <div class="flex flex-col items-center space-y-0.5">
                                        <span class="text-xs font-bold fill-current text-gray-900 dark:text-gray-50 select-none"
                                              style="paint-order: stroke; stroke: rgba(255,255,255,0.8); stroke-width: 3px; stroke-linejoin: round;">
                                            (<span x-text="getMeasurementValue(key)"></span>)
                                        </span>
                                    </div>
                                </div>
                            </template>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-2 py-2 w-10"></th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        項目</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        数値</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        備考</th>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20">
                        操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 sortable-list" id="measurement-sortable-{{ $character->id }}">
                @forelse($character->measurements as $measurement)
                    <tr id="measurement-row-{{ $measurement->id }}" data-id="{{ $measurement->id }}">
                        <td class="px-2 py-1.5 whitespace-nowrap text-center text-gray-400 drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-item">
                            {{ $measurement->item }}
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-value" data-sort-value="{{ floatval($measurement->value) }}">
                            {{ $measurement->value }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight measurement-notes"
                            style="min-width: 150px;">
                            @php
                                $rawNotes = $measurement->notes ?? '';
                                $rawNotes = is_string($rawNotes) ? $rawNotes : (string) $rawNotes;
                                // 行ごとに前後の空白（半角／全角含む）を削除してから結合する
                                $lines = preg_split('/\r\n|\n|\r/', $rawNotes);
                                $cleanLines = array_map(function($ln) {
                                    // trim (半角スペース等) の後、全角スペースも除去
                                    $ln = trim($ln);
                                    $ln = preg_replace('/^[\x{3000}\s]+|[\x{3000}\s]+$/u', '', $ln);
                                    return $ln;
                                }, $lines ?: []);
                                $cleanNotes = implode("\n", $cleanLines);
                            @endphp
                            {!! $cleanNotes !== '' ? nl2br(e($cleanNotes)) : '-' !!}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1">
                                @can('updateMeasurements', $project) {{-- 適切な権限名に変更 --}}
                                    <button type="button"
                                        class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-measurement-btn"
                                        title="編集" data-id="{{ $measurement->id }}" data-item="{{ $measurement->item }}"
                                        data-value="{{ $measurement->value }}" data-notes="{{ $measurement->notes }}">
                                        <i class="fas fa-edit fa-sm"></i>
                                    </button>
                                @endcan
                                @can('deleteMeasurements', $project) {{-- 適切な権限名に変更 --}}
                                    <form
                                        action="{{ route('projects.characters.measurements.destroy', [$project, $character, $measurement]) }}"
                                        method="POST" class="delete-measurement-form"
                                        data-id="{{ $measurement->id }}" onsubmit="return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            title="削除">
                                            <i class="fas fa-trash fa-sm"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="no-measurement-data-row-{{ $character->id }}">
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">採寸データがありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- ★ 並び順保存ボタンを追加 --}}
    @if($character->measurements->isNotEmpty())
    <div class="flex justify-start">
        <button type="button" class="save-order-btn inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150"
                data-target-list="#measurement-sortable-{{ $character->id }}"
                data-url="{{ route('projects.characters.measurements.updateOrder', [$project, $character]) }}">
            <i class="fas fa-save mr-2"></i>並び順を保存
        </button>
    </div>
    @endif

    @can('manageMeasurements', $project) {{-- 適切な権限名に変更 --}}
        <div x-data="{ expanded: false }" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700" id="measurement-template-load-section-{{ $character->id }}">
            <div @click="expanded = !expanded" class="flex justify-between items-center cursor-pointer py-2">
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <i class="fas fa-chevron-right fa-fw mr-1 transition-transform duration-200" :class="{'rotate-90': expanded}"></i>
                    採寸テンプレートを適用
                </h6>
            </div>
            <div x-show="expanded" x-collapse class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-end">
                    <div class="sm:col-span-2">
                        <x-input-label for="measurement_template_select-{{ $character->id }}" value="テンプレートを選択" />
                        <select id="measurement_template_select-{{ $character->id }}" class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200">
                            <option value="">テンプレートを選択...</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="apply-measurement-template-btn-{{ $character->id }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-600 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                            <i class="fas fa-check"></i> <span class="ml-2">適用する</span>
                        </button>
                    </div>
                </div>
                <div id="apply-template-status-{{ $character->id }}" class="text-xs mt-2"></div>
            </div>
        </div>

        <div x-data="{ expanded: false }" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700" id="measurement-template-save-section-{{ $character->id }}">
            <div @click="expanded = !expanded" class="flex justify-between items-center cursor-pointer py-2">
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                     <i class="fas fa-chevron-right fa-fw mr-1 transition-transform duration-200" :class="{'rotate-90': expanded}"></i>
                    現在の採寸項目をテンプレートとして保存
                </h6>
            </div>
            <div x-show="expanded" x-collapse class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-end">
                    <div class="sm:col-span-2">
                        <x-input-label for="measurement_template_name_input-{{ $character->id }}" value="テンプレート名" :required="true" />
                        <x-text-input type="text" id="measurement_template_name_input-{{ $character->id }}"
                            class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                             />
                    </div>
                    <div>
                        <button type="button" id="save-measurement-template-btn-{{ $character->id }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-600 active:bg-purple-700 focus:outline-none focus:border-purple-700 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                            <i class="fas fa-save"></i> <span class="ml-2">この内容で保存</span>
                        </button>
                    </div>
                </div>
                 <div id="save-template-status-{{ $character->id }}" class="text-xs mt-2"></div>
            </div>
        </div>

        {{-- 既存の採寸データ追加フォーム --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2"
                id="measurement-form-title-{{ $character->id }}">採寸データを追加</h6>
            <form id="measurement-form-{{ $character->id }}"
                action="{{ route('projects.characters.measurements.store', [$project, $character]) }}" method="POST"
                data-store-url="{{ route('projects.characters.measurements.store', [$project, $character]) }}"
                data-character-id="{{ $character->id }}"
                class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 items-start">
                @csrf
                <input type="hidden" name="_method" id="measurement-form-method-{{ $character->id }}" value="POST">
                <input type="hidden" name="measurement_id" id="measurement-form-id-{{ $character->id }}" value="">

                <div>
                    <x-input-label for="measurement_item_input-{{ $character->id }}" value="項目" :required="true" />
                    <x-text-input type="text" name="item" id="measurement_item_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required />
                </div>
                <div>
                    <x-input-label for="measurement_value_input-{{ $character->id }}" value="数値" :required="true" />
                    <x-text-input type="text" name="value" id="measurement_value_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="measurement_notes_input-{{ $character->id }}" value="備考" />
                    <x-textarea-input name="notes" id="measurement_notes_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 leading-tight"
                        rows="2"></x-textarea-input>
                </div>
                <div class="sm:col-span-2 flex justify-end items-center space-x-2">
                    <x-secondary-button type="button" id="measurement-form-cancel-btn-{{ $character->id }}"
                        style="display: none;">
                        キャンセル
                    </x-secondary-button>
                    <button type="submit" id="measurement-form-submit-btn-{{ $character->id }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                        <i class="fas fa-plus"></i> <span class="ml-2"
                            id="measurement-form-submit-btn-text-{{ $character->id }}">追加</span>
                    </button>
                </div>
                <div id="measurement-form-errors-{{ $character->id }}"
                    class="sm:col-span-2 text-sm text-red-600 space-y-1 mt-1"></div>
            </form>
        </div>
    @endcan
</div>

<script>
// The script part is unchanged.
if (typeof window.initializeMeasurementTemplateFlags === 'undefined') {
    window.initializeMeasurementTemplateFlags = {};
}

function setupMeasurementTemplateFunctionality(characterId, projectId) {
    const section = document.getElementById(`character-measurements-section-${characterId}`);
    if (!section) {
        return;
    }

    if (window.initializeMeasurementTemplateFlags[`char_${characterId}`]) {
        return;
    }
    window.initializeMeasurementTemplateFlags[`char_${characterId}`] = true;

    const templateSelect = section.querySelector(`#measurement_template_select-${characterId}`);
    const applyTemplateBtn = section.querySelector(`#apply-measurement-template-btn-${characterId}`);
    const applyStatusDiv = section.querySelector(`#apply-template-status-${characterId}`);

    const templateNameInput = section.querySelector(`#measurement_template_name_input-${characterId}`);
    const saveTemplateBtn = section.querySelector(`#save-measurement-template-btn-${characterId}`);
    const saveStatusDiv = section.querySelector(`#save-template-status-${characterId}`);

    const measurementForm = section.querySelector(`#measurement-form-${characterId}`);
    const measurementTableBody = section.querySelector(`#measurement-sortable-${characterId}`); // <- IDを修正

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function displayError(div, errors) {
        if (!div) return;
        let errorMsg = errors.message || 'エラーが発生しました。';
        if (errors.errors) {
            errorMsg += '<ul class="list-disc list-inside pl-4">';
            for (const key in errors.errors) {
                errorMsg += `<li>${errors.errors[key].join(', ')}</li>`;
            }
            errorMsg += '</ul>';
        }
        div.innerHTML = `<span class="text-red-500">${errorMsg}</span>`;
    }

    function displaySuccess(div, message) {
        if (!div) return;
        div.innerHTML = `<span class="text-green-500">${message}</span>`;
        setTimeout(() => { if (div) div.innerHTML = ''; }, 5000);
    }

    function displayInfo(div, message) {
        if (!div) return;
        div.innerHTML = `<span class="text-blue-500">${message}</span>`;
    }

    function loadTemplates() {
        if (!templateSelect) return;
        if (applyStatusDiv) displayInfo(applyStatusDiv, 'テンプレートを読み込み中...');

        fetch(`/projects/${projectId}/characters/${characterId}/measurement-templates`)
            .then(response => {
                if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
                return response.json();
            })
            .then(data => {
                templateSelect.innerHTML = '<option value="">テンプレートを選択...</option>';
                if (data.templates && data.templates.length > 0) {
                    data.templates.forEach(template => {
                        const option = document.createElement('option');
                        option.value = template.id;
                        option.textContent = template.name;
                        templateSelect.appendChild(option);
                    });
                    if (applyStatusDiv) applyStatusDiv.innerHTML = '';
                } else {
                     if (applyStatusDiv) applyStatusDiv.innerHTML = '<span class="text-xs text-gray-500">利用可能なテンプレートはありません。</span>';
                }
            })
            .catch(error => {
                console.error('Error fetching measurement templates:', error);
                if (applyStatusDiv) displayError(applyStatusDiv, {message: 'テンプレートの読み込みに失敗しました。'});
            });
    }

    if (saveTemplateBtn) {
        saveTemplateBtn.addEventListener('click', function() {
            const templateName = templateNameInput.value.trim();
            if (!templateName) {
                displayError(saveStatusDiv, {message: 'テンプレート名を入力してください。'});
                templateNameInput.focus();
                return;
            }

            const itemsToSave = [];
            measurementTableBody.querySelectorAll('tr').forEach(row => {
                if (row.id && row.id.startsWith('measurement-row-')) {
                    const itemCell = row.querySelector('.measurement-item');
                    const valueCell = row.querySelector('.measurement-value');
                    const notesCell = row.querySelector('.measurement-notes');
                    if (itemCell) {
                        const item = itemCell.textContent.trim();
                        // value は data-sort-value 属性を優先して取得し、なければ表示文字列を使う
                        let value = '';
                        if (valueCell) {
                            value = valueCell.getAttribute('data-sort-value') || valueCell.textContent.trim();
                        }
                        const notesHTML = notesCell ? notesCell.innerHTML : '';
                        let notesText = '';
                        if (notesHTML.toLowerCase() !== '-') {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = notesHTML.replace(/<br>\s*\/?/gi, "\n");
                            notesText = tempDiv.textContent || tempDiv.innerText || "";
                        }
                        const finalNotes = (notesText.trim() === '-' || notesText.trim() === '') ? '' : notesText.trim();
                        if (item) {
                            itemsToSave.push({ item: item, value: value, notes: finalNotes });
                        }
                    }
                }
            });

            if (itemsToSave.length === 0) {
                displayError(saveStatusDiv, {message: '保存する採寸項目がありません。現在の採寸リストに項目を追加してください。'});
                return;
            }

            displayInfo(saveStatusDiv, 'テンプレートを保存中...');
            fetch(`/projects/${projectId}/characters/${characterId}/measurement-templates`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    template_name: templateName,
                    items_to_save: itemsToSave
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 || status === 201) {
                    displaySuccess(saveStatusDiv, body.message || 'テンプレートを保存しました。');
                    templateNameInput.value = '';
                    loadTemplates();
                } else {
                    displayError(saveStatusDiv, body);
                }
            })
            .catch(error => {
                console.error('Error saving measurement template:', error);
                displayError(saveStatusDiv, {message: 'テンプレートの保存中にエラーが発生しました。'});
            });
        });
    }

    if (applyTemplateBtn) {
        applyTemplateBtn.addEventListener('click', async function() {
            const templateId = templateSelect.value;
            if (!templateId) {
                displayError(applyStatusDiv, {message: '適用するテンプレートを選択してください。'});
                return;
            }

            displayInfo(applyStatusDiv, 'テンプレートを適用中...');
            console.log(`[CharID: ${characterId}] Applying template ID: ${templateId}`);

            try {
                const templateResponse = await fetch(`/measurement-templates/${templateId}/load?project_id=${projectId}`);
                if (!templateResponse.ok) {
                    const errorText = await templateResponse.text();
                    console.error(`[CharID: ${characterId}] Failed to load template. Status: ${templateResponse.status}. Response: ${errorText}`);
                    throw new Error(`テンプレートの読み込みに失敗しました: ${templateResponse.statusText}`);
                }
                const templateData = await templateResponse.json();
                console.log(`[CharID: ${characterId}] Template data loaded:`, templateData);


                if (templateData && templateData.items && Array.isArray(templateData.items)) {
                    if (templateData.items.length === 0) {
                        if(applyStatusDiv) applyStatusDiv.innerHTML = `<span class="text-yellow-500">テンプレートに適用する項目がありません。</span>`;
                        console.log(`[CharID: ${characterId}] Template has no items.`);
                        return;
                    }

                    let successCount = 0;
                    let errorCount = 0;
                    const totalItems = templateData.items.length;
                    console.log(`[CharID: ${characterId}] Starting to apply ${totalItems} items.`);

                    for (let i = 0; i < templateData.items.length; i++) {
                        const item = templateData.items[i];
                        console.log(`[CharID: ${characterId}] Processing item ${i + 1}/${totalItems}:`, item);

                        if(applyStatusDiv) displayInfo(applyStatusDiv, `テンプレート適用中... (${i + 1}/${totalItems})`);

                        const formData = new FormData();
                        formData.append('item', item.item);
                        formData.append('value', item.value !== undefined && item.value !== null && item.value !== '' ? item.value : '0');
                        const notesToSend = (item.notes === null || typeof item.notes === 'undefined' || item.notes.trim() === '-' || item.notes.trim() === '') ? '' : item.notes;
                        formData.append('notes', notesToSend);
                        formData.append('_token', csrfToken);

                        const storeUrl = measurementForm.dataset.storeUrl;
                        try {
                            const itemAddResponse = await fetch(storeUrl, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                body: formData
                            });
                            const itemResult = await itemAddResponse.json();

                            if (itemAddResponse.ok && itemResult.success && itemResult.measurement) {
                                successCount++;
                                addMeasurementRowToTable(itemResult.measurement, characterId, projectId);
                                console.log(`[CharID: ${characterId}] Item added successfully:`, itemResult.measurement);
                            } else {
                                errorCount++;
                                console.error(`[CharID: ${characterId}] Error adding item from template. Item:`, item, 'Result:', itemResult.message || `Failed with status ${itemAddResponse.status}`, 'Details:', itemResult.errors);
                            }
                        } catch (singleItemError) {
                            errorCount++;
                            console.error(`[CharID: ${characterId}] Exception during single item fetch for item:`, item, 'Error:', singleItemError);
                        }
                    }
                    console.log(`[CharID: ${characterId}] Loop finished. Success: ${successCount}, Errors: ${errorCount}`);


                    if (successCount > 0) {
                        displaySuccess(applyStatusDiv, `${successCount}件の項目を適用しました。` + (errorCount > 0 ? ` (${errorCount}件失敗)` : ''));
                    } else if (errorCount > 0) {
                        displayError(applyStatusDiv, {message: `全${totalItems}件の項目の適用に失敗しました。コンソールで詳細を確認してください。`});
                    } else {
                       if(applyStatusDiv) applyStatusDiv.innerHTML = `<span class="text-yellow-500">テンプレートの項目を適用できませんでした。</span>`;
                    }

                } else {
                    console.error(`[CharID: ${characterId}] Template data format incorrect:`, templateData);
                    displayError(applyStatusDiv, {message: 'テンプレートデータの形式が正しくありません。'});
                }
            } catch (error) {
                console.error(`[CharID: ${characterId}] Error applying measurement template:`, error);
                displayError(applyStatusDiv, {message: `テンプレートの適用中にエラーが発生しました: ${error.message}`});
            }
        });
    }

    function addMeasurementRowToTable(measurement, charId, projId) {
        const noDataRow = measurementTableBody.querySelector(`#no-measurement-data-row-${charId}`);
        if (noDataRow) noDataRow.remove();

        const newRowHtml = `
            <tr id="measurement-row-${measurement.id}">
                <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-item">${escapeHtml(measurement.item)}</td>
                <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-value">${escapeHtml(measurement.value)}</td>
                <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight measurement-notes" style="min-width: 150px;">${measurement.notes ? nl2br(escapeHtml(measurement.notes)) : '-'}</td>
                <td class="px-3 py-1.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end space-x-1">
                        <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-measurement-btn" title="編集" data-id="${measurement.id}" data-item="${escapeHtml(measurement.item)}" data-value="${escapeHtml(measurement.value)}" data-notes="${escapeHtml(measurement.notes || '')}"><i class="fas fa-edit fa-sm"></i></button>
                        <form action="/projects/${projId}/characters/${charId}/measurements/${measurement.id}" method="POST" class="delete-measurement-form" data-id="${measurement.id}" onsubmit="return false;">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="削除"><i class="fas fa-trash fa-sm"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        `;
        measurementTableBody.insertAdjacentHTML('beforeend', newRowHtml);

        if (typeof window.setupDynamicMeasurementRowEventListeners === 'function') {
            window.setupDynamicMeasurementRowEventListeners(measurementTableBody.lastElementChild, charId, projId);
        }
    }

    function nl2br(str) {
        if (typeof str === 'undefined' || str === null) return '';
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
    }
    function escapeHtml(unsafe) {
        if (typeof unsafe === 'undefined' || unsafe === null) return '';
        return unsafe
             .toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    loadTemplates();
}
window.setupMeasurementTemplateFunctionality = setupMeasurementTemplateFunctionality;
</script>

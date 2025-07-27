@csrf
<div x-data="{
    fieldType: '{{ old('type', $formFieldDefinition->type ?? 'text') }}',
    isInventoryLinked: {{ old('is_inventory_linked', $formFieldDefinition->is_inventory_linked ?? false) ? 'true' : 'false' }}
}">
    @if ($errors->any())
        <div class="col-span-1 md:col-span-2 mb-4 p-4 rounded-md bg-red-50 dark:bg-red-900/30">
            <ul class=" list-disc list-inside text-sm text-red-600 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
        {{-- カテゴリ、ラベル、フィールド名、フィールドタイプ、プレースホルダー --}}
        @if(isset($categories))
            <div class="md:col-span-2">
                <x-input-label for="category" value="カテゴリ" :required="true" />
                <x-select-input id="category" name="category" class="mt-1 block w-full" :options="$categories"
                    :selected="old('category', $formFieldDefinition->category ?? request('category', 'project'))"
                    required />
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
            </div>
        @endif
        <div>
            <x-input-label for="label" value="表示ラベル" :required="true" />
            <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label', $formFieldDefinition->label ?? '')" required />
            <x-input-error :messages="$errors->get('label')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="name" value="フィールド名 (半角英数字と_のみ)" :required="true" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $formFieldDefinition->name ?? '')" required placeholder="e.g. client_contact_person" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="type" value="フィールドタイプ" :required="true" />
            <x-select-input id="type" name="type" x-model="fieldType" class="mt-1 block w-full" :options="$fieldTypes"
                :selected="old('type', $formFieldDefinition->type ?? 'text')" required />
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="placeholder" value="プレースホルダー" />
            <x-text-input id="placeholder" name="placeholder" type="text" class="mt-1 block w-full"
                :value="old('placeholder', $formFieldDefinition->placeholder ?? '')" />
            <x-input-error :messages="$errors->get('placeholder')" class="mt-2" />
            <div
                class="mt-2 p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
                <i class="fas fa-info-circle mr-1"></i>
                入力欄に薄く表示される例文や説明文。ユーザーが入力内容の参考にできるガイドのこと
            </div>
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description_editor" value="説明" />
            <div class="mt-1">
                <textarea id="description_editor"
                    name="help_text">{{ old('help_text', $formFieldDefinition->help_text ?? '') }}</textarea>
            </div>
            <x-input-error :messages="$errors->get('help_text')" class="mt-2" />
        </div>

        {{-- ★★★ 新しいオプション管理UI ★★★ --}}
        <div class="md:col-span-2" x-show="['select', 'radio', 'checkbox', 'image_select'].includes(fieldType)" x-data="optionsManager({
                initialOptions: {{ json_encode(old('options', $formFieldDefinition->options ?? [])) }},
                initialFieldType: '{{ old('type', $formFieldDefinition->type ?? 'text') }}',
                initialInventoryMap: {{ json_encode(old('option_inventory_map', $formFieldDefinition->option_inventory_map ?? [])) }},
                initialInventoryDisplayMap: {{ json_encode($inventoryDisplayMap ?? []) }}
             })" style="display: none;">

            {{-- 在庫連携設定のUI --}}
            <div class="border-t pt-6">
                <x-checkbox-input id="is_inventory_linked" name="is_inventory_linked" value="1" :label="'選択肢を在庫と連携する'"
                    x-model="isInventoryLinked" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    有効にすると、フォームで選択された際に指定の在庫数が減少します。在庫が0になった選択肢はフォームに表示されません。
                </p>
                <x-input-error :messages="$errors->get('is_inventory_linked')" class="mt-2" />
            </div>

            {{-- 動的オプションリスト --}}
            <div class="mt-4 p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                <x-input-label value="選択肢" />
                <div class="space-y-4 mt-2">
                    <template x-for="(option, index) in options" :key="option.id">
                        <div class="flex items-start gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                            {{-- 画像プレビュー (画像選択時のみ) --}}
                            <div x-show="fieldType === 'image_select'" class="flex-shrink-0">
                                <img :src="option.previewUrl"
                                    class="w-16 h-16 object-cover rounded-md border dark:border-gray-600">
                            </div>

                            <div class="flex-grow grid grid-cols-2 gap-4">
                                {{-- 値 --}}
                                <div class="space-y-1">
                                    <label :for="'option_value_' + option.id"
                                        class="block font-medium text-xs text-gray-700 dark:text-gray-300">値</label>
                                    <input type="text" :id="'option_value_' + option.id"
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full text-sm"
                                        x-model="option.value" :name="`options[${index}][value]`"
                                        placeholder="例: option_a">
                                    <input type="hidden" :name="`options[${index}][existing_path]`"
                                        :value="option.existing_path">
                                </div>
                                {{-- ラベル (テキスト系) or 画像ファイル (画像選択) --}}
                                <div class="space-y-1" x-show="['select', 'radio', 'checkbox'].includes(fieldType)">
                                    <label :for="'option_label_' + option.id"
                                        class="block font-medium text-xs text-gray-700 dark:text-gray-300">表示ラベル</label>
                                    <input type="text" :id="'option_label_' + option.id"
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full text-sm"
                                        x-model="option.label" :name="`options[${index}][label]`"
                                        placeholder="例: オプションA">
                                </div>
                                <div class="space-y-1" x-show="fieldType === 'image_select'">
                                    <label :for="'option_image_' + option.id"
                                        class="block font-medium text-xs text-gray-700 dark:text-gray-300">画像ファイル</label>
                                    <input type="file" :id="'option_image_' + option.id"
                                        :name="`options[${index}][image]`" @change="handleFileSelect($event, index)"
                                        class="block w-full text-sm text-gray-500 file:text-xs file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                                {{-- 在庫連携ドロップダウン --}}
                                <div class="space-y-1" x-show="isInventoryLinked">
                                    <label :for="'inventory_id_' + option.id"
                                        class="block font-medium text-xs text-gray-700 dark:text-gray-300">連携在庫</label>
                                    <select :id="'inventory_id_' + option.id"
                                        :name="`options[${index}][inventory_item_id]`" :data-index="index"
                                        class="tom-select-inventory" placeholder="品名や品番で検索..."></select>
                                </div>
                                {{-- 消費数入力欄 --}}
                                <div class="space-y-1" x-show="isInventoryLinked && option.inventory_item_id">
                                    <label :for="'consumption_qty_' + option.id"
                                        class="block font-medium text-xs text-gray-700 dark:text-gray-300">消費数</label>
                                    <input type="number" :id="'consumption_qty_' + option.id"
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full text-sm"
                                        x-model.number="option.inventory_consumption_qty"
                                        :name="`options[${index}][inventory_consumption_qty]`" min="1" placeholder="1">
                                </div>
                            </div>
                            <button type="button" @click="removeOption(index)"
                                class="p-2 text-red-500 hover:text-red-700 self-center">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addOption()"
                    class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">+
                    新しい選択肢を追加</button>
            </div>
        </div>
        {{-- ★★★ オプション管理UIここまで ★★★ --}}


        {{-- 最大長、表示順、選択数設定 --}}
        <div>
            <x-input-label for="max_length" value="最大長 (テキスト系フィールド)" />
            <x-text-input id="max_length" name="max_length" type="number" min="1" class="mt-1 block w-full"
                :value="old('max_length', $formFieldDefinition->max_length ?? '')" />
            <x-input-error :messages="$errors->get('max_length')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="order" value="表示順" />
            <x-text-input id="order" name="order" type="number" class="mt-1 block w-full" :value="old('order', $formFieldDefinition->order ?? 0)" />
            <x-input-error :messages="$errors->get('order')" class="mt-2" />
        </div>
        <div x-show="fieldType === 'image_select' || fieldType === 'checkbox'" class="grid grid-cols-2 gap-x-6"
            style="display: none;">
            <div>
                <x-input-label for="min_selections" value="最小選択数" />
                <x-text-input id="min_selections" name="min_selections" type="number" min="0" class="mt-1 block w-full"
                    :value="old('min_selections', $formFieldDefinition->min_selections ?? '')" placeholder="指定なし" />
                <x-input-error :messages="$errors->get('min_selections')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="max_selections" value="最大選択数" />
                <x-text-input id="max_selections" name="max_selections" type="number" min="0" class="mt-1 block w-full"
                    :value="old('max_selections', $formFieldDefinition->max_selections ?? '')" placeholder="指定なし" />
                <x-input-error :messages="$errors->get('max_selections')" class="mt-2" />
            </div>
        </div>
    </div>

    {{-- 必須、有効チェックボックス、保存ボタン --}}
    <div class="mt-4">
        <x-checkbox-input id="is_required" name="is_required" value="1" :label="'この項目を必須にする'"
            :checked="old('is_required', $formFieldDefinition->is_required ?? false)" />
    </div>
    <div class="mt-4">
        <x-checkbox-input id="is_enabled" name="is_enabled" value="1" :label="'この項目定義を有効にする'"
            :checked="old('is_enabled', $formFieldDefinition->is_enabled ?? true)" />
    </div>
    <div class="mt-8 flex justify-end space-x-3">
        <x-secondary-button as="a" :href="route('admin.form-definitions.index', ['category' => request('category', 'project')])">
            キャンセル
        </x-secondary-button>
        <x-primary-button type="submit">
            <i class="fas fa-save mr-2"></i> 保存
        </x-primary-button>
    </div>
</div>

@push('scripts')
    {{-- CKEditorの「Classic」ビルドを読み込みます --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

    {{-- 動作実績のあるプロジェクトから移植した画像アップロード用アダプター --}}
    <script>
        class SimpleUploadAdapter {
            constructor(loader) {
                this.loader = loader;
            }
            upload() {
                return this.loader.file.then(file => new Promise((resolve, reject) => {
                    const data = new FormData();
                    data.append('upload', file);
                    // CSRFトークンはヘッダーで送信
                    fetch('{{ route("admin.form-definitions.uploadImage") }}', {
                        method: 'POST',
                        body: data,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                        .then(response => response.json())
                        .then(result => {
                            // コントローラーからの応答形式に合わせて修正
                            if (result.uploaded) {
                                resolve({
                                    default: result.url
                                });
                            } else {
                                // エラーメッセージがある場合はそれを表示
                                const message = result.error && result.error.message ? result.error.message : 'アップロードに失敗しました。';
                                reject(message);
                            }
                        })
                        .catch(error => {
                            reject('通信エラーによりアップロードに失敗しました。');
                        });
                }));
            }
            abort() {
                // この機能は通常不要です
            }
        }

        function SimpleUploadAdapterPlugin(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                return new SimpleUploadAdapter(loader);
            };
        }
    </script>

    {{-- CKEditorの初期化スクリプト --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ClassicEditorを直接呼び出します
            ClassicEditor
                .create(document.querySelector('#description_editor'), {
                    // 自作のアダプターをプラグインとして読み込みます
                    extraPlugins: [SimpleUploadAdapterPlugin],
                    language: 'ja',
                    toolbar: {
                        items: [
                            'bold',
                            'italic',
                            '|',
                            'imageUpload'
                        ]
                    }
                })
                .catch(error => {
                    console.error('CKEditor初期化エラー:', error);
                });
        });
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('optionsManager', (config) => ({
                options: [],
                nextId: 0,
                init() {
                    let initialData = [];
                    const inventoryMap = config.initialInventoryMap || {};
                    const displayMap = config.initialInventoryDisplayMap || {};

                    if (Array.isArray(config.initialOptions)) { // バリデーション失敗で戻ってきた場合
                        initialData = config.initialOptions.map(opt => ({
                            value: opt.value || '',
                            label: opt.label || '',
                            existing_path: opt.existing_path || '',
                            previewUrl: opt.existing_path || '/placeholder.svg',
                            inventory_item_id: opt.inventory_item_id || null,
                            inventory_consumption_qty: opt.inventory_consumption_qty || 1
                        }));
                    } else { // 通常の編集画面表示
                        Object.entries(config.initialOptions).forEach(([value, labelOrUrl]) => {
                            const inventorySettings = inventoryMap[value] || {};
                            initialData.push({
                                value: value,
                                label: config.initialFieldType !== 'image_select' ? labelOrUrl : '',
                                existing_path: config.initialFieldType === 'image_select' ? labelOrUrl : '',
                                previewUrl: config.initialFieldType === 'image_select' ? labelOrUrl : '/placeholder.svg',
                                inventory_item_id: inventorySettings.id || null,
                                inventory_consumption_qty: inventorySettings.qty || 1
                            });
                        });
                    }

                    this.options = initialData.map(opt => {
                        const id = this.nextId++;
                        const inventoryId = opt.inventory_item_id;
                        return {
                            ...opt,
                            id: id,
                            inventory_consumption_qty: opt.inventory_consumption_qty || 1,
                            inventory_item_display_name: inventoryId ? (displayMap[inventoryId] || inventoryId) : null
                        };
                    });

                    this.$nextTick(() => this.initializeAllTomSelects());
                },
                addOption() {
                    this.options.push({
                        id: this.nextId++,
                        value: '', label: '', existing_path: '', previewUrl: '/placeholder.svg', inventory_item_id: null, inventory_consumption_qty: 1
                    });
                    this.$nextTick(() => this.initializeAllTomSelects());
                },
                removeOption(index) {
                    const optionId = this.options[index].id;
                    const selectEl = document.getElementById(`inventory_id_${optionId}`);
                    if (selectEl && selectEl.tomselect) {
                        selectEl.tomselect.destroy();
                    }
                    this.options.splice(index, 1);
                },
                handleFileSelect(event, index) {
                    const file = event.target.files[0];
                    if (file) {
                        this.options[index].previewUrl = URL.createObjectURL(file);
                    }
                },
                initializeAllTomSelects() {
                    // optionsごとにselect要素を取得し、その都度初期化する
                    this.options.forEach((option, index) => {
                        const el = document.getElementById(`inventory_id_${option.id}`);
                        if (!el || el.classList.contains('ts-wrapper') || el.tomselect) return;

                        const tom = new TomSelect(el, {
                            valueField: 'id',
                            labelField: 'text',
                            searchField: 'text',
                            create: false,
                            onChange: (value) => {
                                this.options[index].inventory_item_id = value;
                            },
                            load: (query, callback) => {
                                const url = `{{ route('admin.inventory.search_api') }}?q=` + encodeURIComponent(query);
                                fetch(url)
                                    .then(response => response.json())
                                    .then(json => callback(json))
                                    .catch(() => callback());
                            }
                        });

                        // 既存の値を設定
                        if (option.inventory_item_id && option.inventory_item_display_name) {
                            tom.addOption({ id: option.inventory_item_id, text: option.inventory_item_display_name });
                            tom.setValue(option.inventory_item_id);
                        }
                    });
                }
            }));
        });
    </script>
@endpush
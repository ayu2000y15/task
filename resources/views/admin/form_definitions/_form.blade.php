@csrf
<div x-data="{ fieldType: '{{ old('type', $formFieldDefinition->type ?? 'text') }}' }">
    @if ($errors->any())
        <div class="col-span-1 md:col-span-2 mb-4 p-4 rounded-md bg-red-50 dark:bg-red-900/30">
            <ul class="mt-3 list-disc list-inside text-sm text-red-600 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
        @if(isset($categories))
            <div class="md:col-span-2">
                <x-input-label for="category" value="カテゴリ" :required="true" />
                <x-select-input id="category" name="category" class="mt-1 block w-full" :options="$categories"
                    :selected="old('category', $formFieldDefinition->category ?? request('category', 'project'))"
                    required />
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">この項目がどの用途で使用されるかを選択してください。</p>
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
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">システム内部で使用する名前です。一度作成すると変更しないでください。</p>
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
        </div>

        {{-- 選択肢の入力欄 --}}
        <div class="md:col-span-2" x-show="['select', 'radio', 'checkbox'].includes(fieldType)" style="display: none;">
            <x-input-label for="options_text" value="選択肢 (テキストベース)" />
            <x-textarea-input id="options_text" name="options_text" class="mt-1 block w-full" rows="3"
                placeholder="例: value1:表示名1, value2:表示名2">{{ old('options_text', $optionsText ?? '') }}</x-textarea-input>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">カンマ区切りで「値:表示ラベル」の形式で入力します。</p>
        </div>

        <div class="md:col-span-2" x-show="fieldType === 'image_select'" style="display: none;" x-data='imageOptionsManager({
        initialOptions: @json($formFieldDefinition->options ?? [])
        })'>

            <x-input-label value="選択肢 (画像)" />
            <div class="mt-2 p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                <div class="space-y-4">
                    <template x-for="(option, index) in options" :key="index">
                        <div class="flex items-center gap-4 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                            <img :src="option.previewUrl"
                                class="w-16 h-16 object-cover rounded-md border dark:border-gray-600">
                            <div class="flex-grow space-y-1">
                                <x-input-label x-bind:for="'option_value_' + index" value="値" class="text-xs" />
                                <x-text-input x-bind:id="'option_value_' + index" type="text"
                                    class="block w-full text-sm" x-model="option.value"
                                    x-bind:name="`options[${index}][value]`" placeholder="例: option_a" />
                                <input type="hidden" x-bind:name="`options[${index}][existing_path]`"
                                    x-bind:value="option.existing_path">
                            </div>
                            <div class="flex-grow space-y-1">
                                <x-input-label x-bind:for="'option_image_' + index" value="画像ファイル" class="text-xs" />
                                <input type="file" x-bind:id="'option_image_' + index"
                                    x-bind:name="`options[${index}][image]`" @change="handleFileSelect($event, index)"
                                    class="block w-full text-sm text-gray-500 file:text-xs file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            </div>
                            <button type="button" @click="removeOption(index)"
                                class="p-2 text-red-500 hover:text-red-700 self-end mb-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addOption()"
                    class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">+
                    新しい選択肢を追加</button>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">「新しい選択肢を追加」で項目を増やし、それぞれ「値」と「画像」を設定します。</p>
        </div>

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

        {{-- 選択数の設定UI --}}
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
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('imageOptionsManager', (config) => ({
                options: [],
                init() {
                    // 既存の選択肢を初期化
                    if (config.initialOptions && typeof config.initialOptions === 'object') {
                        this.options = Object.entries(config.initialOptions).map(([value, imageUrl]) => ({
                            value: value,
                            previewUrl: imageUrl,
                            existing_path: imageUrl // 既存のパスを保持
                        }));
                    }
                },
                addOption() {
                    // 新しい空の選択肢を追加
                    this.options.push({ value: '', previewUrl: '/placeholder.svg', existing_path: '' });
                },
                removeOption(index) {
                    this.options.splice(index, 1);
                },
                handleFileSelect(event, index) {
                    const file = event.target.files[0];
                    if (!file) return;
                    // プレビューURLを更新
                    this.options[index].previewUrl = URL.createObjectURL(file);
                }
            }));
        });
    </script>
@endpush
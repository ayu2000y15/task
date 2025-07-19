<div x-data="inlineOptionsManager({
    definitionId: {{ $definition->id }},
    definitionType: '{{ $definition->type }}',
    isInventoryLinkedInitially: {{ $definition->is_inventory_linked ? 'true' : 'false' }},
    initialOptions: {{ json_encode($definition->options ?? []) }},
    initialInventoryMap: {{ json_encode($definition->option_inventory_map ?? []) }},
    inventoryItemsJson: {{ json_encode($availableInventoryItems->map(fn($item) => ['id' => $item->id, 'text' => $item->display_name, 'unit' => $item->unit, 'avg_price' => $item->average_unit_price, 'name_only' => $item->name])) }}
})" x-cloak>
    <form @submit.prevent="saveOptions">
        {{-- 在庫連携チェックボックス --}}
        <div class="mb-4">
            <x-checkbox-input :id="'is_inventory_linked_' . $definition->id" name="is_inventory_linked" value="1"
                :label="'選択肢を在庫と連携する'" x-model="isInventoryLinked" />
        </div>

        {{-- 動的オプションリスト --}}
        <div class="space-y-4">
            <template x-for="(option, index) in options" :key="option.id">
                <div
                    class="flex items-start gap-4 p-3 bg-white dark:bg-gray-700/50 rounded-md border dark:border-gray-600">
                    {{-- 画像プレビュー --}}
                    <div x-show="definitionType === 'image_select'" class="flex-shrink-0">
                        <img :src="option.previewUrl"
                            class="w-16 h-16 object-cover rounded-md border dark:border-gray-600">
                    </div>

                    <div class="flex-grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- 値 --}}
                        <div class="space-y-1">
                            <label :for="'option_value_' + option.id"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">値</label>
                            <input type="text" :id="'option_value_' + option.id"
                                class="form-input block w-full text-sm rounded-md" x-model="option.value"
                                :name="`options[${index}][value]`">
                            <input type="hidden" :name="`options[${index}][existing_path]`"
                                :value="option.existing_path">
                        </div>
                        {{-- ラベル or 画像ファイル --}}
                        <div class="space-y-1" x-show="definitionType !== 'image_select'">
                            <label :for="'option_label_' + option.id"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">表示ラベル</label>
                            <input type="text" :id="'option_label_' + option.id"
                                class="form-input block w-full text-sm rounded-md" x-model="option.label"
                                :name="`options[${index}][label]`">
                        </div>
                        <div class="space-y-1" x-show="definitionType === 'image_select'">
                            <label :for="'option_image_' + option.id"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">画像ファイル</label>
                            <input type="file" :id="'option_image_' + option.id" :name="`options[${index}][image]`"
                                @change="handleFileSelect($event, index)"
                                class="block w-full text-sm file:text-xs file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0">
                        </div>
                        {{-- 在庫連携 --}}
                        <div class="space-y-1" x-show="isInventoryLinked">
                            <label :for="'inventory_id_' + option.id"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">連携在庫</label>
                            <select :id="'inventory_id_' + option.id" :name="`options[${index}][inventory_item_id]`"
                                :data-index="index" class="tom-select-inventory-inline"
                                placeholder="在庫品目を検索..."></select>
                        </div>
                        {{-- 消費数 --}}
                        <div class="space-y-1" x-show="isInventoryLinked && option.inventory_item_id">
                            <label :for="'consumption_qty_' + option.id"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">消費数</label>
                            <input type="number" :id="'consumption_qty_' + option.id"
                                class="form-input block w-full text-sm rounded-md"
                                x-model.number="option.inventory_consumption_qty"
                                :name="`options[${index}][inventory_consumption_qty]`" min="1">
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
            class="mt-4 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">+
            新しい選択肢を追加</button>

        {{-- 保存ボタンとステータス表示 --}}
        <div class="flex justify-end items-center space-x-4 mt-6 pt-4 border-t dark:border-gray-700">
            <span x-text="statusMessage" :class="{ 'text-green-600': success, 'text-red-600': !success }"
                class="text-sm font-medium"></span>
            <x-secondary-button type="button" @click="open = false">キャンセル</x-secondary-button>
            <x-primary-button type="submit" x-text="isSaving ? '保存中...' : '保存'"
                x-bind:disabled="isSaving"></x-primary-button>
        </div>
    </form>
</div>
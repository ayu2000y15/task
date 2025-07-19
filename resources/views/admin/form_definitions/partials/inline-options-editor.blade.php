{{-- このdiv全体がJavaScriptの制御範囲になります --}}
<div id="inline-options-container-{{ $definition->id }}" class="inline-options-container"
    data-definition-id="{{ $definition->id }}" data-definition-type="{{ $definition->type }}">
    <form>
        {{-- 在庫連携チェックボックス --}}
        <div class="mb-4">
            <x-checkbox-input :id="'is_inventory_linked_' . $definition->id" name="is_inventory_linked" value="1"
                :label="'選択肢を在庫と連携する'" :checked="$definition->is_inventory_linked"
                class="is-inventory-linked-checkbox" />
        </div>

        {{-- 動的オプションリスト --}}
        <div class="space-y-4 options-list">
            {{-- 既存の選択肢をサーバーサイドで描画 --}}
            @foreach (($definition->options ?? []) as $value => $labelOrUrl)
                @php
                    $optionId = Str::random(8);
                    $inventoryMapping = $definition->option_inventory_map[$value] ?? null;
                @endphp
                <div
                    class="option-row flex items-start gap-4 p-3 bg-white dark:bg-gray-700/50 rounded-md border dark:border-gray-600">
                    {{-- 画像プレビュー --}}
                    @if ($definition->type === 'image_select')
                        <div class="flex-shrink-0">
                            <img src="{{ $labelOrUrl }}"
                                class="w-16 h-16 object-cover rounded-md border dark:border-gray-600 image-preview">
                        </div>
                    @endif

                    <div class="flex-grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- 値 --}}
                        <div class="space-y-1">
                            <label for="option_value_{{ $optionId }}"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">値</label>
                            <input type="text" id="option_value_{{ $optionId }}"
                                class="form-input block w-full text-sm rounded-md option-value-input" name="value"
                                value="{{ $value }}">
                            <input type="hidden" name="existing_path"
                                value="{{ $definition->type === 'image_select' ? $labelOrUrl : '' }}">
                        </div>

                        {{-- ラベル or 画像ファイル --}}
                        @if ($definition->type === 'image_select')
                            <div class="space-y-1">
                                <label for="option_image_{{ $optionId }}"
                                    class="block font-medium text-xs text-gray-700 dark:text-gray-300">画像ファイル</label>
                                <input type="file" id="option_image_{{ $optionId }}" name="image"
                                    class="block w-full text-sm file:text-xs file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        @else
                            <div class="space-y-1">
                                <label for="option_label_{{ $optionId }}"
                                    class="block font-medium text-xs text-gray-700 dark:text-gray-300">表示ラベル</label>
                                <input type="text" id="option_label_{{ $optionId }}"
                                    class="form-input block w-full text-sm rounded-md option-label-input" name="label"
                                    value="{{ $labelOrUrl }}">
                            </div>
                        @endif

                        {{-- 在庫連携 --}}
                        <div class="space-y-1 inventory-fields {{ $definition->is_inventory_linked ? '' : 'hidden' }}">
                            <label for="inventory_id_{{ $optionId }}"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">連携在庫</label>
                            <select id="inventory_id_{{ $optionId }}" name="inventory_item_id"
                                class="tom-select-inventory-inline" placeholder="在庫品目を検索...">
                                @if($inventoryMapping && isset($inventoryMapping['id']))
                                    @php
                                        $item = $availableInventoryItems->firstWhere('id', $inventoryMapping['id']);
                                    @endphp
                                    @if($item)
                                        <option value="{{ $item->id }}" selected>{{ $item->display_name }}</option>
                                    @endif
                                @endif
                            </select>
                        </div>

                        {{-- 消費数 --}}
                        <div
                            class="space-y-1 consumption-fields {{ ($definition->is_inventory_linked && $inventoryMapping) ? '' : 'hidden' }}">
                            <label for="consumption_qty_{{ $optionId }}"
                                class="block font-medium text-xs text-gray-700 dark:text-gray-300">消費数</label>
                            <input type="number" id="consumption_qty_{{ $optionId }}" name="inventory_consumption_qty"
                                class="form-input block w-full text-sm rounded-md" min="1"
                                value="{{ $inventoryMapping['qty'] ?? 1 }}">
                        </div>
                    </div>
                    <button type="button" class="remove-option-btn p-2 text-red-500 hover:text-red-700 self-center">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>
        <button type="button"
            class="add-option-btn mt-4 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">+
            新しい選択肢を追加</button>

        {{-- 保存ボタンとステータス表示 --}}
        <div class="flex justify-end items-center space-x-4 mt-6 pt-4 border-t dark:border-gray-700">
            <span class="status-message text-sm font-medium"></span>
            <x-secondary-button type="button" class="cancel-btn">キャンセル</x-secondary-button>
            <x-primary-button type="button" class="save-options-btn">保存</x-primary-button>
        </div>
    </form>
</div>

{{-- 新しい選択肢を追加するためのHTMLテンプレート --}}
<template id="option-row-template">
    <div
        class="option-row flex items-start gap-4 p-3 bg-white dark:bg-gray-700/50 rounded-md border dark:border-gray-600">
        <div class="flex-shrink-0 image-preview-wrapper" style="display: none;">
            <img src="/placeholder.svg"
                class="w-16 h-16 object-cover rounded-md border dark:border-gray-600 image-preview">
        </div>
        <div class="flex-grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="space-y-1">
                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300">値</label>
                <input type="text" name="value" class="form-input block w-full text-sm rounded-md option-value-input">
                <input type="hidden" name="existing_path" value="">
            </div>
            <div class="space-y-1 label-field-wrapper" style="display: none;">
                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300">表示ラベル</label>
                <input type="text" name="label" class="form-input block w-full text-sm rounded-md option-label-input">
            </div>
            <div class="space-y-1 image-field-wrapper" style="display: none;">
                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300">画像ファイル</label>
                <input type="file" name="image"
                    class="block w-full text-sm file:text-xs file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 option-image-input">
            </div>
            <div class="space-y-1 inventory-fields hidden">
                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300">連携在庫</label>
                <select name="inventory_item_id" class="tom-select-inventory-inline" placeholder="在庫品目を検索..."></select>
            </div>
            <div class="space-y-1 consumption-fields hidden">
                <label class="block font-medium text-xs text-gray-700 dark:text-gray-300">消費数</label>
                <input type="number" name="inventory_consumption_qty" class="form-input block w-full text-sm rounded-md"
                    min="1" value="1">
            </div>
        </div>
        <button type="button" class="remove-option-btn p-2 text-red-500 hover:text-red-700 self-center">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>
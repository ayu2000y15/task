{{-- resources/views/projects/partials/character-materials-tailwind.blade.php --}}
<div class="space-y-4">
    <div
        class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
        <i class="fas fa-info-circle mr-1"></i>
        材料は在庫品目から選択または入力が可能です。<br>
        　在庫品目を選択すると、在庫品目の単価が自動的に設定されます。<br>
        　単価は追加時に計算されたものから変動しませんのでご注意ください。
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-16">
                        購入</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        材料名 [品番/色番] (在庫品目)</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        必要量</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        合計参考価格</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        備考</th>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20">
                        操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($character->materials as $material)
                    <tr id="material-row-{{ $material->id }}">
                        <td class="px-3 py-1.5 whitespace-nowrap">
                            @can('manageMaterials', $project)
                                @if($material->inventory_item_id)
                                    {{-- 在庫品の場合は「購入済」テキスト表示 (変更不可) --}}
                                    <span class="text-xs px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">購入済</span>
                                @else
                                    {{-- 在庫品でない場合は編集可能なチェックボックス --}}
                                    <input type="checkbox" id="material-status-checkbox-{{ $material->id }}"
                                        class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600 material-status-checkbox"
                                        data-url="{{ route('projects.characters.materials.update', [$project, $character, $material]) }}"
                                        data-id="{{ $material->id }}" data-character-id="{{ $character->id }}"
                                        {{ $material->status === '購入済' ? 'checked' : '' }}>
                                @endif
                            @else
                                {{-- 権限がない場合は常にテキスト表示 --}}
                                <span class="text-gray-700 dark:text-gray-200">{{ $material->status }}</span>
                            @endcan
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-name">
                            {{ $material->inventoryItem->name ?? $material->name }}
                            @if($material->inventoryItem)
                                <span class="text-xs">[ {{ $material->inventoryItem->product_number ?? 'なし'}} / {{ $material->inventoryItem->color_number ?? 'なし' }} ]</span>
                                <span class="text-xs text-gray-400">(在庫品)</span>
                            @endif
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-quantity_needed">
                            @php
                                $qtyNeeded = $material->quantity_needed;
                                $unitLower = strtolower($material->unit ?? optional($material->inventoryItem)->unit);
                                $decimalsQty = 0;
                                if ($unitLower === 'm') {
                                    $decimalsQty = (fmod($qtyNeeded, 1) !== 0.00 ? 1 : 0);
                                } else {
                                    if (fmod($qtyNeeded, 1) !== 0.00) {
                                        $decimalPart = explode('.', (string)$qtyNeeded)[1] ?? '';
                                        $decimalsQty = strlen($decimalPart);
                                        if ($decimalsQty > 2) $decimalsQty = 2;
                                    }
                                }
                            @endphp
                            {{ number_format($qtyNeeded, $decimalsQty) }} {{ $material->unit ?? optional($material->inventoryItem)->unit }}
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-price">
                            {{ !is_null($material->price) ? number_format($material->price) . '円' : '-' }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight material-notes"
                            style="min-width: 150px;">
                            {!! nl2br(e($material->notes)) ?: '-' !!}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1">
                                @can('updateMaterials', $project)
                                    <button type="button"
                                        class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-material-btn"
                                        title="編集" data-id="{{ $material->id }}"
                                        data-inventory_item_id="{{ $material->inventory_item_id }}"
                                        data-name="{{ $material->name }}"
                                        data-price="{{ $material->price }}"
                                        data-unit="{{ $material->unit }}"
                                        data-unit_price_at_creation="{{ $material->unit_price_at_creation }}"
                                        data-quantity_needed="{{ $material->quantity_needed }}"
                                        data-status="{{ $material->status }}"
                                        data-notes="{{ $material->notes }}">
                                        <i class="fas fa-edit fa-sm"></i>
                                    </button>
                                @endcan
                                @can('deleteMaterials', $project)
                                    <form
                                        action="{{ route('projects.characters.materials.destroy', [$project, $character, $material]) }}"
                                        method="POST" class="delete-material-form" data-id="{{ $material->id }}"
                                        onsubmit="return false;">
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
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            登録されている材料はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@can('manageMaterials', $project)
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2" id="material-form-title-{{ $character->id }}">材料を追加</h6>
        <form id="material-form-{{ $character->id }}"
            action="{{ route('projects.characters.materials.store', [$project, $character]) }}" method="POST"
            data-store-url="{{ route('projects.characters.materials.store', [$project, $character]) }}"
            data-character-id="{{ $character->id }}"
            class="space-y-3">
            @csrf
            <input type="hidden" name="_method" id="material-form-method-{{ $character->id }}" value="POST">
            <input type="hidden" name="material_id" id="material-form-id-{{ $character->id }}" value="">

            <input type="hidden" name="name" id="material_name_hidden_input-{{ $character->id }}" value="">
            <input type="hidden" name="unit" id="material_unit_hidden_input-{{ $character->id }}" value="">
            <input type="hidden" name="price" id="material_price_hidden_input-{{ $character->id }}">
            <input type="hidden" name="unit_price_at_creation" id="material_unit_price_hidden_input-{{ $character->id }}">
            <input type="hidden" name="status" id="material_status_hidden_input-{{ $character->id }}" value="未購入">

            {{-- 1行目: 在庫品目選択 / 材料名手入力 --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-start">
                <div class="sm:col-span-2">
                    <x-input-label for="inventory_item_id_input-{{ $character->id }}" value="材料種別" :required="true" />
                    <select name="inventory_item_id" id="inventory_item_id_input-{{ $character->id }}"
                            class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 material-inventory-item-select"
                            data-character-id="{{ $character->id }}" required>
                        <option value="">在庫品目を選択...</option>
                        <option value="manual_input">在庫品目以外 (手入力)</option>
                        @php
                            $availableInventoryItems = $availableInventoryItems ?? collect();
                        @endphp
                        @foreach($availableInventoryItems as $item)
                            <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-name="{{ $item->name }}" data-avg_price="{{ $item->average_unit_price }}">
                                {{ $item->name }} (品番:{{ $item->product_number ?? 'なし' }}, 色番:{{ $item->color_number ?? 'なし'}}, 在庫: {{ number_format($item->quantity, (in_array(strtolower($item->unit), ['m'])) ? (fmod($item->quantity, 1) !== 0.00 ? 1:0) : 0) }}, 単位: {{ $item->unit }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('inventory_item_id')" class="mt-2" />
                </div>

                <div id="manual_material_name_field-{{ $character->id }}" class="sm:col-span-2 hidden">
                    <x-input-label for="manual_material_name_input-{{ $character->id }}" value="材料名" :required="true" />
                    <x-text-input type="text" id="manual_material_name_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        placeholder="例: 赤色の布" />
                </div>

                <div>
                    <x-input-label for="material_unit_display-{{ $character->id }}" value="単位" :required="true"/>
                    <x-text-input type="text" id="material_unit_display-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400"
                        readonly placeholder="品目選択時自動表示/手入力" />
                </div>
            </div>

            {{-- 2行目: 必要量 と 合計参考価格 --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-start">
                <div class="sm:col-span-1">
                    <x-input-label for="material_quantity_input-{{ $character->id }}" value="必要量" :required="true" />
                    <x-text-input type="number" step="0.01" name="quantity_needed" id="material_quantity_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 material-quantity-input"
                        data-character-id="{{ $character->id }}"
                        placeholder="例: 2" required />
                    <x-input-error :messages="$errors->get('quantity_needed')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="material_price_display-{{ $character->id }}" value="合計参考価格(円)" :required="true"/>
                    <x-text-input type="text" id="material_price_display-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400"
                        placeholder="自動計算/手入力" readonly />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="material_notes_input-{{ $character->id }}" value="備考" />
                <x-textarea-input name="notes" id="material_notes_input-{{ $character->id }}"
                    class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 leading-tight"
                    rows="2"></x-textarea-input>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" id="material_status_checkbox_for_form-{{ $character->id }}"
                       class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600">
                <label for="material_status_checkbox_for_form-{{ $character->id }}" class="text-sm text-gray-700 dark:text-gray-300">購入済として処理する</label>
            </div>

            {{-- 他のキャラクターへ適用オプション (新規追加フォームにのみ表示) --}}
            <div id="apply_to_others_wrapper_for_add_material-{{ $character->id }}">
                @if ($project->characters->count() > 1)
                    <div class="pt-2">
                        <x-input-label for="apply_material_to_other_characters-{{ $character->id }}" class="inline-flex items-center">
                            <input type="checkbox" id="apply_material_to_other_characters-{{ $character->id }}" name="apply_to_other_characters" value="1"
                                   class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">この案件の他のすべてのキャラクターに同じ材料情報を適用する</span>
                        </x-input-label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            注意: チェックすると、他のキャラクターにもこの材料が新規追加されます。
                        </p>
                    </div>
                @endif
            </div>


            <div class="flex justify-end items-center space-x-2">
                <x-secondary-button type="button" id="material-form-cancel-btn-{{ $character->id }}" style="display: none;">
                    キャンセル
                </x-secondary-button>
                <button type="submit" id="material-form-submit-btn-{{ $character->id }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                    <i class="fas fa-plus"></i> <span class="ml-2" id="material-form-submit-btn-text-{{ $character->id }}">追加</span>
                </button>
            </div>
            <div id="material-form-errors-{{ $character->id }}" class="text-sm text-red-600 space-y-1 mt-1"></div>
        </form>
    </div>
@endcan
</div>
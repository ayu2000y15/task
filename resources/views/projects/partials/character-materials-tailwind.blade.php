<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-16">
                        購入</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        材料名</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        価格</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        必要量</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        備考</th>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20">
                        {{-- ★ 幅調整 --}}
                        操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($character->materials as $material)
                    <tr id="material-row-{{ $material->id }}"> {{-- ★ 行にID付与 --}}
                        <td class="px-3 py-1.5 whitespace-nowrap">
                            @can('manageMaterials', $project) {{-- ★ AJAX更新対象のため、id等を追加 --}}
                                <input type="checkbox" id="material-status-checkbox-{{ $material->id }}"
                                    class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600 material-status-checkbox"
                                    data-url="{{ route('projects.characters.materials.update', [$project, $character, $material]) }}"
                                    data-id="{{ $material->id }}" data-character-id="{{ $character->id }}" {{ $material->status === '購入済' ? 'checked' : '' }}>
                            @else
                                <span class="text-gray-700 dark:text-gray-200">{{ $material->status }}</span>
                            @endcan
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-name">
                            {{ $material->name }}
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-price">
                            {{ !is_null($material->price) ? number_format($material->price) . '円' : '-' }}
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-quantity_needed">
                            {{ $material->quantity_needed }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight material-notes"
                            style="min-width: 150px;">
                            {!! nl2br(e($material->notes)) ?: '-' !!} {{-- ★ nl2br(e()) を使用 --}}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1">
                                @can('updateMaterials', $project) {{-- ★ 適切な更新権限 (例: manageMaterials) --}}
                                    <button type="button"
                                        class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-material-btn"
                                        title="編集" data-id="{{ $material->id }}" data-name="{{ $material->name }}"
                                        data-price="{{ $material->price }}"
                                        data-quantity_needed="{{ $material->quantity_needed }}"
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
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            登録されている材料はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageMaterials', $project)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2"
                id="material-form-title-{{ $character->id }}">材料を追加</h6>
            <form id="material-form-{{ $character->id }}"
                action="{{ route('projects.characters.materials.store', [$project, $character]) }}" method="POST"
                data-store-url="{{ route('projects.characters.materials.store', [$project, $character]) }}"
                data-character-id="{{ $character->id }}"
                class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-start">
                @csrf
                <input type="hidden" name="_method" id="material-form-method-{{ $character->id }}" value="POST">
                <input type="hidden" name="material_id" id="material-form-id-{{ $character->id }}" value="">

                <div>
                    <x-input-label for="material_name_input-{{ $character->id }}" value="材料名" :required="true" />
                    <x-text-input type="text" name="name" id="material_name_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required />
                </div>
                <div>
                    <x-input-label for="material_price_input-{{ $character->id }}" value="価格(円)" />
                    <x-text-input type="number" name="price" id="material_price_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        placeholder="例: 1000" min="0" />
                </div>
                <div>
                    <x-input-label for="material_quantity_input-{{ $character->id }}" value="必要量" :required="true" />
                    <x-text-input type="text" name="quantity_needed" id="material_quantity_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        placeholder="例: 2m" required />
                </div>
                <div class="sm:col-span-3">
                    <x-input-label for="material_notes_input-{{ $character->id }}" value="備考" />
                    <x-textarea-input name="notes" id="material_notes_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 leading-tight"
                        rows="2"></x-textarea-input>
                </div>
                <div class="sm:col-span-3 flex justify-end items-center space-x-2">
                    <x-secondary-button type="button" id="material-form-cancel-btn-{{ $character->id }}"
                        style="display: none;">
                        キャンセル
                    </x-secondary-button>
                    <button type="submit" id="material-form-submit-btn-{{ $character->id }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                        <i class="fas fa-plus"></i> <span class="ml-2"
                            id="material-form-submit-btn-text-{{ $character->id }}">追加</span>
                    </button>
                </div>
                <div id="material-form-errors-{{ $character->id }}"
                    class="sm:col-span-3 text-sm text-red-600 space-y-1 mt-1"></div>
            </form>
        </div>
    @endcan
</div>
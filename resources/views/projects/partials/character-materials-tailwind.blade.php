<div class="space-y-4">
    <div
        class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
        <i class="fas fa-info-circle mr-1"></i>
        材料を「購入済」にすると価格が登録されていれば同名の「材料費」としてコストに自動追加されます。逆に「未購入」に戻したり材料自体を削除した場合、または対応する「材料費」コストを手動で削除した場合、材料のステータスが「未購入」に戻ることがあります。
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
                        材料名</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        価格</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        必要量</th>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-10">
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($character->materials as $material)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @can('manageMaterials', $project)
                                <input type="checkbox"
                                    class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600 material-status-checkbox"
                                    data-url="{{ route('projects.characters.materials.update', [$project, $character, $material]) }}"
                                    data-character-id="{{ $character->id }}" {{ $material->status === '購入済' ? 'checked' : '' }}>
                            @else
                                <span class="text-gray-700 dark:text-gray-200">{{ $material->status }}</span>
                            @endcan
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $material->name }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">
                            {{ !is_null($material->price) ? number_format($material->price) . '円' : '-' }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">
                            {{ $material->quantity_needed }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-right">
                            @can('deleteMaterials', $project)
                                <form
                                    action="{{ route('projects.characters.materials.destroy', [$project, $character, $material]) }}"
                                    method="POST" onsubmit="return confirm('この材料を削除しますか？');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        title="削除">
                                        <i class="fas fa-trash fa-sm"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            登録されている材料はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageMaterials', $project)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">材料を追加</h6>
            <form action="{{ route('projects.characters.materials.store', [$project, $character]) }}" method="POST"
                class="grid grid-cols-1 sm:grid-cols-8 gap-x-3 gap-y-2 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <label for="material_name_{{ $character->id }}"
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300">材料名</label>
                    <input type="text" name="name" id="material_name_{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required>
                </div>
                <div class="sm:col-span-2">
                    <label for="material_price_{{ $character->id }}"
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300">価格(円)</label>
                    <input type="number" name="price" id="material_price_{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        placeholder="例: 1000">
                </div>
                <div class="sm:col-span-2">
                    <label for="material_quantity_{{ $character->id }}"
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300">必要量</label>
                    <input type="text" name="quantity_needed" id="material_quantity_{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        placeholder="例: 2m" required>
                </div>
                <div class="sm:col-span-2"> <button type="submit"
                        class="w-full inline-flex items-center justify-center px-3 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                        <i class="fas fa-plus"></i> <span class="hidden sm:inline ml-1">追加</span>
                    </button>
                </div>
            </form>
        </div>
    @endcan
</div>
<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        項目</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        数値</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        単位</th>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-10">
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($character->measurements as $measurement)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $measurement->item }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $measurement->value }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $measurement->unit }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-right">
                            @can('deleteMeasurements', $project)
                                <form
                                    action="{{ route('projects.characters.measurements.destroy', [$project, $character, $measurement]) }}"
                                    method="POST" onsubmit="return confirm('この採寸データを削除しますか？');">
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
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">採寸データがありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageMeasurements', $project)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">採寸データを追加</h6>
            <form action="{{ route('projects.characters.measurements.store', [$project, $character]) }}" method="POST"
                class="grid grid-cols-1 sm:grid-cols-7 gap-x-3 gap-y-2 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <label for="measurement_item_{{ $character->id }}"
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300">項目</label>
                    <input type="text" name="item" id="measurement_item_{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required>
                </div>
                <div class="sm:col-span-2">
                    <label for="measurement_value_{{ $character->id }}"
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300">数値</label>
                    <input type="text" name="value" id="measurement_value_{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required>
                </div>
                <div class="sm:col-span-2">
                    <label for="measurement_unit_{{ $character->id }}"
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300">単位</label>
                    <select name="unit" id="measurement_unit_{{ $character->id }}"
                        class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200">
                        <option value="cm">cm</option>
                        <option value="mm">mm</option>
                        <option value="inch">inch</option>
                        <option value="">その他</option>
                    </select>
                </div>
                <div class="sm:col-span-1">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center px-3 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                        <i class="fas fa-plus"></i> <span class="hidden sm:inline ml-1">追加</span>
                    </button>
                </div>
            </form>
        </div>
    @endcan
</div>
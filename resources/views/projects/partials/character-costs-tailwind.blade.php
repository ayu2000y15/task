<div class="space-y-4 text-sm">
    <div class="p-3 rounded-md {{ $character->costs->sum('amount') > 0 ? 'bg-green-100 text-green-700 dark:bg-green-700/30 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-300' }}">
        合計コスト: <span class="font-semibold">{{ number_format($character->costs->sum('amount')) }}円</span>
    </div>

    <div class="overflow-x-auto character-costs-list-dynamic-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">内容</th>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">種別</th>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">金額</th>
                    <th scope="col" class="relative px-3 py-2 w-10"><span class="sr-only">削除</span></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 character-costs-list" data-character-id="{{ $character->id }}">
                @forelse($character->costs()->orderBy('cost_date', 'desc')->get() as $cost)
                    <tr id="cost-row-{{ $cost->id }}">
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $cost->cost_date->format('Y/m/d') }}</td>
                        <td class="px-4 py-2 whitespace-normal break-words text-gray-700 dark:text-gray-200">{{ $cost->item_description }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @switch($cost->type)
                                    @case('材料費') bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100 @break
                                    @case('作業費') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 @break
                                    @default bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100
                                @endswitch">
                                {{ $cost->type }}
                            </span>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ number_format($cost->amount) }}円</td>
                        <td class="px-3 py-2 whitespace-nowrap text-right">
                            @can('deleteCosts', $project)
                            <form action="{{ route('projects.characters.costs.destroy', [$project, $character, $cost]) }}"
                                  method="POST" onsubmit="return false;" class="delete-cost-form" data-cost-id="{{ $cost->id }}"> {{-- onsubmit="return false;" を追加 --}}
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="削除">
                                    <i class="fas fa-trash fa-sm"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">登録されているコストはありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageCosts', $project)
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">コストを追加</h6>
        <form action="{{ route('projects.characters.costs.store', [$project, $character]) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-9 gap-x-3 gap-y-2 items-end add-cost-form" data-character-id="{{ $character->id }}">
            @csrf
            <div class="sm:col-span-3">
                <label for="cost_item_description_{{ $character->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300">内容</label>
                <input type="text" name="item_description" id="cost_item_description_{{ $character->id }}" class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500" required>
            </div>
            <div class="sm:col-span-2">
                <label for="cost_amount_{{ $character->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300">金額(円)</label>
                <input type="number" name="amount" id="cost_amount_{{ $character->id }}" class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500" required min="0">
            </div>
            <div class="sm:col-span-2">
                <label for="cost_cost_date_{{ $character->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300">日付</label>
                <input type="date" name="cost_date" id="cost_cost_date_{{ $character->id }}" class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500" value="{{ today()->format('Y-m-d') }}" required>
            </div>
            <div class="sm:col-span-2">
                <label for="cost_type_{{ $character->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300">種別</label>
                <select name="type" id="cost_type_{{ $character->id }}" class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="材料費">材料費</option>
                    <option value="作業費">作業費</option>
                    <option value="その他">その他</option>
                </select>
            </div>
            <div class="sm:col-span-9 mt-2 sm:mt-0 sm:col-span-2 sm:col-start-8">
                 <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                    <i class="fas fa-plus"></i> <span class="hidden sm:inline ml-1">追加</span>
                </button>
            </div>
        </form>
    </div>
    @endcan
</div>
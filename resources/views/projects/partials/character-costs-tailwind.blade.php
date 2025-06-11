<div class="space-y-4 text-sm">
    <div
        class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
        <i class="fas fa-info-circle mr-1"></i>
        材料を「購入済」にすると価格が登録されていれば同名の「材料費」としてコストに自動追加されます。<br>
        　対応する「材料費」コストを手動で削除した場合、材料のステータスが「未購入」に戻ることがあります。
    </div>
    <div class="p-3 rounded-md {{ $character->costs->sum('amount') > 0 ? 'bg-green-100 text-green-700 dark:bg-green-700/30 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-300' }}">
        合計コスト: <span class="font-semibold">{{ number_format($character->costs->sum('amount')) }}円</span>
    </div>

    <div class="overflow-x-auto character-costs-list-dynamic-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    {{-- ★ ドラッグハンドル用の列を追加 --}}
                    <th scope="col" class="px-2 py-2 w-10"></th>
                    <th scope="col" class="sortable-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-sort-by="date" data-sort-type="date">日付 <i class="fas fa-sort sort-icon text-gray-400 ml-1"></i></th>
                    <th scope="col" class="sortable-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-sort-by="type">種別 <i class="fas fa-sort sort-icon text-gray-400 ml-1"></i></th>
                    <th scope="col" class="sortable-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-sort-by="amount" data-sort-type="numeric">金額 <i class="fas fa-sort sort-icon text-gray-400 ml-1"></i></th>
                    <th scope="col" class="sortable-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-sort-by="description">内容 <i class="fas fa-sort sort-icon text-gray-400 ml-1"></i></th>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">備考</th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20"> {{-- ★ 幅調整 --}}
                        操作</th>
                </tr>
            </thead>
            {{-- ★ tbodyにIDとクラスを追加 --}}
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 character-costs-list sortable-list" data-character-id="{{ $character->id }}" id="cost-sortable-{{ $character->id }}">
                @forelse($character->costs()->orderBy('display_order')->orderBy('id', 'desc')->get() as $cost)
                    {{-- ★ trにdata-idを追加 --}}
                    <tr id="cost-row-{{ $cost->id }}" data-id="{{ $cost->id }}">
                        {{-- ★ ドラッグハンドルを追加 --}}
                        <td class="px-2 py-1.5 whitespace-nowrap text-center text-gray-400 drag-handle"><i class="fas fa-grip-vertical"></i></td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 cost-cost_date" data-sort-value="{{ $cost->cost_date->toDateString() }}">{{ $cost->cost_date->format('Y/m/d') }}</td>
                        <td class="px-4 py-1.5 whitespace-nowrap cost-type">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @switch($cost->type)
                                    @case('材料費') bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100 @break
                                    @case('作業費') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 @break
                                    @default bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100
                                @endswitch">
                                {{ $cost->type }}
                            </span>
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 cost-amount" data-sort-value="{{ $cost->amount }}">{{ number_format($cost->amount) }}円</td>
                        <td class="px-4 py-1.5 whitespace-nowrap break-words text-gray-700 dark:text-gray-200 cost-item_description" style="min-width: 120px;">{{ $cost->item_description }}</td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight cost-notes" style="min-width: 150px;">
                            {!! nl2br(e($cost->notes)) ?: '-' !!}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1">
                                @can('updateCosts', $project) {{-- ★ 適切な更新権限 --}}
                                    <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-cost-btn"
                                            title="編集"
                                            data-id="{{ $cost->id }}"
                                            data-cost_date="{{ $cost->cost_date->format('Y-m-d') }}"
                                            data-type="{{ $cost->type }}"
                                            data-amount="{{ $cost->amount }}"
                                            data-item_description="{{ $cost->item_description }}"
                                            data-notes="{{ $cost->notes }}">
                                        <i class="fas fa-edit fa-sm"></i>
                                    </button>
                                @endcan
                                @can('deleteCosts', $project)
                                <form action="{{ route('projects.characters.costs.destroy', [$project, $character, $cost]) }}"
                                      method="POST" onsubmit="return false;" class="delete-cost-form" data-id="{{ $cost->id }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="削除">
                                        <i class="fas fa-trash fa-sm"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">登録されているコストはありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ★ 並び順保存ボタンを追加 --}}
    @if($character->costs->isNotEmpty())
    <div class="flex justify-start mt-4">
        <button type="button" class="save-order-btn inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150"
                data-target-list="#cost-sortable-{{ $character->id }}"
                data-url="{{ route('projects.characters.costs.updateOrder', [$project, $character]) }}">
            <i class="fas fa-save mr-2"></i>並び順を保存
        </button>
    </div>
    @endif

    @can('manageCosts', $project)
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2" id="cost-form-title-{{ $character->id }}">コストを追加</h6>
        <form id="cost-form-{{ $character->id }}"
              action="{{ route('projects.characters.costs.store', [$project, $character]) }}" method="POST"
              data-store-url="{{ route('projects.characters.costs.store', [$project, $character]) }}"
              data-character-id="{{ $character->id }}"
              class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 items-start add-cost-form">
            @csrf
            <input type="hidden" name="_method" id="cost-form-method-{{ $character->id }}" value="POST">
            <input type="hidden" name="cost_id" id="cost-form-id-{{ $character->id }}" value="">

            <div>
                <x-input-label for="cost_cost_date_input-{{ $character->id }}" value="日付" :required="true"/>
                <x-text-input type="date" name="cost_date" id="cost_cost_date_input-{{ $character->id }}" class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500" value="{{ today()->format('Y-m-d') }}" required/>
            </div>
            <div>
                <x-input-label for="cost_type_input-{{ $character->id }}" value="種別" :required="true"/>
                @php
                    $costTypes = ['作業費' => '作業費', 'その他' => 'その他'];
                @endphp
                <x-select-input name="type" id="cost_type_input-{{ $character->id }}" class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500" :options="$costTypes" :selected="old('type', '材料費')" required />
            </div>

            {{-- 金額と内容を横並びにするための親div --}}
            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                <div>
                    <x-input-label for="cost_amount_input-{{ $character->id }}" value="金額(円)" :required="true"/>
                    <x-text-input type="number" name="amount" id="cost_amount_input-{{ $character->id }}" class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500" required min="0"/>
                </div>
                <div>
                    <x-input-label for="cost_item_description_input-{{ $character->id }}" value="内容" :required="true"/>
                    <x-textarea-input name="item_description" id="cost_item_description_input-{{ $character->id }}" class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 leading-tight" rows="1" required></x-textarea-input>
                </div>
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="cost_notes_input-{{ $character->id }}" value="備考" />
                <x-textarea-input name="notes" id="cost_notes_input-{{ $character->id }}"
                    class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 leading-tight"
                    rows="2"></x-textarea-input>
            </div>
            <div class="sm:col-span-2 flex justify-end items-center space-x-2">
                <x-secondary-button type="button" id="cost-form-cancel-btn-{{ $character->id }}" style="display: none;">
                    キャンセル
                </x-secondary-button>
                <button type="submit" id="cost-form-submit-btn-{{ $character->id }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                    <i class="fas fa-plus"></i> <span class="ml-2" id="cost-form-submit-btn-text-{{ $character->id }}">追加</span>
                </button>
            </div>
            <div id="cost-form-errors-{{ $character->id }}" class="sm:col-span-2 text-sm text-red-600 space-y-1 mt-1"></div>
        </form>
    </div>
    @endcan
</div>
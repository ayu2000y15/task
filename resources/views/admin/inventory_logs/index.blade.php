@extends('layouts.app')

@section('title', '在庫変動ログ')

@push('styles')
    <style>
        /* 必要に応じてこのビュー専用のスタイルを追加 */
        .change-positive {
            @apply text-green-600 dark:text-green-400;
        }

        .change-negative {
            @apply text-red-600 dark:text-red-400;
        }

        /* ★ 備考欄の表示を改善するためのスタイル */
        .notes-cell {
            max-width: 250px;
            /* 備考欄の最大幅を設定 */
            word-break: break-all;
            /* 長い単語でも折り返すようにする */
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ count(array_filter(request()->except('page'))) > 0 ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">在庫変動ログ一覧</h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
                <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}">
                    <i class="fas fa-boxes mr-2"></i>在庫一覧へ戻る
                </x-secondary-button>
            </div>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.inventory-logs.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="filter_inventory_item_name" value="在庫品目名" />
                        <x-text-input id="filter_inventory_item_name" name="inventory_item_name" type="text"
                            class="mt-1 block w-full" :value="request('inventory_item_name')" placeholder="品名で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_user_name" value="操作者名" />
                        <x-text-input id="filter_user_name" name="user_name" type="text" class="mt-1 block w-full"
                            :value="request('user_name')" placeholder="操作者名で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_change_type" value="変動種別" />
                        {{-- ★★★ ここを修正 ★★★ --}}
                        <x-select-input id="filter_change_type" name="change_type" class="mt-1 block w-full"
                            :emptyOptionText="'すべての種別'" :options="$changeTypesForFilter" {{-- ★ :options プロパティとして渡す --}}
                            :selected="request('change_type')" {{-- ★ :selected プロパティとして渡す --}} />
                        {{-- @foreach ループは不要になる --}}
                    </div>
                    <div>
                        <x-input-label for="filter_date_from" value="日時 (From)" />
                        <x-text-input id="filter_date_from" name="date_from" type="date" class="mt-1 block w-full"
                            :value="request('date_from')" />
                    </div>
                    <div>
                        <x-input-label for="filter_date_to" value="日時 (To)" />
                        <x-text-input id="filter_date_to" name="date_to" type="date" class="mt-1 block w-full"
                            :value="request('date_to')" />
                    </div>
                    <div>
                        <x-input-label for="filter_related_stock_order_id" value="関連発注ID" />
                        <x-text-input id="filter_related_stock_order_id" name="related_stock_order_id" type="number"
                            class="mt-1 block w-full" :value="request('related_stock_order_id')" placeholder="発注ID" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.inventory-logs.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        リセット
                    </a>
                    <x-primary-button type="submit">
                        <i class="fas fa-search mr-2"></i> 絞り込む
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- ... (テーブル表示部分は変更なし) ... --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                品名</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作者</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                変動種別</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                変動量</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                変動前在庫</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                変動後在庫</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                変動時単価</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                変動時総額</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                関連情報</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                備考</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($inventoryLogs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                    title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $log->created_at->setTimezone('Asia/Tokyo')->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @if($log->inventoryItem)
                                        <a href="{{ route('admin.inventory.edit', $log->inventoryItem) }}" class="hover:underline"
                                            title="在庫品目「{{ $log->inventoryItem->name }}」を編集">
                                            {{ $log->inventoryItem->name }}
                                        </a>
                                        <span
                                            class="text-xs text-gray-400 dark:text-gray-500">(ID:{{ $log->inventory_item_id }})</span>
                                    @else
                                        品目情報なし (ID:{{ $log->inventory_item_id }})
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->user->name ?? 'システム' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $changeTypesForFilter[$log->change_type] ?? $log->change_type }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $log->quantity_change > 0 ? 'change-positive' : ($log->quantity_change < 0 ? 'change-negative' : '') }}">
                                    {{ $log->quantity_change > 0 ? '+' : '' }}{{ number_format($log->quantity_change, optional($log->inventoryItem)->unit === 'm' || optional($log->inventoryItem)->unit === 'M' ? 1 : 0) }}
                                    {{ optional($log->inventoryItem)->unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    {{ number_format($log->quantity_before_change, optional($log->inventoryItem)->unit === 'm' || optional($log->inventoryItem)->unit === 'M' ? 1 : 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 text-right">
                                    {{ number_format($log->quantity_after_change, optional($log->inventoryItem)->unit === 'm' || optional($log->inventoryItem)->unit === 'M' ? 1 : 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    {{ $log->unit_price_at_change !== null ? number_format($log->unit_price_at_change, 2) . ' 円' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    {{ $log->total_price_at_change !== null ? number_format($log->total_price_at_change, 0) . ' 円' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($log->related_material_id)
                                        材料ID: {{ $log->related_material_id }}
                                    @endif
                                    @if($log->related_stock_order_id)
                                        <a href="{{ route('admin.stock-orders.show', $log->related_stock_order_id) }}"
                                            class="hover:underline text-blue-600 dark:text-blue-400">発注ID:
                                            {{ $log->related_stock_order_id }}</a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-normal notes-cell">
                                    {{ $log->notes }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-exchange-alt fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                        <span>在庫変動ログはありません。</span>
                                        @if(count(array_filter(request()->except('page'))) > 0)
                                            <p class="mt-1">絞り込み条件を変更してみてください。</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($inventoryLogs->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $inventoryLogs->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
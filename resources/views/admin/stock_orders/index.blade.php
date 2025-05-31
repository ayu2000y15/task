@extends('layouts.app')

@section('title', '在庫発注申請一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ count(array_filter(request()->except('page'))) > 0 ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">在庫発注申請一覧</h1>
            <div class="flex space-x-2">
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
                @can('create', App\Models\StockOrder::class)
                    <x-primary-button as="a" href="{{ route('admin.stock-orders.create') }}">
                        <i class="fas fa-plus mr-2"></i>新規発注申請
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.stock-orders.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="filter_inventory_item_name" value="品名" />
                        <x-text-input id="filter_inventory_item_name" name="inventory_item_name" type="text"
                            class="mt-1 block w-full" :value="request('inventory_item_name')" placeholder="品名で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_requested_by_user_name" value="申請者" />
                        <x-text-input id="filter_requested_by_user_name" name="requested_by_user_name" type="text"
                            class="mt-1 block w-full" :value="request('requested_by_user_name')" placeholder="申請者名で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_status" value="ステータス" />
                        <x-select-input id="filter_status" name="status" class="mt-1 block w-full"
                            :emptyOptionText="'すべてのステータス'"
                            :options="$statusOptions"
                            :selected="request('status')"
                            />
                    </div>
                    <div>
                        <x-input-label for="filter_date_from" value="申請日 (From)" />
                        <x-text-input id="filter_date_from" name="date_from" type="date" class="mt-1 block w-full"
                            :value="request('date_from')" />
                    </div>
                    <div>
                        <x-input-label for="filter_date_to" value="申請日 (To)" />
                        <x-text-input id="filter_date_to" name="date_to" type="date" class="mt-1 block w-full"
                            :value="request('date_to')" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.stock-orders.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        リセット
                    </a>
                    <x-primary-button type="submit">
                        <i class="fas fa-search mr-2"></i> 絞り込む
                    </x-primary-button>
                </div>
            </form>
        </div>


        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">申請ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">品名</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">申請数</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">申請者</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ステータス</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">申請日</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">希望/予定納期</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($stockOrders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $order->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @if($order->inventoryItem)
                                        <a href="{{ route('admin.inventory.edit', $order->inventoryItem) }}" class="hover:underline" title="在庫品目「{{ $order->inventoryItem->name }}」を編集">
                                            {{ $order->inventoryItem->name }}
                                        </a>
                                    @else
                                        品目情報なし
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    @php $decimals = (optional($order->inventoryItem)->unit === 'm' || optional($order->inventoryItem)->unit === 'M') ? 1 : 0; @endphp
                                    {{ number_format($order->quantity_requested, $decimals) }} {{ optional($order->inventoryItem)->unit ?? '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $order->requestedByUser->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{-- ★★★ ステータスバッジ表示を show.blade.php と同様に修正 ★★★ --}}
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @switch($order->status)
                                            @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 @break
                                            @case('approved') bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100 @break
                                            @case('ordered') bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100 @break
                                            @case('partially_received') bg-purple-100 text-purple-800 dark:bg-purple-700 dark:text-purple-100 @break
                                            @case('received') bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100 @break
                                            @case('rejected') bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100 @break
                                            @case('cancelled') bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-100 @break
                                            @default bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100
                                        @endswitch
                                    ">
                                        {{ $statusOptions[$order->status] ?? $order->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" title="{{ $order->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $order->created_at->format('Y/m/d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('Y/m/d') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1">
                                    @can('view', $order)
                                        <x-secondary-button as="a" :href="route('admin.stock-orders.show', $order)" class="py-1 px-3">
                                            詳細確認
                                        </x-secondary-button>
                                    @endcan
                                    @can('delete', $order)
                                        @if(in_array($order->status, ['pending', 'rejected', 'cancelled']))
                                            <form action="{{ route('admin.stock-orders.destroy', $order) }}" method="POST"
                                                class="inline-block" onsubmit="return confirm('本当にこの申請を削除しますか？');">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button type="submit" class="py-1 px-2">
                                                    <i class="fas fa-trash"></i>
                                                </x-danger-button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-file-invoice-dollar fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                        <span>発注申請はありません。</span>
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
            @if ($stockOrders->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $stockOrders->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
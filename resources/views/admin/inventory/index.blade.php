@extends('layouts.app')

@section('title', '在庫管理')

@push('styles')
    <style>
        .supplier-cell a {
            @apply text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline;
        }

        .supplier-cell p {
            margin-bottom: 0.25rem;
        }

        .supplier-cell p:last-child {
            margin-bottom: 0;
        }

        .inventory-table-text {
            @apply text-gray-700 dark:text-gray-300;
        }

        .inventory-table-subtext {
            @apply text-gray-500 dark:text-gray-400;
        }

        /* 警告アイコン用のスタイル */
        .warning-icon-low {
            @apply text-yellow-500 dark:text-yellow-400;
        }

        .warning-icon-out {
            @apply text-red-500 dark:text-red-400;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ count(array_filter(request()->except('page'))) > 0 ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">在庫品目一覧</h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
                @can('viewAny', App\Models\InventoryLog::class)
                    <x-secondary-button as="a" href="{{ route('admin.inventory-logs.index') }}">
                        <i class="fas fa-history mr-2"></i>在庫変動ログ
                    </x-secondary-button>
                @endcan
                @can('create', App\Models\InventoryItem::class)
                    <x-primary-button as="a" href="{{ route('admin.inventory.create') }}">
                        <i class="fas fa-plus mr-2"></i>新規在庫品目登録
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.inventory.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="filter_item_name" value="品名" />
                        <x-text-input id="filter_item_name" name="inventory_item_name" type="text" class="mt-1 block w-full"
                            :value="request('inventory_item_name')" placeholder="品名で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_product_number" value="品番" />
                        <x-text-input id="filter_product_number" name="product_number" type="text" class="mt-1 block w-full"
                            :value="request('product_number')" placeholder="品番で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_color_number" value="色番" />
                        <x-text-input id="filter_color_number" name="color_number" type="text" class="mt-1 block w-full"
                            :value="request('color_number')" placeholder="色番で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_supplier" value="仕入先" />
                        <x-text-input id="filter_supplier" name="supplier" type="text" class="mt-1 block w-full"
                            :value="request('supplier')" placeholder="仕入先で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_stock_status" value="在庫状況" />
                        <x-select-input id="filter_stock_status" name="stock_status" class="mt-1 block w-full"
                            :emptyOptionText="'すべて'">
                            <option value="ok" @if(request('stock_status') == 'ok') selected @endif>在庫あり</option>
                            <option value="low" @if(request('stock_status') == 'low') selected @endif>在庫僅少(発注点以下)</option>
                            <option value="out" @if(request('stock_status') == 'out') selected @endif>在庫切れ</option>
                        </x-select-input>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.inventory.index') }}"
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
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 inventory-table">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                品名 (品番/色番)</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                現在在庫数</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                単位</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                最小在庫数</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                仕入先</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                平均単価</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($inventoryItems as $item)
                            @php
                                $isLowStock = $item->quantity < $item->minimum_stock_level && $item->minimum_stock_level > 0;
                                $isOutOfStock = $item->quantity <= 0 && $item->minimum_stock_level > 0;
                                $rowHighlightClass = '';
                                if ($isOutOfStock) {
                                    $rowHighlightClass = 'bg-red-100 dark:bg-red-800/40';
                                } elseif ($isLowStock) {
                                    $rowHighlightClass = 'bg-yellow-50 dark:bg-yellow-700/30';
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $rowHighlightClass }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm inventory-table-subtext">{{ $item->id }}
                                    @if($isOutOfStock)
                                        <i class="fas fa-exclamation-circle warning-icon-out mr-1"
                                            title="在庫切れ (発注点 {{ $item->minimum_stock_level }}{{ $item->unit }})"></i>
                                    @elseif($isLowStock)
                                        <i class="fas fa-exclamation-triangle warning-icon-low mr-1"
                                            title="在庫僅少 (発注点 {{ $item->minimum_stock_level }}{{ $item->unit }})"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <div class="font-medium text-gray-900 dark:text-white text-lg">{{ $item->name }}</div>
                                    @if($item->product_number || $item->color_number)
                                        <p class="text-xs inventory-table-subtext">
                                            {{ $item->product_number ?? '品番なし' }} / {{ $item->color_number ?? '色番なし' }}
                                        </p>
                                    @endif
                                    @if($item->description)
                                        <p class="text-xs inventory-table-subtext truncate" title="{{ $item->description }}">
                                            {!! Str::limit(nl2br($item->description), 30) !!}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm inventory-table-text text-right">
                                    @php $decimals = ($item->unit === 'm' || $item->unit === 'M') ? 1 : 0; @endphp
                                    {{ number_format($item->quantity, $decimals) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm inventory-table-subtext">
                                    {{ $item->unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm inventory-table-subtext text-right">
                                    {{ number_format($item->minimum_stock_level, $decimals) }}
                                </td>
                                <td class="px-6 py-4 text-sm supplier-cell inventory-table-subtext">
                                    @if($item->supplier)
                                        @php
                                            $supplierLines = explode("\n", e($item->supplier));
                                        @endphp
                                        @foreach($supplierLines as $line)
                                            <p>
                                                @if(preg_match('/^(https?:\/\/|www\.)\S+/i', trim($line)))
                                                    <a href="{{ (Str::startsWith(trim($line), 'www.') ? 'http://' : '') . trim($line) }}"
                                                        target="_blank" rel="noopener noreferrer">
                                                        {{ trim($line) }}
                                                    </a>
                                                @else
                                                    {{ $line }}
                                                @endif
                                            </p>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm inventory-table-subtext text-right">
                                    {{ $item->quantity > 0 ? number_format($item->average_unit_price, 2) . ' 円' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @can('update', $item)
                                        <x-secondary-button as="a" href="{{ route('admin.inventory.edit', $item) }}"
                                            class="py-1 px-3">
                                            在庫編集
                                        </x-secondary-button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-box-open fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                        <span>在庫品目が登録されていません。</span>
                                        @can('create', App\Models\InventoryItem::class)
                                            <p class="mt-1">右上の「新規在庫品目登録」ボタンから追加してください。</p>
                                        @endcan
                                        @if(count(array_filter(request()->except('page'))) > 0)
                                            <p class="mt-1">絞り込み条件に一致する在庫品目がありません。</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($inventoryItems->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $inventoryItems->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
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

        /* Alpine.js x-cloak */
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
                filtersOpen: {{ count(array_filter(request()->except('page'))) > 0 ? 'true' : 'false' }},
                selectedItems: [],
                selectAll: false,
                currentPage: 1,
                lastPage: {{ $inventoryItems->lastPage() }},
                loading: false,
                observer: null,
                toggleAll() {
                    this.selectedItems = this.selectAll ? Array.from(document.querySelectorAll('input[name=\'item_ids[]\']:not(:disabled)')).map(cb => parseInt(cb.value)) : [];
                },
                updateSelectAll() {
                    const checkboxes = document.querySelectorAll('input[name=\'item_ids[]\']:not(:disabled)');
                    this.selectAll = checkboxes.length > 0 && this.selectedItems.length === checkboxes.length;
                },
                async loadMore() {
                    if (this.loading || this.currentPage >= this.lastPage) return;

                    this.loading = true;
                    this.currentPage++;

                    try {
                        const params = new URLSearchParams(window.location.search);
                        params.set('page', this.currentPage);

                        const response = await fetch(`{{ route('admin.inventory.index') }}?${params.toString()}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        const tbody = document.querySelector('.inventory-table tbody');
                        const emptyRow = tbody.querySelector('tr td[colspan]')?.parentElement;
                        if (emptyRow) emptyRow.remove();

                        tbody.insertAdjacentHTML('beforeend', data.html);
                        this.lastPage = data.last_page;

                        this.$nextTick(() => {
                            if (this.observer && this.currentPage < this.lastPage) {
                                const trigger = document.getElementById('load-more-trigger');
                                if (trigger) this.observer.observe(trigger);
                            }
                        });
                    } catch (error) {
                        console.error('Error loading more items:', error);
                        this.currentPage--;
                    } finally {
                        this.loading = false;
                    }
                },
                initIntersectionObserver() {
                    this.observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMore();
                            }
                        });
                    }, { threshold: 0.1 });

                    this.$nextTick(() => {
                        const trigger = document.getElementById('load-more-trigger');
                        if (trigger && this.currentPage < this.lastPage) {
                            this.observer.observe(trigger);
                        }
                    });
                }
            }" x-init="initIntersectionObserver()">
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
                    {{-- <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">在庫管理</h1> --}}
                    <div class="flex space-x-2">
                        <x-primary-button as="a" href="{{ route('admin.inventory.create') }}">
                            <i class="fas fa-plus mr-2"></i> 新規登録
                        </x-primary-button>
                        <x-secondary-button as="a" href="{{ route('admin.inventory.bulk-create') }}"
                            class="!bg-green-600 hover:!bg-green-700 dark:!bg-green-700 dark:hover:!bg-green-800 !text-white !border-transparent">
                            <i class="fas fa-layer-group mr-2"></i> 一括登録
                        </x-secondary-button>
                    </div>
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
                    <div>
                        <x-input-label for="filter_active_status" value="有効状態" />
                        <x-select-input id="filter_active_status" name="active_status" class="mt-1 block w-full">
                            <option value="active" @if(request('active_status', 'active') == 'active') selected @endif>有効のみ
                            </option>
                            <option value="inactive" @if(request('active_status') == 'inactive') selected @endif>無効のみ</option>
                            <option value="all" @if(request('active_status') == 'all') selected @endif>全て</option>
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

        {{-- 一括操作ボタン --}}
        <div x-show="selectedItems.length > 0" x-cloak
            class="mb-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span x-text="selectedItems.length"></span> 件選択中
                </span>
                <div class="flex items-center space-x-2">
                    <form method="POST" action="{{ route('admin.inventory.bulk-deactivate') }}"
                        onsubmit="return confirm('選択した ' + document.querySelector('[x-text=\"
                        selectedItems.length\"]').textContent + ' 件の在庫品目を無効にしますか？' );" x-ref="bulkForm">
                        @csrf
                        <template x-for="id in selectedItems" :key="id">
                            <input type="hidden" name="item_ids[]" :value="id">
                        </template>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            <i class="fas fa-ban mr-2"></i>選択項目を無効にする
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 inventory-table">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-center">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                品名 [品番/色番]</th>
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
                        @if($inventoryItems->count() > 0)
                            @include('admin.inventory.partials.inventory-rows', ['inventoryItems' => $inventoryItems])
                        @else
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
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
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- 読み込み中表示 --}}
            <div x-show="loading" class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <div class="inline-flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-3 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400">読み込み中...</span>
                </div>
            </div>

            {{-- もっと見るボタン --}}
            <div x-show="currentPage < lastPage && !loading"
                class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <button @click="loadMore()"
                    class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-chevron-down mr-2"></i>
                    もっと見る
                </button>
            </div>

            {{-- 自動読み込みトリガー --}}
            <div id="load-more-trigger" class="h-1"></div>
        </div>
    </div>
@endsection
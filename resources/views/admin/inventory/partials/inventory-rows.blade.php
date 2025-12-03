@foreach ($inventoryItems as $item)
    @php
        $isLowStock = $item->quantity < $item->minimum_stock_level && $item->minimum_stock_level > 0;
        $isOutOfStock = $item->quantity <= 0 && $item->minimum_stock_level > 0;
        $rowHighlightClass = '';

        // 無効な品目の場合はグレー表示（最優先）
        if (!$item->is_active) {
            $rowHighlightClass = 'bg-gray-200 dark:bg-gray-700 opacity-60';
        } elseif ($isOutOfStock) {
            $rowHighlightClass = 'bg-red-100 dark:bg-red-800/40';
        } elseif ($isLowStock) {
            $rowHighlightClass = 'bg-yellow-50 dark:bg-yellow-700/30';
        }
    @endphp
    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $rowHighlightClass }}">
        <td class="px-4 py-4 text-center">
            <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" x-model="selectedItems"
                @change="updateSelectAll()" {{ !$item->is_active ? 'disabled' : '' }}
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 {{ !$item->is_active ? 'opacity-50 cursor-not-allowed' : '' }}">
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm inventory-table-subtext">{{ $item->id }}
            @if($isOutOfStock)
                <i class="fas fa-exclamation-circle warning-icon-out mr-1"
                    title="在庫切れ (発注点 {{ $item->minimum_stock_level }}{{ $item->unit }})"></i>
            @elseif($isLowStock)
                <i class="fas fa-exclamation-triangle warning-icon-low mr-1"
                    title="在庫僅少 (発注点 {{ $item->minimum_stock_level }}{{ $item->unit }})"></i>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <div class="flex items-center">
                @if($item->image_path)
                    <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}"
                        class="h-12 w-12 flex-shrink-0 rounded object-cover mr-4">
                @else
                    <div
                        class="h-12 w-12 flex-shrink-0 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center mr-4">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>
                @endif
                <div>
                    <div class="font-medium text-gray-900 dark:text-white text-base">
                        {{ $item->name }}
                        @if($item->product_number || $item->color_number)
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                                [ {{ $item->product_number ?? 'なし' }} / {{ $item->color_number ?? 'なし' }} ]
                            </span>
                        @endif
                    </div>
                    @if($item->description)
                        <p class="text-xs inventory-table-subtext truncate" title="{{ $item->description }}">
                            {!! Str::limit(nl2br(e($item->description)), 30) !!}
                        </p>
                    @endif
                </div>
            </div>
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
                            <a class="text-blue-600 hover:underline"
                                href="{{ (Str::startsWith(trim($line), 'www.') ? 'http://' : '') . trim($line) }}" target="_blank"
                                rel="noopener noreferrer">
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
                <x-secondary-button as="a" href="{{ route('admin.inventory.edit', $item) }}" class="py-1 px-3">
                    在庫編集
                </x-secondary-button>
            @endcan
        </td>
    </tr>
@endforeach
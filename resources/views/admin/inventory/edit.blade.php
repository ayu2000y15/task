@extends('layouts.app')

@section('title', '在庫品目編集 - ' . $inventoryItem->name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                在庫品目編集: <span class="font-normal">{{ $inventoryItem->name }}</span>
            </h1>
            <div>
                <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}" class="mr-2">
                    <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
                </x-secondary-button>
                @can('delete', $inventoryItem)
                    <form action="{{ route('admin.inventory.destroy', $inventoryItem) }}" method="POST" class="inline-block"
                        onsubmit="return confirm('この在庫品目を本当に削除しますか？関連する発注申請やログも影響を受ける可能性があります。');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">
                            <i class="fas fa-trash mr-2"></i>削除
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- 在庫操作セクション --}}
        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">在庫操作</h2>
            </div>
            <div class="p-6 space-y-6">
                {{-- 入荷フォーム --}}
                @can('stockIn', $inventoryItem)
                <form action="{{ route('admin.inventory.stockIn', $inventoryItem) }}" method="POST" class="mb-4 pb-4 border-b dark:border-gray-700 last:border-b-0 last:pb-0 last:mb-0">
                    @csrf
                    <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-100">入荷処理</h3>
                    <div
                        class="p-2 mb-4 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        単価または総額を入力してください。いずれかが入力されていれば単価の計算ができます。
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <x-input-label for="stock_in_quantity" value="入荷数量" :required="true"/>
                            <x-text-input type="number" step="0.01" name="quantity_in" id="stock_in_quantity" class="mt-1 block w-full" min="0.01" required :value="old('quantity_in')"/>
                        </div>
                        <div>
                            <x-input-label for="stock_in_unit_price" value="入荷単価 (税抜など)"/>
                            <x-text-input type="number" step="0.01" name="unit_price_in" id="stock_in_unit_price" class="mt-1 block w-full" min="0" :value="old('unit_price_in')" placeholder="単価を入力"/>
                        </div>
                        <div>
                            <x-input-label for="stock_in_total_price" value="入荷総額 (税抜など)"/>
                            <x-text-input type="number" step="0.01" name="total_price_in" id="stock_in_total_price" class="mt-1 block w-full" min="0" :value="old('total_price_in')" placeholder="総額を入力"/>
                        </div>
                        <div class="lg:col-span-2">
                            <x-input-label for="stock_in_notes" value="備考 (発注IDなど)"/>
                            <x-text-input type="text" name="notes" id="stock_in_notes" class="mt-1 block w-full" :value="old('notes')" />
                        </div>
                        <div class="lg:col-start-4">
                            <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 w-full justify-center sm:w-auto">
                                <i class="fas fa-plus-circle mr-2"></i>入荷を記録
                            </x-primary-button>
                        </div>
                    </div>
                    @if($errors->stockIn->any())
                        <div class="mt-3 text-sm text-red-600 dark:text-red-400">
                            <ul>
                                @foreach ($errors->stockIn->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </form>
                @endcan

                {{-- 在庫調整フォーム --}}
                @can('adjustStock', $inventoryItem)
                <form action="{{ route('admin.inventory.adjustStock', $inventoryItem) }}" method="POST"
                    x-data="{
                        adjustmentType: '{{ old('adjustment_type', 'new_total') }}',
                        currentQuantity: {{ $inventoryItem->quantity }},
                        inputLabel: '調整後の総在庫数',
                        updateLabel() {
                            if (this.adjustmentType === 'new_total') {
                                this.inputLabel = '調整後の総在庫数';
                            } else {
                                this.inputLabel = '在庫の増減数 (+/-)';
                            }
                        }
                    }"
                    x-init="updateLabel()">
                    @csrf
                    <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-100">在庫調整</h3>
                    <div class="mb-4 space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">調整方法:</label>
                        <div class="flex flex-wrap gap-x-4 gap-y-2">
                            <div class="flex items-center">
                                <input type="radio" id="adjust_type_total" name="adjustment_type_selector_display" value="new_total"
                                    class="form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500"
                                    x-model="adjustmentType" @change="updateLabel()">
                                <label for="adjust_type_total" class="ml-2 text-sm text-gray-700 dark:text-gray-300">調整後の総在庫数を指定</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" id="adjust_type_change" name="adjustment_type_selector_display" value="change_amount"
                                    class="form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500"
                                    x-model="adjustmentType" @change="updateLabel()">
                                <label for="adjust_type_change" class="ml-2 text-sm text-gray-700 dark:text-gray-300">増減した数量を指定</label>
                            </div>
                        </div>
                        <input type="hidden" name="adjustment_type" :value="adjustmentType">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="adjust_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300" x-text="inputLabel + ' *'"></label>
                            <x-text-input type="number" step="0.01" name="adjustment_value" id="adjust_value" class="mt-1 block w-full"
                                required
                                :value="old('adjustment_value', (old('adjustment_type', 'new_total') === 'new_total' ? number_format($inventoryItem->quantity, ($inventoryItem->unit === 'm' || $inventoryItem->unit === 'M') ? 1 : 0, '.', '') : '0'))"
                                x-bind:placeholder="adjustmentType === 'new_total' ? '例: 100' : '例: +10 または -5'"
                                />
                        </div>
                        <div>
                            <x-input-label for="adjust_notes" value="調整理由" :required="true"/>
                            <x-text-input type="text" name="notes" id="adjust_notes" class="mt-1 block w-full" required placeholder="例: 棚卸差異、破損" :value="old('notes')" />
                        </div>
                        <x-primary-button type="submit" class="bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700">
                            <i class="fas fa-pencil-alt mr-2"></i>在庫を調整
                        </x-primary-button>
                    </div>
                    @if($errors->adjustStock->any())
                        <div class="mt-3 text-sm text-red-600 dark:text-red-400">
                            <ul>
                                @foreach ($errors->adjustStock->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </form>
                @endcan
            </div>
        </div>

        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">品目情報編集</h2>
            </div>
            <form action="{{ route('admin.inventory.update', $inventoryItem) }}" method="POST" enctype="multipart/form-data" x-data="imageEditor({ currentImageUrl: '{{ $inventoryItem->image_path ? Storage::url($inventoryItem->image_path) : '' }}' })">
                @csrf
                @method('PUT')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <x-input-label for="name" value="品名" :required="true" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            :value="old('name', $inventoryItem->name)" required :hasError="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="description" value="説明" />
                        <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3"
                            :hasError="$errors->has('description')">{{ old('description', $inventoryItem->description) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="product_number" value="品番" />
                            <x-text-input id="product_number" name="product_number" type="text" class="mt-1 block w-full" :value="old('product_number', $inventoryItem->product_number)" :hasError="$errors->has('product_number')" />
                            <x-input-error :messages="$errors->get('product_number')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="color_number" value="色番" />
                            <x-text-input id="color_number" name="color_number" type="text" class="mt-1 block w-full" :value="old('color_number', $inventoryItem->color_number)" :hasError="$errors->has('color_number')" />
                            <x-input-error :messages="$errors->get('color_number')" class="mt-2" />
                        </div>
                    </div>

                    {{-- 画像プレビューと入力欄 --}}
                    <div>
                        <x-input-label for="image_path" value="画像" />
                        <input type="file" id="image_path" name="image_path" @change="handleFileSelect" class="mt-2 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-200 dark:file:bg-gray-600 file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-300 dark:hover:file:bg-gray-500">
                        <div class="mt-2" x-show="previewUrl">
                            <img :src="previewUrl" alt="画像プレビュー" class="h-32 w-32 object-cover rounded-md border border-gray-200 dark:border-gray-600">
                        </div>
                        @if($inventoryItem->image_path)
                            <div class="mt-2 flex items-center">
                                <input type="checkbox" name="remove_image" id="remove_image" @change="previewUrl = $event.target.checked ? '' : '{{ Storage::url($inventoryItem->image_path) }}'" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="remove_image" class="ml-2 text-sm text-gray-900 dark:text-gray-300">現在の画像を削除する</label>
                            </div>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">新しい画像をアップロードすると、既存の画像は上書きされます。</p>
                        <x-input-error :messages="$errors->get('image_path')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="unit" value="単位" :required="true" />
                            <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full"
                                :value="old('unit', $inventoryItem->unit)" required :hasError="$errors->has('unit')" placeholder="例: m, 個, 袋, 箱" />
                            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="quantity_display_info" value="現在の在庫数" />
                            <x-text-input id="quantity_display_info" name="quantity_display_info" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                                :value="number_format($inventoryItem->quantity, ($inventoryItem->unit === 'm' || $inventoryItem->unit === 'M') ? 1 : 0)"
                                disabled />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">在庫数は在庫操作から変更します。</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                        <div>
                            <x-input-label for="total_cost" value="現在の在庫総コスト (税抜など)" />
                            <x-text-input id="total_cost" name="total_cost" type="number" step="0.01" class="mt-1 block w-full"
                                :value="old('total_cost', number_format($inventoryItem->total_cost, 2, '.', ''))"
                                :hasError="$errors->has('total_cost')" placeholder="現在の在庫全体の仕入れ値など" />
                            <x-input-error :messages="$errors->get('total_cost')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">現在の在庫の総原価です。入荷・調整時に自動更新されますが、手動での修正も可能です。</p>
                        </div>
                        <div>
                            <x-input-label for="average_unit_price_display" value="平均単価 (参考)" />
                            <x-text-input id="average_unit_price_display" name="average_unit_price_display" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                                :value="number_format($inventoryItem->average_unit_price, 2) . ' 円/' . $inventoryItem->unit"
                                disabled />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">総コスト ÷ 在庫数。自動計算されます。</p>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="minimum_stock_level" value="最小在庫数 (発注点)" :required="true" />
                        <x-text-input id="minimum_stock_level" name="minimum_stock_level" type="number" step="0.01" class="mt-1 block w-full"
                            :value="old('minimum_stock_level', number_format($inventoryItem->minimum_stock_level, ($inventoryItem->unit === 'm' || $inventoryItem->unit === 'M') ? 1 : 0, '.', ''))"
                             required :hasError="$errors->has('minimum_stock_level')" />
                        <x-input-error :messages="$errors->get('minimum_stock_level')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="supplier" value="仕入先 (URL可・複数行入力可)" />
                        <x-textarea-input id="supplier" name="supplier" class="mt-1 block w-full" rows="5"
                            :hasError="$errors->has('supplier')"
                            placeholder="例:&#10;〇〇商店&#10;https://example.com/shop&#10;担当:△△様">{{ old('supplier', $inventoryItem->supplier) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('supplier')" class="mt-2" />
                    </div>
                     <div>
                        <x-input-label for="last_stocked_at" value="最終入荷日" />
                        <x-text-input id="last_stocked_at" name="last_stocked_at" type="date" class="mt-1 block w-full"
                            :value="old('last_stocked_at', optional($inventoryItem->last_stocked_at)->format('Y-m-d'))"
                            :hasError="$errors->has('last_stocked_at')" />
                        <x-input-error :messages="$errors->get('last_stocked_at')" class="mt-2" />
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> 更新
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function imageEditor(config) {
        return {
            previewUrl: config.currentImageUrl,
            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    this.previewUrl = URL.createObjectURL(file);
                }
            }
        }
    }
</script>
@endpush

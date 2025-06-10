@extends('layouts.app')

@section('title', '新規在庫品目登録')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">新規在庫品目登録</h1>
            <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <form action="{{ route('admin.inventory.store') }}" method="POST">
                @csrf
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <x-input-label for="name" value="品名" :required="true" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')"
                            required :hasError="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="description" value="説明" />
                        <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3"
                            :hasError="$errors->has('description')">{{ old('description') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="product_number" value="品番" />
                            <x-text-input id="product_number" name="product_number" type="text" class="mt-1 block w-full"
                                :value="old('product_number')" :hasError="$errors->has('product_number')"
                                placeholder="例: ABC-123" />
                            <x-input-error :messages="$errors->get('product_number')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="color_number" value="色番" />
                            <x-text-input id="color_number" name="color_number" type="text" class="mt-1 block w-full"
                                :value="old('color_number')" :hasError="$errors->has('color_number')"
                                placeholder="例: C-01" />
                            <x-input-error :messages="$errors->get('color_number')" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="unit" value="単位" :required="true" />
                            <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', '個')" required :hasError="$errors->has('unit')" placeholder="例: m, 個, 袋, 箱" />
                            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="quantity" value="初期在庫数" :required="true" />
                            <x-text-input id="quantity" name="quantity" type="number" step="0.01" class="mt-1 block w-full"
                                :value="old('quantity', 0)" required :hasError="$errors->has('quantity')" />
                            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                        </div>
                    </div>
                    {{-- ★ 初期在庫総コスト入力欄を追加 --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        <div>
                            <x-input-label for="total_cost" value="初期在庫総コスト (税抜など)" />
                            <x-text-input id="total_cost" name="total_cost" type="number" step="0.01"
                                class="mt-1 block w-full" :value="old('total_cost', 0)"
                                :hasError="$errors->has('total_cost')" placeholder="初期在庫全体の仕入れ値など" />
                            <x-input-error :messages="$errors->get('total_cost')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                初期在庫数に対する総仕入れ値を入力します。これが最初の平均単価の基準になります。
                            </p>
                        </div>
                        <div>
                            <x-input-label for="minimum_stock_level" value="最小在庫数 (発注点)" :required="true" />
                            <x-text-input id="minimum_stock_level" name="minimum_stock_level" type="number" step="0.01"
                                class="mt-1 block w-full" :value="old('minimum_stock_level', 0)" required
                                :hasError="$errors->has('minimum_stock_level')" />
                            <x-input-error :messages="$errors->get('minimum_stock_level')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="supplier" value="仕入先 (URL可・複数行入力可)" />
                        <x-textarea-input id="supplier" name="supplier" class="mt-1 block w-full" rows="5"
                            :hasError="$errors->has('supplier')"
                            placeholder="例:&#10;〇〇商店&#10;https://example.com/shop&#10;担当:△△様">{{ old('supplier') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('supplier')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_stocked_at" value="最終入荷日 (初期登録時)" />
                        <x-text-input id="last_stocked_at" name="last_stocked_at" type="date" class="mt-1 block w-full"
                            :value="old('last_stocked_at')" :hasError="$errors->has('last_stocked_at')" />
                        <x-input-error :messages="$errors->get('last_stocked_at')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">初期在庫を登録する場合、その入荷日も記録できます。</p>
                    </div>
                </div>
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> 登録
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection
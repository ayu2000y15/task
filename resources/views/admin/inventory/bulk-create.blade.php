@extends('layouts.app')

@section('title', '在庫品目一括登録')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">在庫品目一括登録</h1>
            <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">同じ品名で品番・色番違いの商品を一括登録</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    共通の品名、説明、単位、最小在庫数、仕入先を設定し、品番・色番・在庫数・総コストが異なる複数のバリエーションを一度に登録できます。
                </p>
            </div>

            <form action="{{ route('admin.inventory.bulk-store') }}" method="POST" x-data="bulkInventoryForm()">
                @csrf
                <div class="p-6 sm:p-8 space-y-6">
                    {{-- 共通情報 --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                        <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-100">共通情報</h3>
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="base_name" value="品名" :required="true" />
                                <x-text-input id="base_name" name="base_name" type="text" class="mt-1 block w-full"
                                    :value="old('base_name')" required :hasError="$errors->has('base_name')"
                                    placeholder="例: プリント生地" />
                                <x-input-error :messages="$errors->get('base_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="product_number" value="品番" />
                                <x-text-input id="product_number" name="product_number" type="text"
                                    class="mt-1 block w-full" :value="old('product_number')"
                                    :hasError="$errors->has('product_number')" placeholder="例: ABC-123" />
                                <x-input-error :messages="$errors->get('product_number')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="description" value="説明" />
                                <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3"
                                    :hasError="$errors->has('description')"
                                    placeholder="商品の共通説明">{{ old('description') }}</x-textarea-input>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="unit" value="単位" :required="true" />
                                    <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full"
                                        :value="old('unit', 'm')" required :hasError="$errors->has('unit')"
                                        placeholder="例: m, 個, 袋, 箱" />
                                    <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="minimum_stock_level" value="最小在庫数" :required="true" />
                                    <x-text-input id="minimum_stock_level" name="minimum_stock_level" type="number"
                                        step="0.01" class="mt-1 block w-full" :value="old('minimum_stock_level', 10)"
                                        required :hasError="$errors->has('minimum_stock_level')" />
                                    <x-input-error :messages="$errors->get('minimum_stock_level')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="last_stocked_at" value="入荷日" />
                                    <x-text-input id="last_stocked_at" name="last_stocked_at" type="date"
                                        class="mt-1 block w-full" :value="old('last_stocked_at')"
                                        :hasError="$errors->has('last_stocked_at')" />
                                    <x-input-error :messages="$errors->get('last_stocked_at')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="supplier" value="仕入先" />
                                <x-textarea-input id="supplier" name="supplier" class="mt-1 block w-full" rows="3"
                                    :hasError="$errors->has('supplier')"
                                    placeholder="例:&#10;〇〇商店&#10;https://example.com/shop&#10;担当:△△様">{{ old('supplier') }}</x-textarea-input>
                                <x-input-error :messages="$errors->get('supplier')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- バリエーション --}}
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">バリエーション</h3>
                            <div class="space-x-2">
                                <x-secondary-button type="button" @click="addVariant()" x-show="variants.length < 20">
                                    <i class="fas fa-plus mr-2"></i>追加
                                </x-secondary-button>
                                <span class="text-xs text-gray-500" x-text="`${variants.length}/20件`"></span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(variant, index) in variants" :key="index">
                                <div
                                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-medium text-gray-700 dark:text-gray-300"
                                            x-text="`バリエーション ${index + 1}`"></h4>
                                        <button type="button" @click="removeVariant(index)" x-show="variants.length > 1"
                                            class="inline-flex items-center px-2 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300"
                                                x-bind:for="`variant_color_number_${index}`">色番</label>
                                            <input type="text" x-bind:id="`variant_color_number_${index}`"
                                                x-bind:name="`variants[${index}][color_number]`"
                                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full"
                                                x-model="variant.color_number" placeholder="例: C-01" />
                                        </div>

                                        <div>
                                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300"
                                                x-bind:for="`variant_quantity_${index}`">在庫数 <span
                                                    class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" x-bind:id="`variant_quantity_${index}`"
                                                x-bind:name="`variants[${index}][quantity]`"
                                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full"
                                                x-model="variant.quantity" required min="0" />
                                        </div>

                                        <div>
                                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300"
                                                x-bind:for="`variant_total_cost_${index}`">総コスト</label>
                                            <input type="number" step="0.01" x-bind:id="`variant_total_cost_${index}`"
                                                x-bind:name="`variants[${index}][total_cost]`"
                                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full"
                                                x-model="variant.total_cost" min="0" placeholder="仕入れ値など" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- バリデーションエラー表示 --}}
                        @if($errors->any())
                            <div
                                class="mt-4 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 rounded-lg">
                                <h4 class="font-medium text-red-800 dark:text-red-200 mb-2">入力エラーがあります:</h4>
                                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('admin.inventory.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> 一括登録
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bulkInventoryForm() {
            return {
                variants: {!! json_encode(old('variants', [
        ['color_number' => '', 'quantity' => 0, 'total_cost' => 0]
    ])) !!},

                addVariant() {
                    if (this.variants.length < 20) {
                        this.variants.push({
                            product_number: '',
                            color_number: '',
                            quantity: 0,
                            total_cost: 0
                        });
                    }
                },

                removeVariant(index) {
                    if (this.variants.length > 1) {
                        this.variants.splice(index, 1);
                    }
                }
            }
        }
    </script>
@endsection
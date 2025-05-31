@extends('layouts.app')

@section('title', '新規在庫発注申請')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">新規在庫発注申請</h1>
            <x-secondary-button as="a" href="{{ route('admin.stock-orders.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <form action="{{ route('admin.stock-orders.store') }}" method="POST">
                @csrf
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <x-input-label for="inventory_item_id" value="対象在庫品目" :required="true" />
                        <select name="inventory_item_id" id="inventory_item_id"
                            class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('inventory_item_id') border-red-500 @enderror"
                            required>
                            <option value="">選択してください</option>
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" {{ old('inventory_item_id', optional($inventoryItem)->id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }} (現在庫: {{-- ★ ここを修正 --}}
                                    @php
                                        // 単位に応じて小数点以下の桁数を決定 (例: 'm' なら1桁、他は0桁)
                                        $decimals = ($item->unit === 'm' || $item->unit === 'M') ? 1 : 0;
                                    @endphp
                                    {{ number_format($item->quantity, $decimals) }} {{ $item->unit }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('inventory_item_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="quantity_requested" value="申請数量" :required="true" />
                        <x-text-input id="quantity_requested" name="quantity_requested" type="number" step="0.01"
                            class="mt-1 block w-full" :value="old('quantity_requested')" required min="0.01"
                            :hasError="$errors->has('quantity_requested')" />
                        <x-input-error :messages="$errors->get('quantity_requested')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="expected_delivery_date" value="希望納品日 (任意)" />
                        <x-text-input id="expected_delivery_date" name="expected_delivery_date" type="date"
                            class="mt-1 block w-full" :value="old('expected_delivery_date')"
                            :hasError="$errors->has('expected_delivery_date')" />
                        <x-input-error :messages="$errors->get('expected_delivery_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="備考 (申請理由など)" />
                        <x-textarea-input id="notes" name="notes" class="mt-1 block w-full" rows="3"
                            :hasError="$errors->has('notes')">{{ old('notes') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                </div>
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('admin.stock-orders.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-paper-plane mr-2"></i> 申請する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection
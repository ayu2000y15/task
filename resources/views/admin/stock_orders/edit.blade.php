@extends('layouts.app')

@section('title', '発注申請編集 - ID: ' . $stockOrder->id)

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
            発注申請編集 - ID: {{ $stockOrder->id }}
            <span class="px-2 ml-2 inline-flex text-sm leading-5 font-semibold rounded-full
                @switch($stockOrder->status)
                    @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 @break
                    @case('approved') bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100 @break
                    @case('ordered') bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100 @break
                    @case('received') bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100 @break
                    @case('rejected')
                    @case('cancelled') bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100 @break
                    @default bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100
                @endswitch
            ">
                {{ \App\Models\StockOrder::STATUS_OPTIONS[$stockOrder->status] ?? $stockOrder->status }}
            </span>
        </h1>
        <x-secondary-button as="a" href="{{ route('admin.stock-orders.show', $stockOrder) }}">
            <i class="fas fa-arrow-left mr-2"></i> 詳細へ戻る
        </x-secondary-button>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <form action="{{ route('admin.stock-orders.update', $stockOrder) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">申請品目: {{ $stockOrder->inventoryItem->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">申請数量: {{ $stockOrder->quantity_requested }} {{ $stockOrder->inventoryItem->unit }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">申請者: {{ $stockOrder->requestedByUser->name ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">申請日時: {{ $stockOrder->created_at->format('Y/m/d H:i') }}</p>
                </div>
                <hr class="dark:border-gray-700">

                <div>
                    <x-input-label for="expected_delivery_date" value="希望/予定納品日" />
                    <x-text-input id="expected_delivery_date" name="expected_delivery_date" type="date" class="mt-1 block w-full"
                        :value="old('expected_delivery_date', optional($stockOrder->expected_delivery_date)->format('Y-m-d'))"
                        :hasError="$errors->has('expected_delivery_date')"
                        {{-- ステータスによっては編集不可にするなどの制御も可能 --}}
                        {{-- :disabled="!in_array($stockOrder->status, ['pending', 'approved', 'ordered'])" --}}
                         />
                    <x-input-error :messages="$errors->get('expected_delivery_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="申請者からの備考" />
                    <x-textarea-input id="notes" name="notes" class="mt-1 block w-full" rows="3"
                        :hasError="$errors->has('notes')"
                        {{-- 申請者本人かつステータスが初期の場合のみ編集可能など --}}
                        {{-- :disabled="!(Auth::id() == $stockOrder->requested_by_user_id && $stockOrder->status === 'pending')" --}}
                        >{{ old('notes', $stockOrder->notes) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                @can('updateStatus', $stockOrder) {{-- 例: 管理者のみ管理者メモを編集可能とする --}}
                <div>
                    <x-input-label for="manager_notes" value="管理者備考" />
                    <x-textarea-input id="manager_notes" name="manager_notes" class="mt-1 block w-full" rows="3"
                        :hasError="$errors->has('manager_notes')">{{ old('manager_notes', $stockOrder->manager_notes) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('manager_notes')" class="mt-2" />
                </div>
                @else
                @if($stockOrder->manager_notes)
                <div>
                    <x-input-label value="管理者備考 (閲覧のみ)" />
                    <div class="mt-1 p-3 bg-gray-100 dark:bg-gray-700/50 rounded-md text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                        {{ $stockOrder->manager_notes }}
                    </div>
                </div>
                @endif
                @endcan

            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                <x-secondary-button as="a" href="{{ route('admin.stock-orders.show', $stockOrder) }}">
                    キャンセル
                </x-secondary-button>
                <x-primary-button type="submit">
                    <i class="fas fa-save mr-2"></i> 更新する
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endsection
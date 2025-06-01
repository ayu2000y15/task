@extends('layouts.app')

@section('title', '発注申請詳細 - ID: ' . $stockOrder->id)

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
            発注申請詳細 - ID: {{ $stockOrder->id }}
            <span class="px-2 py-0.5 ml-2 inline-flex text-xs leading-5 font-semibold rounded-full
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
        <x-secondary-button as="a" href="{{ route('admin.stock-orders.index') }}">
            <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
        </x-secondary-button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- 左側: 各情報カード --}}
        <div class="md:col-span-2 space-y-6">
            {{-- 申請情報カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 dark:border-gray-600 pb-2 mb-4">申請情報</h3>
                    <div class="space-y-0">
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">品名:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $stockOrder->inventoryItem->name ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">申請数量:</strong>
                            <span class="text-gray-900 dark:text-gray-100">
                                @php
                                    $decimals = (optional($stockOrder->inventoryItem)->unit === 'm' || optional($stockOrder->inventoryItem)->unit === 'M') ? 1 : 0;
                                @endphp
                                {{ number_format($stockOrder->quantity_requested, $decimals) }}
                                {{ optional($stockOrder->inventoryItem)->unit ?? '' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">申請者:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $stockOrder->requestedByUser->name ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">申請日時:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $stockOrder->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">希望納品日:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $stockOrder->expected_delivery_date ? $stockOrder->expected_delivery_date->format('Y/m/d') : '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">申請者備考:</strong>
                            <p class="whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-2 rounded-md text-gray-900 dark:text-gray-100">{{ $stockOrder->notes ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 管理情報カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 dark:border-gray-600 pb-2 mb-4">管理情報</h3>
                    <div class="space-y-0">
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-center py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">現在のステータス:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ \App\Models\StockOrder::STATUS_OPTIONS[$stockOrder->status] ?? $stockOrder->status }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-center py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">最終対応者:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $stockOrder->managedByUser->name ?? '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-center py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">最終対応日時:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $stockOrder->managed_at ? $stockOrder->managed_at->format('Y/m/d H:i') : '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">管理者備考:</strong>
                            <p class="whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-2 rounded-md text-gray-900 dark:text-gray-100">{{ $stockOrder->manager_notes ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 右側: ステータス更新フォーム --}}
        <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 border-b border-gray-300 dark:border-gray-600 pb-2">ステータス更新・管理</h3>
                @can('updateStatus', $stockOrder)
                <form action="{{ route('admin.stock-orders.updateStatus', $stockOrder) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="status" value="新しいステータス" :required="true"/>
                            <select name="status" id="status" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('status') border-red-500 @enderror" required>
                                @foreach($statusOptions as $value => $label) {{-- $statusOptions がコントローラーから渡されている前提 --}}
                                    <option value="{{ $value }}" {{ old('status', $stockOrder->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                        <div id="expected_delivery_date_on_status_change_wrapper" {{ old('status', $stockOrder->status) !== 'ordered' ? 'style="display:none;"' : '' }}>
                            <x-input-label for="expected_delivery_date_on_status_change" value="納品予定日 (発注時)" />
                            <x-text-input id="expected_delivery_date_on_status_change" name="expected_delivery_date_on_status_change" type="date" class="mt-1 block w-full"
                                :value="old('expected_delivery_date_on_status_change', optional($stockOrder->expected_delivery_date)->format('Y-m-d'))"
                                :hasError="$errors->has('expected_delivery_date_on_status_change')" />
                            <x-input-error :messages="$errors->get('expected_delivery_date_on_status_change')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="manager_notes" value="管理者備考" />
                            <x-textarea-input id="manager_notes" name="manager_notes" class="mt-1 block w-full" rows="3" :hasError="$errors->has('manager_notes')">{{ old('manager_notes', $stockOrder->manager_notes) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('manager_notes')" class="mt-2" />
                        </div>
                        <x-primary-button type="submit" class="w-full justify-center">
                            <i class="fas fa-sync-alt mr-2"></i>ステータスを更新
                        </x-primary-button>
                    </div>
                </form>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">この申請のステータスを更新する権限がありません。</p>
                @endcan

                @can('update', $stockOrder)
                    <div class="mt-6 pt-4 border-t border-gray-300 dark:border-gray-700">
                         <x-secondary-button as="a" href="{{ route('admin.stock-orders.edit', $stockOrder) }}" class="w-full justify-center">
                            <i class="fas fa-edit mr-2"></i>申請内容を編集
                        </x-secondary-button>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- 画像プレビューモーダルは削除されました --}}

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ステータス更新時の納期表示制御スクリプト
        const statusSelect = document.getElementById('status');
        if (statusSelect) {
            const deliveryDateWrapper = document.getElementById('expected_delivery_date_on_status_change_wrapper');
            function toggleDeliveryDateVisibility() {
                if (deliveryDateWrapper) {
                    if (statusSelect.value === 'ordered') {
                        deliveryDateWrapper.style.display = 'block';
                    } else {
                        deliveryDateWrapper.style.display = 'none';
                    }
                }
            }
            statusSelect.addEventListener('change', toggleDeliveryDateVisibility);
            toggleDeliveryDateVisibility(); // 初期表示のため
        }

        // 画像プレビューモーダルの処理は削除されました
    });
</script>
@endpush
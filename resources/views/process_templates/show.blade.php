@extends('layouts.app')

@section('title', '工程テンプレート編集: ' . $processTemplate->name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">工程テンプレート編集: <span
                    class="font-normal">{{ $processTemplate->name }}</span></h1>
            <x-secondary-button as="a" href="{{ route('process-templates.index') }}">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>一覧へ戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">テンプレート情報</h2>
                </div>
                <div class="p-6">
                    @can('update', App\Models\ProcessTemplate::class)
                        <form action="{{ route('process-templates.update', $processTemplate) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="name" value="テンプレート名" required />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                        :value="old('name', $processTemplate->name)" required />
                                </div>
                                <div>
                                    <x-input-label for="description" value="説明" />
                                    <x-textarea-input id="description" name="description" class="mt-1 block w-full"
                                        rows="3">{{ old('description', $processTemplate->description) }}</x-textarea-input>
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-6">
                                <x-primary-button type="submit">
                                    <i class="fas fa-save mr-2"></i>テンプレート情報を更新
                                </x-primary-button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">工程項目</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr> {{-- テーブルヘッダーは変更なし --}}
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    順序</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    工程名</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    標準工数(日)</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($processTemplate->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->order }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $item->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->default_duration ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @can('delete', arguments: App\Models\ProcessTemplate::class)
                                            <form action="{{ route('process-templates.items.destroy', [$processTemplate, $item]) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <x-icon-button
                                                    icon="fas fa-trash"
                                                    title="削除"
                                                    color="red"
                                                    type="submit"
                                                    :confirm="'本当に削除しますか？'"
                                                />
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        工程項目がありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">工程項目を追加</h3>
                    <form action="{{ route('process-templates.items.store', $processTemplate) }}" method="POST"> @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-start">
                            <div class="sm:col-span-2">
                                <x-input-label for="item_name" value="工程名" required/>
                                <x-text-input id="item_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                                {{-- エラー表示は<x-text-input>が対応する想定 --}}
                            </div>
                            <div>
                                <x-input-label for="item_default_duration" value="標準工数(日)" />
                                <x-text-input id="item_default_duration" name="default_duration" type="number" min="0"
                                    class="mt-1 block w-full" :value="old('default_duration')" />
                            </div>
                            <div>
                                <x-input-label for="item_order" value="順序" required />
                                <x-text-input id="item_order" name="order" type="number" min="0" class="mt-1 block w-full"
                                    :value="old('order', ($processTemplate->items->max('order') ?? -1) + 1)" required />
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-primary-button type="submit">追加</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('layouts.app')

@section('title', 'カスタム項目管理')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">カスタム項目管理</h1>
            @can('create', App\Models\FormFieldDefinition::class)
                <x-primary-button
                    onclick="location.href='{{ route('admin.form-definitions.create', ['category' => $category]) }}'">
                    <i class="fas fa-plus mr-2"></i>新規項目定義を作成
                </x-primary-button>
            @endcan
        </div>

        {{-- タブ切り替え --}}
        <div class="mb-6">
            <nav class="flex space-x-8" aria-label="Tabs">
                @foreach($availableCategories as $key => $label)
                    <a href="{{ route('admin.form-definitions.index', ['category' => $key]) }}"
                        class="py-2 px-1 border-b-2 font-medium text-sm whitespace-nowrap {{ $category === $key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        {{ $label }}
                        @php
                            $count = \App\Models\FormFieldDefinition::category($key)->count();
                        @endphp
                        @if($count > 0)
                            <span
                                class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-medium bg-gray-500 text-white rounded-full dark:bg-gray-600">
                                {{ $count }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                            </th> {{-- ドラッグハンドル用 --}}
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                順序</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ラベル</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                名前 (スラグ)</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                タイプ</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                必須</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                有効</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                        id="sortable-definitions">
                        @forelse($fieldDefinitions as $definition)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" data-id="{{ $definition->id }}">
                                <td
                                    class="px-4 py-4 whitespace-nowrap text-sm text-gray-400 dark:text-gray-500 cursor-move drag-handle text-center">
                                    <i class="fas fa-grip-vertical"></i>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $definition->order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $definition->label }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $definition->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ App\Models\FormFieldDefinition::FIELD_TYPES[$definition->type] ?? $definition->type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if($definition->is_required)
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    @else
                                        <i class="fas fa-times-circle text-gray-400"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if($definition->is_enabled)
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    @else
                                        <i class="fas fa-times-circle text-gray-400"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        @can('update', $definition)
                                            <x-icon-button :href="route('admin.form-definitions.edit', $definition)"
                                                icon="fas fa-edit" title="編集" color="blue" />
                                        @endcan
                                        @can('delete', $definition)
                                            @if($definition->isBeingUsed())
                                                <x-icon-button icon="fas fa-trash"
                                                    title="この項目は {{ $definition->getUsageCount() }} 件の投稿で使用されているため削除できません" color="gray"
                                                    disabled="true" />
                                            @else
                                                <form action="{{ route('admin.form-definitions.destroy', $definition) }}" method="POST"
                                                    onsubmit="return confirm('本当に削除しますか？この操作は取り消せません。');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-icon-button icon="fas fa-trash" title="削除" color="red" type="submit" />
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    カスタム項目定義がありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
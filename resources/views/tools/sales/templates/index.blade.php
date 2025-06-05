@extends('layouts.tool')

@section('title', 'メールテンプレート管理')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">メールテンプレート管理</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">メールテンプレート管理</h1>
            <div>
                @can('tools.sales.access') {{-- 適切な権限でラップ --}}
                    <x-primary-button as="a" href="{{ route('tools.sales.templates.create') }}">
                        <i class="fas fa-plus mr-1"></i> 新規テンプレート作成
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30">
                    <tr>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            テンプレート名
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            件名
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            作成者
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            最終更新日
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[180px]">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{-- 編集画面へのリンクとする --}}
                                <a href="{{ route('tools.sales.templates.edit', $template) }}"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline">
                                    {{ $template->name }}
                                </a>
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500 dark:text-gray-400 ">
                                {!! nl2br(Str::limit($template->subject, 80)) !!}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $template->createdBy->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $template->updated_at->format('Y/m/d H:i') }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @can('tools.sales.access') {{-- 適切な権限でラップ --}}
                                        <x-secondary-button as="a" href="{{ route('tools.sales.templates.edit', $template) }}"
                                            class="py-1 px-3 text-xs">
                                            <i class="fas fa-edit mr-1"></i>編集
                                        </x-secondary-button>
                                        <form action="{{ route('tools.sales.templates.destroy', $template) }}" method="POST"
                                            class="inline-block"
                                            onsubmit="return confirm('本当にテンプレート「{{ $template->name }}」を削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button type="submit" class="py-1 px-3 text-xs">
                                                <i class="fas fa-trash mr-1"></i>削除
                                            </x-danger-button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">(操作権限なし)</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                メールテンプレートはまだ作成されていません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())
            <div class="mt-4">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
@endsection
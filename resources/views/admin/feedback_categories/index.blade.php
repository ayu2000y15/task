@extends('layouts.app')

@section('title', 'フィードバックカテゴリ管理')

@push('styles')
<style>
    .sortable-handle {
        cursor: move; /* マウスカーソルを移動アイコンに */
        color: #a0aec0; /* グレー */
    }
    .sortable-handle:hover {
        color: #718096; /* 少し濃いグレー */
    }
    .sortable-placeholder { /* ドラッグ中のプレースホルダーのスタイル (SortableJSのオプションで設定) */
        background-color: #edf2f7;
        border: 2px dashed #cbd5e0;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">フィードバックカテゴリ管理</h1>
        <div>
            @can('create', App\Models\FeedbackCategory::class)
            <x-primary-button onclick="location.href='{{ route('admin.feedback-categories.create') }}'">
                <i class="fas fa-plus mr-2"></i> 新規カテゴリ追加
            </x-primary-button>
            @endcan
            <a href="{{ route('admin.feedbacks.index') }}"
               class="ml-2 inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> フィードバック一覧へ
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-10"></th> {{-- ドラッグハンドル用 --}}
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            カテゴリ名
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            関連FB数
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            状態
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            作成日時
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                {{-- ★ tbodyにIDを付与 --}}
<tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="sortable-feedback-categories" data-reorder-url="{{ route('admin.feedback-categories.reorder') }}">                    @forelse ($categories as $category)
                        {{-- ★ trにdata-id属性を付与 --}}
                        <tr data-id="{{ $category->id }}">
                            @can('reorder', App\Models\FeedbackCategory::class)
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                <i class="fas fa-grip-vertical sortable-handle" title="ドラッグして並び替え"></i>
                            </td>
                            @endcan
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $category->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $category->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $category->feedbacks_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($category->is_active)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                        有効
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">
                                        無効
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $category->created_at->format('Y/m/d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @can('update', App\Models\FeedbackCategory::class)
                                <a href="{{ route('admin.feedback-categories.edit', $category) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="編集">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('delete', App\Models\FeedbackCategory::class)
                                <form action="{{ route('admin.feedback-categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('本当にこのカテゴリを削除しますか？このカテゴリに紐づくフィードバックがない場合のみ削除可能です。');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="削除" @if($category->feedbacks_count > 0) disabled @endif>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- colspanを7に変更 --}}
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                フィードバックカテゴリはまだ登録されていません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

{{-- ★ JavaScriptの読み込みはapp.js側で行うため、ここでは不要。代わりにapp.jsで読み込む --}}
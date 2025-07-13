@extends('layouts.app')

@section('title', '投稿タイプ詳細')

@section('breadcrumbs')
    <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('admin.board-post-types.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">投稿タイプ管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">{{ $boardPostType->display_name }}</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">投稿タイプ詳細</h1>
            <div class="flex items-center space-x-2">
                @can('update', $boardPostType)
                    <x-secondary-button as="a" href="{{ route('admin.board-post-types.edit', $boardPostType) }}">
                        <i class="fas fa-edit mr-1"></i>編集
                    </x-secondary-button>
                @endcan
                @can('delete', $boardPostType)
                    <form action="{{ route('admin.board-post-types.destroy', $boardPostType) }}" method="POST"
                        class="inline-block" onsubmit="return confirm('本当に投稿タイプ「{{ $boardPostType->display_name }}」を削除しますか？\n\n使用中の投稿がある場合は削除できません。');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">
                            <i class="fas fa-trash mr-1"></i>削除
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="p-6 sm:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">基本情報</h3>
                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">表示名</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                    {{ $boardPostType->display_name }}
                                    @if($boardPostType->is_default)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            <i class="fas fa-star mr-1"></i>デフォルト
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">システム名</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $boardPostType->name }}</code>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">説明</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $boardPostType->description ?: '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">表示順序</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $boardPostType->order }}
                                </dd>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">ステータス・統計</h3>
                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ステータス</dt>
                                <dd class="mt-1">
                                    @if($boardPostType->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            <i class="fas fa-check-circle mr-1"></i>有効
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                            <i class="fas fa-times-circle mr-1"></i>無効
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">使用中の投稿数</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    <span class="font-semibold">{{ $boardPostType->boardPosts()->count() }}</span> 件
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">作成日時</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $boardPostType->created_at->format('Y年m月d日 H:i') }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">最終更新日時</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $boardPostType->updated_at->format('Y年m月d日 H:i') }}
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- カスタム項目カテゴリとの連携情報 --}}
                @php
                    $category = \App\Models\FormFieldCategory::where('name', $boardPostType->name)->first();
                    $customFields = $category ? \App\Models\FormFieldDefinition::where('category', $category->name)->get() : collect();
                @endphp

                <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">関連するカスタム項目</h3>

                    @if($category)
                        <div class="bg-green-50 dark:bg-green-900/50 border-l-4 border-green-400 dark:border-green-500 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700 dark:text-green-200">
                                        <strong>カスタム項目カテゴリ「{{ $category->display_name }}」と連携済み</strong><br>
                                        カテゴリID: {{ $category->id }} |
                                        ステータス: {{ $category->is_enabled ? '有効' : '無効' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if($customFields->isNotEmpty())
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                カスタム項目名
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                タイプ
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                必須
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                ステータス
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($customFields as $field)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $field->label }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $field->type }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($field->is_required)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                            必須
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                                            任意
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($field->is_enabled)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                            有効
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                            無効
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 text-center">
                                <x-secondary-button as="a" href="{{ route('admin.form-definitions.index') }}?category={{ $category->name }}">
                                    <i class="fas fa-cog mr-1"></i>カスタム項目を管理
                                </x-secondary-button>
                            </div>
                        @else
                            <div class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                                この投稿タイプ用のカスタム項目はまだ作成されていません。
                            </div>
                            <div class="text-center">
                                <x-primary-button as="a" href="{{ route('admin.form-definitions.create') }}?category={{ $category->name }}">
                                    <i class="fas fa-plus mr-1"></i>カスタム項目を作成
                                </x-primary-button>
                            </div>
                        @endif
                    @else
                        <div class="bg-yellow-50 dark:bg-yellow-900/50 border-l-4 border-yellow-400 dark:border-yellow-500 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 dark:text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                        <strong>カスタム項目カテゴリが見つかりません</strong><br>
                                        この投稿タイプに対応するカスタム項目カテゴリが存在しません。投稿タイプの編集で再作成されます。
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

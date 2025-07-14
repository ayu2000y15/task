@extends('layouts.app')

@section('title', '投稿タイプ詳細: ' . $boardPostType->display_name)

@section('breadcrumbs')
    <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('admin.board-post-types.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">投稿タイプ管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">{{ $boardPostType->display_name }}</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                投稿タイプ詳細: <span class="font-normal">{{ $boardPostType->display_name }}</span>
            </h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button as="a" href="{{ route('admin.board-post-types.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i>一覧へ戻る
                </x-secondary-button>
                @can('update', $boardPostType)
                    <x-primary-button as="a" href="{{ route('admin.board-post-types.edit', $boardPostType) }}">
                        <i class="fas fa-edit mr-2"></i>編集
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- メインコンテンツ --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- 基本情報 --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">基本情報</h2>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700">
                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">表示名</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0 flex items-center">
                                    <span class="flex-grow">{{ $boardPostType->display_name }}</span>
                                    @if($boardPostType->is_default)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 flex-shrink-0">
                                            <i class="fas fa-star mr-1"></i>デフォルト
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">システム名</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $boardPostType->name }}</code>
                                </dd>
                            </div>

                            @if($boardPostType->description)
                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">説明</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $boardPostType->description }}</dd>
                            </div>
                            @endif

                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">表示順序</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $boardPostType->order }}</dd>
                            </div>

                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ステータス</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                     @if($boardPostType->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            有効
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            無効
                                        </span>
                                    @endif
                                </dd>
                            </div>

                             <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">使用中の投稿数</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    <span class="font-semibold">{{ $boardPostType->boardPosts()->count() }}</span> 件
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- ▼▼▼ 関連するカスタム項目セクションのデザインを修正 ▼▼▼ --}}
                @php
                    $category = \App\Models\FormFieldCategory::where('name', $boardPostType->name)->first();
                    $customFields = $category ? \App\Models\FormFieldDefinition::where('category', $category->name)->orderBy('order')->get() : collect();
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">関連するカスタム項目</h2>
                    </div>

                    @if($category)
                        <div class="p-6">
                            <div class="bg-green-50 dark:bg-green-900/50 border-l-4 border-green-400 dark:border-green-500 p-4 rounded-r-md">
                                <p class="text-sm text-green-700 dark:text-green-200">
                                    カスタム項目カテゴリ「<strong>{{ $category->display_name }}</strong>」と連携済みです。
                                </p>
                            </div>
                        </div>

                        @if($customFields->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">順序</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ラベル</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">タイプ</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">必須</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">状態</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($customFields as $field)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $field->order }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $field->label }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $field->name }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ \App\Models\FormFieldDefinition::FIELD_TYPES[$field->type] ?? $field->type }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($field->is_required)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">必須</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">任意</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                     @if($field->is_enabled)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">有効</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">無効</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="px-6 pb-6">
                                <p class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">
                                    この投稿タイプ用のカスタム項目はまだ作成されていません。
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="p-6">
                             <div class="bg-yellow-50 dark:bg-yellow-900/50 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 rounded-r-md">
                                <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                    <strong>カスタム項目カテゴリが見つかりません。</strong><br>
                                    投稿タイプを一度編集・保存すると、対応するカテゴリが自動的に作成されます。
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
                {{-- ▲▲▲ ここまで修正 ▲▲▲ --}}
            </div>

            {{-- サイドバー --}}
            <div class="space-y-6">
                 {{-- アクション --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">アクション</h2>
                    </div>
                    <div class="p-6 space-y-3">
                         @if($category)
                            <x-secondary-button as="a" href="{{ route('admin.form-definitions.index') }}?category={{ $category->name }}" class="w-full justify-center">
                                <i class="fas fa-cogs mr-2"></i>カスタム項目を管理
                            </x-secondary-button>
                        @endif
                        @can('delete', $boardPostType)
                            <form action="{{ route('admin.board-post-types.destroy', $boardPostType) }}" method="POST"
                                onsubmit="return confirm('本当に投稿タイプ「{{ $boardPostType->display_name }}」を削除しますか？\n\n使用中の投稿がある場合は削除できません。');">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit" class="w-full justify-center">
                                    <i class="fas fa-trash mr-2"></i>削除
                                </x-danger-button>
                            </form>
                        @endcan
                    </div>
                </div>

                {{-- メタ情報 --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">メタ情報</h2>
                    </div>
                    <div class="p-6">
                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 col-span-1">作成日時</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ $boardPostType->created_at->format('Y年m月d日 H:i') }}</dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 col-span-1">最終更新日時</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ $boardPostType->updated_at->format('Y年m月d日 H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

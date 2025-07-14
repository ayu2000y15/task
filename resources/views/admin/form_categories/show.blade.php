@extends('layouts.app')

@section('title', 'フォームカテゴリ詳細: ' . $formCategory->display_name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                フォームカテゴリ詳細: <span class="font-normal">{{ $formCategory->display_name }}</span>
            </h1>
            <div class="flex space-x-2">
                <x-secondary-button as="a" href="{{ route('admin.form-categories.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
                </x-secondary-button>
                <x-primary-button as="a" href="{{ route('admin.form-categories.edit', $formCategory) }}">
                    <i class="fas fa-edit mr-2"></i> 編集
                </x-primary-button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- 基本情報 --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">基本情報</h2>
                    </div>
                    {{-- ▼▼▼ 基本情報セクションのデザインを修正 ▼▼▼ --}}
                    <div class="border-t border-gray-200 dark:border-gray-700">
                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">カテゴリ名</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $formCategory->name }}</code>
                                </dd>
                            </div>
                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">表示名</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $formCategory->display_name }}</dd>
                            </div>
                            @if($formCategory->description)
                                <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">説明</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $formCategory->description }}</dd>
                                </div>
                            @endif
                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">表示順序</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $formCategory->order }}</dd>
                            </div>
                            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">状態</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    @if($formCategory->is_enabled)
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
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">フィールド数</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $formCategory->form_field_definitions_count }}</dd>
                            </div>
                        </dl>
                    </div>
                    {{-- ▲▲▲ ここまで修正 ▲▲▲ --}}
                </div>

                {{-- フィールド一覧 --}}
                @if($formFieldDefinitions->isNotEmpty())
                    <div class="mt-6 bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">フィールド定義</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            順序
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            ラベル
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            タイプ
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            必須
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            状態
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($formFieldDefinitions as $field)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $field->order }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $field->label }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $field->name }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $field->type }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($field->is_required)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        必須
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                        任意
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($field->is_enabled)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        有効
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        無効
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- サイドバー --}}
            <div class="space-y-6">
                {{-- 外部フォーム情報 --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">外部フォーム</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">公開状態</dt>
                            <dd class="mt-1">
                                @if($formCategory->is_external_form)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        公開中
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        非公開
                                    </span>
                                @endif
                            </dd>
                        </div>

                        @if($formCategory->is_external_form)
                            @if($formCategory->slug)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">フォームURL</dt>
                                    <dd class="mt-1">
                                        <a href="{{ $formCategory->external_form_url }}" target="_blank"
                                           class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all">
                                            {{ $formCategory->external_form_url }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($formCategory->form_title)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">フォームタイトル</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $formCategory->form_title }}</dd>
                                </div>
                            @endif

                            @if($formCategory->notification_emails)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">通知先</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        @foreach($formCategory->notification_emails as $email)
                                            <div>{{ $email }}</div>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">案件化用</dt>
                                <dd class="mt-1">
                                    @if($formCategory->requires_approval)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            有効
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            無効
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">送信完了メール</dt>
                                <dd class="mt-1">
                                    @if($formCategory->send_completion_email)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            有効
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            無効
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- アクション --}}
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">アクション</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        @if($formCategory->is_external_form && $formCategory->slug)
                            <a href="{{ $formCategory->external_form_url }}" target="_blank"
                               class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <i class="fas fa-external-link-alt mr-2"></i>
                                フォームを確認
                            </a>
                        @endif

                        <a href="{{ route('admin.form-definitions.index', ['category' => $formCategory->name]) }}"
                           class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <i class="fas fa-cogs mr-2"></i>
                            フィールド管理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

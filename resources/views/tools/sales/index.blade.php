{{-- resources/views/tools/sales/index.blade.php --}}
@extends('layouts.tool') {{-- ツール専用レイアウトを使用 --}}

@section('title', '営業ツール ダッシュボード')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">営業ツール</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">営業ツール ダッシュボード</h1>
            <div>
                {{-- 新規メール作成ボタンなど、主要なアクションへのショートカットを配置可能 --}}
                <x-primary-button as="a" href="{{ route('tools.sales.emails.compose') }}">
                    <i class="fas fa-plus mr-1"></i> 新規メール作成
                </x-primary-button>
            </div>
        </div>

        {{-- サマリー情報表示エリア --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-500 text-white mr-4">
                        <i class="fas fa-list-alt fa-2x"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">メールリスト数</p>
                        <p class="text-3xl font-semibold text-gray-700 dark:text-gray-200">
                            {{ $summary['total_email_lists'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-500 text-white mr-4">
                        <i class="fas fa-paper-plane fa-2x"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">本日送信済メール</p>
                        <p class="text-3xl font-semibold text-gray-700 dark:text-gray-200">
                            {{ $summary['total_sent_emails_today'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
            {{-- 他のサマリーカード（例: 平均開封率、平均クリック率など） --}}
        </div>

        {{-- 各機能へのナビゲーションカード --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="{{ route('tools.sales.email-lists.index') }}"
                class="block bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center mb-3">
                    <i class="fas fa-address-book fa-2x text-blue-500 mr-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">メールリスト管理</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">送信先メールアドレスのリストを作成・編集します。</p>
            </a>

            <a href="{{ route('tools.sales.emails.compose') }}"
                class="block bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center mb-3">
                    <i class="fas fa-envelope-open-text fa-2x text-green-500 mr-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">メール作成・送信</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">営業メールを作成し、指定したリストに送信します。</p>
            </a>

            <a href="{{route('tools.sales.emails.sent.index')}}"
                class="block bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center mb-3">
                    <i class="fas fa-chart-line fa-2x text-yellow-500 mr-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">送信履歴・効果測定</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">送信したメールの履歴や開封・クリック状況を確認します。</p>
            </a>

            <a href="{{ route('tools.sales.blacklist.index') }}"
                class="block bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center mb-3">
                    <i class="fas fa-user-shield fa-2x text-red-500 mr-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">ブラックリスト管理</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">送信を拒否するメールアドレスを管理します。</p>
            </a>

            <a href="{{route('tools.sales.settings.edit')}}"
                class="block bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center mb-3">
                    <i class="fas fa-cog fa-2x text-gray-500 mr-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">設定</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">送信間隔やバッチサイズなどの設定を行います。</p>
            </a>
        </div>
    </div>
@endsection
@extends('layouts.tool')

@section('title', '送信履歴一覧')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">送信履歴</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">送信履歴一覧</h1>
            <div>
                @can('tools.sales.access')
                    <x-primary-button as="a" href="{{ route('tools.sales.emails.compose') }}">
                        <i class="fas fa-plus mr-1"></i> 新規メール作成
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <form action="{{ route('tools.sales.emails.sent.index') }}" method="GET"
                class="flex flex-col sm:flex-row gap-3">
                <div class="flex-grow">
                    <x-input-label for="keyword" value="件名・リスト名で検索" class="sr-only" />
                    <x-text-input type="search" name="keyword" id="keyword" placeholder="件名またはメールリスト名で検索..." class="w-full"
                        :value="request('keyword')" />
                </div>
                <x-primary-button type="submit">
                    <i class="fas fa-search mr-1"></i> 検索
                </x-primary-button>
                @if(request('keyword'))
                    <x-secondary-button as="a" href="{{ route('tools.sales.emails.sent.index') }}">
                        クリア
                    </x-secondary-button>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30" >
                    <tr>
                        <th
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            件名</th>
                        <th
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            対象リスト</th>
                        <th
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            送信日時</th>
                        <th
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            開封数</th>
                        <th
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            開封率</th>
                        <th
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ｸﾘｯｸ数</th>
                        <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                            title="開封数に対するクリック率">CTOR</th>
                        <th
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ステータス</th>
                        <th
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[100px]">
                            操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($sentEmails as $sentMail)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('tools.sales.emails.sent.show', $sentMail) }}"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline"
                                    title="{{ $sentMail->subject }}">
                                    {{ Str::limit($sentMail->subject, 35) }}
                                </a>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ Str::limit($sentMail->emailList->name ?? 'N/A', 20) }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $sentMail->sent_at ? $sentMail->sent_at->format('Y/m/d H:i') : '-' }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400 text-center">
                                {{ $sentMail->opened_count }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ number_format($sentMail->open_rate, 1) }}%</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-center">
                                {{ $sentMail->clicked_count }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ number_format($sentMail->click_to_open_rate, 1) }}%</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm">
                                @php
                                    $statusKey = $sentMail->status; // 英語のステータスキー
                                    $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; // Default

                                    switch ($statusKey) {
                                        case 'queued':
                                        case 'queuing':
                                        case 'processing': // 処理中 (青/水色系)
                                            $statusClass = 'bg-sky-100 text-sky-800 dark:bg-sky-700 dark:text-sky-200';
                                            break;
                                        case 'sent': // MTAへの引き渡し完了
                                        case 'completed_sent': // 全て成功 (緑系)
                                            $statusClass = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200';
                                            break;
                                        case 'completed_partially': // 一部完了/失敗混在 (黄色系)
                                            $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200';
                                            break;
                                        case 'all_failed_or_bounced': // 全て失敗/バウンス (赤系)
                                        case 'all_queue_failed':    // 全てキューイング失敗 (赤系)
                                        case 'failed':              // 送信失敗 (全体) (赤系)
                                            $statusClass = 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200';
                                            break;
                                        case 'all_skipped': // 全件BLスキップ (黄色系 - 警告)
                                            $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-300';
                                            break;
                                        case 'no_recipients':
                                        case 'no_recipients_processed':
                                        case 'processing_issue': // その他・情報 (グレー系)
                                            $statusClass = 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300';
                                            break;
                                    }
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                    {{ $sentMail->readable_status }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <x-secondary-button as="a" href="{{ route('tools.sales.emails.sent.show', $sentMail) }}"
                                        class="py-1 px-3 text-xs">
                                        <i class="fas fa-eye mr-1"></i>詳細
                                    </x-secondary-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                送信履歴はありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sentEmails->hasPages())
            <div class="mt-4">
                {{ $sentEmails->links() }}
            </div>
        @endif
    </div>
@endsection
@extends('layouts.tool')

@section('title', '送信履歴詳細 - ' . Str::limit($sentEmail->subject, 30))

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.emails.sent.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">送信履歴</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200 truncate" title="{{ $sentEmail->subject }}">{{ Str::limit($sentEmail->subject, 30) }}</span>
@endsection

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-5xl mx-auto"> {{-- 詳細画面はさらに幅広に max-w-5xl --}}
        <div class="flex flex-col sm:flex-row justify-between items-start mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    送信メール詳細: <span class="font-normal">{{ $sentEmail->subject }}</span>
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    対象リスト: <a href="{{ route('tools.sales.email-lists.show', $sentEmail->emailList) }}" class="text-blue-600 hover:underline">{{ $sentEmail->emailList->name ?? 'N/A' }}</a>
                    | 送信日時: {{ $sentEmail->sent_at ? $sentEmail->sent_at->format('Y/m/d H:i') : '-' }}
                    | ステータス: <span class="font-medium">{{ $sentEmail->readable_status  }}</span>
                </p>
            </div>
            <x-secondary-button as="a" href="{{ route('tools.sales.emails.sent.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 送信履歴一覧へ戻る
            </x-secondary-button>
        </div>

        {{-- サマリー情報 --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">総試行数</div>
                <div class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $summary['total'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">成功</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $summary['sent'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">失敗/バウンス</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $summary['failed'] }}</div>
            </div>
             <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">キュー投入済</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $summary['queued'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">BLスキップ</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $summary['skipped_blacklist'] }}</div>
            </div>
        </div>

        {{-- メール本文プレビュー (任意) --}}
        <div class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">送信本文プレビュー</h3>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto border rounded-b-lg dark:border-gray-700">
                <div class="prose dark:prose-invert max-w-none">
                    {!! $sentEmail->body_html !!}
                </div>
            </div>
        </div>


        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">受信者別 送信ログ ({{ $recipientLogs->total() }}件)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30">
                        <tr>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">メールアドレス</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">購読者名</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ステータス</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">処理日時</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">エラー</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recipientLogs as $log)
                            <tr>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $log->recipient_email }}</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->subscriber->name ?? '-' }}</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm">
                                     @php
                                        $logStatusClass = '';
                                        switch ($log->status) {
                                            case 'sent': $logStatusClass = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200'; break;
                                            case 'queued': $logStatusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200'; break;
                                            case 'failed': case 'bounced': case 'queue_failed': $logStatusClass = 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200'; break;
                                            case 'skipped_blacklist': $logStatusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200'; break;
                                            default: $logStatusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; break;
                                        }
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $logStatusClass }}">
                                        {{ $log->readable_status }} {{-- ここも日本語化するなら SentEmailLog モデルにアクセサ追加 --}}
                                    </span>
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->processed_at ? $log->processed_at->format('Y/m/d H:i:s') : ($log->created_at ? $log->created_at->format('Y/m/d H:i:s') : '-') }}</td>
                                <td class="px-6 py-2 text-sm text-red-500 dark:text-red-400 whitespace-pre-wrap break-words">{{ $log->error_message ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">送信ログはありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($recipientLogs->hasPages())
                <div class="mt-4 px-6 pb-4">
                    {{ $recipientLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
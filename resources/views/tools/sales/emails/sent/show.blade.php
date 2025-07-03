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
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    送信メール詳細: <span class="font-normal">{{ $sentEmail->subject }}</span>
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    対象リスト: <a href="{{ $sentEmail->emailList ? route('tools.sales.email-lists.show', $sentEmail->emailList) : '#' }}" class="text-blue-600 hover:underline">{{ $sentEmail->emailList->name ?? 'N/A' }}</a>
                    | 送信日時: {{ $sentEmail->sent_at ? $sentEmail->sent_at->format('Y/m/d H:i') : '-' }}
                    | ステータス: {{-- ▼▼▼ SentEmail のステータスにも色付け ▼▼▼ --}}
                    @php
                        $sentEmailStatusKey = $sentEmail->status;
                        $sentEmailStatusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; // Default
                        switch ($sentEmailStatusKey) {
                            case 'queued': case 'queuing': case 'processing':
                                $sentEmailStatusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200'; break;
                            case 'sent': case 'completed_all_sent':
                                $sentEmailStatusClass = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200'; break;
                            case 'partially_completed':
                                $sentEmailStatusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200'; break;
                            case 'all_failed_or_bounced': case 'all_queue_failed': case 'failed':
                                $sentEmailStatusClass = 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200'; break;
                            case 'all_skipped':
                                $sentEmailStatusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-300'; break;
                        }
                    @endphp
                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sentEmailStatusClass }}">
                        {{ $sentEmail->readable_status }}
                    </span>
                </p>
            </div>
            <x-secondary-button as="a" href="{{ route('tools.sales.emails.sent.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 送信履歴一覧へ戻る
            </x-secondary-button>
        </div>

        {{-- サマリー情報 (変更なし) --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
            {{-- 完了までの推定時間カード --}}
            @if (in_array($sentEmail->status, ['queued', 'queuing', 'processing']))
                @php
                    $settings = \App\Models\SalesToolSetting::first();
                    $remainingCount = $recipientLogs->where('status', 'queued')->count();
                    $estimatedDuration = '計算不可';
                    $estimatedCompletionTime = ''; // 完了時刻を初期化

                    if ($settings && $remainingCount > 0) {
                        $delayPerEmail = 1; // デフォルト1秒
                        if ($settings->send_timing_type === 'fixed') {
                            $delayPerEmail = $settings->max_emails_per_minute > 0 ? (60 / $settings->max_emails_per_minute) : 1;
                        } else {
                            $delayPerEmail = ($settings->random_send_min_seconds + $settings->random_send_max_seconds) / 2;
                        }
                        $totalSeconds = $remainingCount * $delayPerEmail;

                        // 残り時間を人間が読みやすい形式に変換
                        $estimatedDuration = \Carbon\CarbonInterval::seconds($totalSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);

                        // 現在時刻から計算して、実際の完了予測時刻を算出
                        $estimatedCompletionTimestamp = now()->addSeconds($totalSeconds);
                        $estimatedCompletionTime = $estimatedCompletionTimestamp->format('Y/m/d H:i');
                    }
                @endphp
                <div class="bg-blue-50 dark:bg-blue-900/50 p-4 shadow rounded-lg text-center border border-blue-200 dark:border-blue-700">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">完了までの推定時間</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">約 {{ $estimatedDuration }}</div>
                    {{-- 完了予測時刻を追加 --}}
                    @if($estimatedCompletionTime)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ({{ $estimatedCompletionTime }} ごろ完了予定)
                        </div>
                    @endif
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">開封数</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $sentEmail->opened_count }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">開封率</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($sentEmail->open_rate, 1) }}%</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">クリック数</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $sentEmail->clicked_count }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg text-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400" title="開封数に対するクリック率">開封数に対するクリック率(CTOR)</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($sentEmail->click_to_open_rate, 1) }}%</div>
            </div>
        </div>

        {{-- メール本文プレビュー (変更なし) --}}
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

        {{-- 受信者別 送信ログ --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">受信者別 送信ログ ({{ $recipientLogs->total() }}件)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30" >
                        <tr>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">メールアドレス</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">購読者名</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">送信日時</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ステータス</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">開封日時</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">最終クリック日時</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">エラー</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recipientLogs as $log)
                            <tr>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $log->recipient_email }}</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->subscriber->name ?? '-' }}</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->processed_at ? $log->processed_at->format('Y/m/d H:i:s') : '-' }}</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm">
                                     @php
                                        $logStatusKey = $log->status; // 英語のステータスキー
                                        $logStatusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; // Default
                                        switch ($logStatusKey) {
                                            case 'sent':
                                                $logStatusClass = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200'; break;
                                            case 'opened':
                                                $logStatusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200'; break;
                                            case 'clicked':
                                                $logStatusClass = 'bg-purple-100 text-purple-800 dark:bg-purple-700 dark:text-purple-200'; break;
                                            case 'queued':
                                                $logStatusClass = 'bg-sky-100 text-sky-800 dark:bg-sky-700 dark:text-sky-200'; break; // 少し青系でも変える
                                            case 'failed': case 'bounced': case 'queue_failed':
                                                $logStatusClass = 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200'; break;
                                            case 'skipped_blacklist': case 'unsubscribed_via_link':
                                                $logStatusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-300'; break;
                                        }
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $logStatusClass }}">
                                        {{ $log->readable_status }} {{-- SentEmailLogモデルのアクセサで日本語化 --}}
                                    </span>
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->opened_at ? $log->opened_at->format('Y/m/d H:i:s') : '-' }}</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->clicked_at ? $log->clicked_at->format('Y/m/d H:i:s') : '-' }}</td>
                                <td class="px-6 py-2 text-sm text-red-500 dark:text-red-400 whitespace-pre-wrap break-words">{{ Str::limit($log->error_message, 100) ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    送信ログはありません。
                                </td>
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
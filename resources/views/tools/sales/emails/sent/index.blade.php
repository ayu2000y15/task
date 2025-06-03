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
                <x-primary-button as="a" href="{{ route('tools.sales.emails.compose') }}">
                    <i class="fas fa-plus mr-1"></i> 新規メール作成
                </x-primary-button>
            </div>
        </div>

        {{-- 検索フォーム (任意) --}}
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
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            件名</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            対象リスト</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            送信日時</th>
                        <th scope="col"
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            総数</th>
                        <th scope="col"
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            成功</th>
                        <th scope="col"
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            失敗/B</th>
                        <th scope="col"
                            class="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            BLｽｷｯﾌﾟ</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ステータス</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[100px]">
                            操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($sentEmails as $sentMail)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                <a href="{{ route('tools.sales.emails.sent.show', $sentMail) }}"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline"
                                    title="{{ $sentMail->subject }}">
                                    {{ Str::limit($sentMail->subject, 40) }}
                                </a>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $sentMail->emailList->name ?? 'N/A' }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $sentMail->sent_at ? $sentMail->sent_at->format('Y/m/d H:i') : '-' }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ $sentMail->total_recipients_count ?? 0 }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-center">
                                {{ $sentMail->successful_sends_count ?? 0 }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-center">
                                {{ $sentMail->failed_sends_count ?? 0 }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-yellow-600 dark:text-yellow-400 text-center">
                                {{ $sentMail->skipped_blacklist_count ?? 0 }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm">
                                @php
                                    $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; // Default
                                    if ($sentMail->status === 'sent')
                                        $statusClass = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200';
                                    if ($sentMail->status === 'queued' || $sentMail->status === 'queuing')
                                        $statusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200';
                                    if (Str::contains($sentMail->status, 'failed') || $sentMail->status === 'all_blacklisted_or_failed')
                                        $statusClass = 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200';
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                    {{ $sentMail->readable_status  }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <x-secondary-button as="a" href="{{ route('tools.sales.emails.sent.show', $sentMail) }}"
                                        class="py-1 px-3 text-xs">
                                        <i class="fas fa-eye mr-1"></i>詳細
                                    </x-secondary-button>
                                    {{-- 将来的に再送信やコピー機能など --}}
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
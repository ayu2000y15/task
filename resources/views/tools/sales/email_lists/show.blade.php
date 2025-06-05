@extends('layouts.tool')

@section('title', 'メールリスト詳細 - ' . $emailList->name)

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.email-lists.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">メールリスト管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200 truncate"
        title="{{ $emailList->name }}">{{ Str::limit($emailList->name, 30) }}</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mx-auto mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        メールリスト: <span class="font-normal">{{ $emailList->name }}</span>
                    </h1>
                    @if($emailList->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{!! nl2br(e(trim($emailList->description))) !!}
                        </p>
                    @endif
                </div>
                <div class="flex space-x-2 flex-shrink-0">
                    <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.index') }}">
                        <i class="fas fa-arrow-left mr-2"></i> メールリスト一覧へ戻る
                    </x-secondary-button>
                    @can('tools.sales.access')
                        <x-primary-button as="a" href="{{ route('tools.sales.email-lists.subscribers.create', $emailList) }}">
                            <i class="fas fa-user-plus mr-1"></i> 購読者を追加
                        </x-primary-button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">登録購読者一覧 ({{ $subscribers->total() }}件)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                メールアドレス</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                名前</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                会社名</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ステータス</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                登録日</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[180px]">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($subscribers as $subscriber)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{-- Subscriberに保存されたメールアドレスを表示 --}}
                                    {{ $subscriber->managedContact->email }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{-- ManagedContactの情報を表示 --}}
                                    {{ $subscriber->managedContact->name ?? '-' }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{-- ManagedContactの情報を表示 --}}
                                    {{ $subscriber->managedContact->company_name ?? '-' }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm">
                                    @php
                                        $statusClass = '';
                                        switch ($subscriber->status) {
                                            case 'subscribed':
                                                $statusClass = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200';
                                                break;
                                            case 'unsubscribed':
                                                $statusClass = 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200';
                                                break;
                                            case 'bounced':
                                                $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200';
                                                break;
                                            case 'pending':
                                                $statusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200';
                                                break;
                                            default:
                                                $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                                break;
                                        }
                                    @endphp
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                        {{ $subscriber->readable_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $subscriber->subscribed_at->format('Y/m/d') }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        @can('tools.sales.access')
                                            <x-secondary-button as="a"
                                                href="{{ route('tools.sales.email-lists.subscribers.edit', [$emailList, $subscriber]) }}"
                                                class="py-1 px-3 text-xs">
                                                <i class="fas fa-edit mr-1"></i>編集
                                            </x-secondary-button>
                                            <form
                                                action="{{ route('tools.sales.email-lists.subscribers.destroy', [$emailList, $subscriber]) }}"
                                                method="POST" class="inline-block"
                                                onsubmit="return confirm('本当に購読者「{{ $subscriber->email }}」をこのリストから削除しますか？');">
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
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    このリストにはまだ購読者が登録されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($subscribers->hasPages())
                <div class="mt-4 px-6 pb-4">
                    {{ $subscribers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
@extends('layouts.tool')

@section('title', 'ブラックリスト管理')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">ブラックリスト管理</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">ブラックリスト管理</h1>
            {{-- 必要に応じて、ダッシュボードへ戻るボタンなどをここに配置 --}}
            <x-secondary-button as="a" href="{{ route('tools.sales.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 営業ツールダッシュボードへ
            </x-secondary-button>
        </div>

        {{-- 新規登録フォーム --}}
        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">新規ブラックリスト登録</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('tools.sales.blacklist.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="email" value="メールアドレス" :required="true" />
                            <x-text-input type="email" id="email" name="email" class="mt-1 block w-full"
                                :value="old('email')" required :hasError="$errors->has('email')"
                                placeholder="blacklist@example.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reason" value="登録理由 (任意)" />
                            <x-textarea-input id="reason" name="reason" class="mt-1 block w-full" rows="3"
                                :hasError="$errors->has('reason')">{{ old('reason') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <x-primary-button type="submit">
                            <i class="fas fa-plus mr-2"></i> ブラックリストに追加
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ブラックリスト一覧表示 --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">登録済みブラックリスト
                    ({{ $blacklistedEmails->total() }}件)</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30" style="top: 4rem;">
                    <tr>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            メールアドレス</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            登録理由</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            登録者</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            登録日</th>
                        <th scope="col"
                            class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[100px]">
                            操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($blacklistedEmails as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $entry->email }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-wrap break-words">
                                {!! nl2br(e(trim($entry->reason))) !!}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $entry->addedByUser->name ?? 'N/A' }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $entry->created_at->format('Y/m/d H:i') }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                @can('tools.sales.access') {{-- 適切な権限で --}}
                                    <form action="{{ route('tools.sales.blacklist.destroy', $entry) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('「{{ $entry->email }}」をブラックリストから本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button type="submit" class="py-1 px-3 text-xs">
                                            <i class="fas fa-trash mr-1"></i>削除
                                        </x-danger-button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                ブラックリストに登録されているメールアドレスはありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($blacklistedEmails->hasPages())
            <div class="mt-4 px-6 pb-4">
                {{ $blacklistedEmails->links() }}
            </div>
        @endif
    </div>
@endsection
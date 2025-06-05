@extends('layouts.tool')

@section('title', '購読者編集 - ' . ($subscriber->managedContact->email ?? $subscriber->email) . ' (リスト: ' . $emailList->name . ')')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.email-lists.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">メールリスト管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.email-lists.show', $emailList) }}"
        class="hover:text-blue-600 dark:hover:text-blue-400 truncate"
        title="{{ $emailList->name }}">{{ Str::limit($emailList->name, 30) }}</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">購読者編集</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-3xl mx-auto mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    購読者編集 <span class="text-base font-normal text-gray-600 dark:text-gray-400">(リスト:
                        {{ $emailList->name }})</span>
                </h1>
                <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.show', $emailList) }}">
                    <i class="fas fa-arrow-left mr-2"></i> リスト詳細へ戻る
                </x-secondary-button>
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">購読者情報</h2>
            </div>
            <div class="p-6 sm:p-8">
                {{-- ManagedContact の情報を表示 (編集不可) --}}
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                    <h3 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-3">関連連絡先情報
                        @if($subscriber->managedContact)
                            <a href="{{ route('tools.sales.managed-contacts.edit', $subscriber->managedContact) }}"
                                class="ml-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">(連絡先を編集)</a>
                        @endif
                    </h3>
                    @if ($subscriber->managedContact)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <span class="font-medium text-gray-600 dark:text-gray-400">メールアドレス:</span>
                                <span class="text-gray-800 dark:text-gray-200">{{ $subscriber->managedContact->email }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600 dark:text-gray-400">名前:</span>
                                <span
                                    class="text-gray-800 dark:text-gray-200">{{ $subscriber->managedContact->name ?? '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="font-medium text-gray-600 dark:text-gray-400">会社名:</span>
                                <span
                                    class="text-gray-800 dark:text-gray-200">{{ $subscriber->managedContact->company_name ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600 dark:text-gray-400">郵便番号:</span>
                                <span
                                    class="text-gray-800 dark:text-gray-200">{{ $subscriber->managedContact->postal_code ?? '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="font-medium text-gray-600 dark:text-gray-400">住所:</span>
                                <span
                                    class="text-gray-800 dark:text-gray-200">{{ $subscriber->managedContact->address ?? '-' }}</span>
                            </div>
                            {{-- 必要に応じて他のManagedContact情報も表示 --}}
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">関連する管理連絡先情報が見つかりません。</p>
                    @endif
                </div>

                <hr class="my-6 border-gray-200 dark:border-gray-700">

                {{-- Subscriber固有情報（ステータスなど）の編集フォーム --}}
                <form action="{{ route('tools.sales.email-lists.subscribers.update', [$emailList, $subscriber]) }}"
                    method="POST">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="subscriber_email_display" value="購読メールアドレス (変更不可)" />
                        <x-text-input type="email" id="subscriber_email_display" name="subscriber_email_display"
                            class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                            :value="$subscriber->managedContact->email" readonly />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    </div>

                    <div class="mt-6"> {{-- status のみ編集可能とする例 --}}
                        @php
                            // Subscriberモデルで定義されているステータスを利用できるようにする
                            // ここでは例として手動で定義
                            $statusOptions = [
                                'subscribed' => '購読中 (Subscribed)',
                                'unsubscribed' => '解除済 (Unsubscribed)',
                                'bounced' => 'エラー (Bounced)',
                                'pending' => '保留中 (Pending)',
                            ];
                        @endphp
                        <x-select-input label="購読ステータス" id="status" name="status" class="mt-1 block w-full"
                            :options="$statusOptions" :selected="old('status', $subscriber->status)" :required="true"
                            :hasError="$errors->has('status')" />
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.show', $emailList) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> ステータスを更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
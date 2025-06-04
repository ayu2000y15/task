@extends('layouts.tool')

@section('title', '営業ツール設定')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">設定</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-2xl mx-auto mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    営業ツール設定
                </h1>
                <x-secondary-button as="a" href="{{ route('tools.sales.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> 営業ツールダッシュボードへ
                </x-secondary-button>
            </div>
        </div>

        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">送信設定</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('tools.sales.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="max_emails_per_minute" value="1分あたりの最大メール送信数" :required="true" />
                            <x-text-input type="number" id="max_emails_per_minute" name="max_emails_per_minute"
                                class="mt-1 block w-full" :value="old('max_emails_per_minute', $settings->max_emails_per_minute ?? 60)" required min="1"
                                :hasError="$errors->has('max_emails_per_minute')" />
                            <x-input-error :messages="$errors->get('max_emails_per_minute')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                1分間に送信するメールの最大数を設定します。この値に基づき、各メール送信ジョブの遅延時間が自動計算され、送信タイミングが分散されます。(例: 60
                                を設定すると、1通あたり約1秒の間隔でキューに投入されます。)</p>
                        </div>

                        {{-- 以前のバッチ関連設定項目 (max_emails_per_minute に統合した場合は不要) --}}
                        {{--
                        <div>
                            <x-input-label for="emails_per_batch" value="1バッチあたりのメール送信数 (旧)" :required="true" />
                            <x-text-input type="number" id="emails_per_batch" name="emails_per_batch"
                                class="mt-1 block w-full"
                                :value="old('emails_per_batch', $settings->emails_per_batch ?? 100)" required min="1"
                                max="1000" :hasError="$errors->has('emails_per_batch')" />
                            <x-input-error :messages="$errors->get('emails_per_batch')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">旧設定: 一度のキュー処理で送信を試みるメールの最大数です。</p>
                        </div>

                        <div>
                            <x-input-label for="batch_delay_seconds" value="バッチ間の遅延 (秒) (旧)" :required="true" />
                            <x-text-input type="number" id="batch_delay_seconds" name="batch_delay_seconds"
                                class="mt-1 block w-full"
                                :value="old('batch_delay_seconds', $settings->batch_delay_seconds ?? 60)" required min="0"
                                :hasError="$errors->has('batch_delay_seconds')" />
                            <x-input-error :messages="$errors->get('batch_delay_seconds')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">旧設定:
                                各バッチのメールをキューに追加した後、次のバッチを追加するまでの遅延時間（秒単位）です。</p>
                        </div>

                        <div>
                            <x-input-label for="send_interval_minutes" value="送信間隔の目安 (分) (旧)" :required="true" />
                            <x-text-input type="number" id="send_interval_minutes" name="send_interval_minutes"
                                class="mt-1 block w-full"
                                :value="old('send_interval_minutes', $settings->send_interval_minutes ?? 5)" required
                                min="1" :hasError="$errors->has('send_interval_minutes')" />
                            <x-input-error :messages="$errors->get('send_interval_minutes')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">旧設定:
                                この設定は現在、直接的な送信制御には使用されていませんが、将来的な機能拡張のための目安として設定します。</p>
                        </div>
                        --}}

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <x-input-label value="オプション"
                                class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2" />
                            <label for="image_sending_enabled" class="flex items-center">
                                <x-checkbox-input id="image_sending_enabled" name="image_sending_enabled" value="1"
                                    :checked="old('image_sending_enabled', $settings->image_sending_enabled ?? true)" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">メール本文への画像送信を有効にする</span>
                            </label>
                            <x-input-error :messages="$errors->get('image_sending_enabled')" class="mt-2" />
                            <p class="mt-1 ml-6 text-xs text-gray-500 dark:text-gray-400">
                                TinyMCEエディタからの画像アップロードと本文への埋め込みを許可します。</p>
                        </div>

                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 設定を保存
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
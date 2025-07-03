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
                            <x-input-label for="daily_send_limit" value="1日の最大メール送信数" :required="true" />
                            <x-text-input type="number" id="daily_send_limit" name="daily_send_limit"
                                class="mt-1 block w-full" :value="old('daily_send_limit', $settings->daily_send_limit ?? 10000)" required min="1"
                                :hasError="$errors->has('daily_send_limit')" />
                            <x-input-error :messages="$errors->get('daily_send_limit')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                24時間あたりのメール送信数の上限を設定します。この数を超えて送信しようとするとエラーになります。
                            </p>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <x-input-label value="送信間隔のタイプ" :required="true" />
                            <div class="mt-2 space-y-2">
                                <label for="send_timing_type_fixed" class="flex items-center">
                                    <input type="radio" id="send_timing_type_fixed" name="send_timing_type" value="fixed"
                                        class="text-blue-600 focus:ring-blue-500"
                                        {{ old('send_timing_type', $settings->send_timing_type ?? 'fixed') == 'fixed' ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">固定間隔で送信</span>
                                </label>
                                <label for="send_timing_type_random" class="flex items-center">
                                    <input type="radio" id="send_timing_type_random" name="send_timing_type" value="random"
                                        class="text-blue-600 focus:ring-blue-500"
                                        {{ old('send_timing_type', $settings->send_timing_type) == 'random' ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ランダムな間隔で送信</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('send_timing_type')" class="mt-2" />
                        </div>

                        {{-- 固定間隔用の設定 --}}
                        <div id="fixed_settings_block" class="space-y-6">
                            <div>
                                <x-input-label for="max_emails_per_minute" value="1分あたりの最大メール送信数" :required="true" />
                                <x-text-input type="number" id="max_emails_per_minute" name="max_emails_per_minute"
                                    class="mt-1 block w-full" :value="old('max_emails_per_minute', $settings->max_emails_per_minute ?? 60)" required min="1"
                                    :hasError="$errors->has('max_emails_per_minute')" />
                                <x-input-error :messages="$errors->get('max_emails_per_minute')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    1分間に送信するメールの最大数を設定します。この値に基づき、各メール送信ジョブの遅延時間が自動計算され、送信タイミングが分散されます。(例: 60
                                    を設定すると、1通あたり1秒の間隔でキューに投入されます。)
                                </p>
                            </div>
                        </div>

                        {{-- ランダム間隔用の設定 --}}
                        <div id="random_settings_block" class="space-y-6">
                            <div>
                                <x-input-label for="random_send_min_seconds" value="最小送信間隔 (秒)" :required="true" />
                                <x-text-input type="number" id="random_send_min_seconds" name="random_send_min_seconds"
                                    class="mt-1 block w-full" :value="old('random_send_min_seconds', $settings->random_send_min_seconds ?? 2)" required min="0"
                                    :hasError="$errors->has('random_send_min_seconds')" />
                                <x-input-error :messages="$errors->get('random_send_min_seconds')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    メール1通を送信する際の、ランダムな間隔の最小秒数を設定します。
                                </p>
                            </div>
                            <div>
                                <x-input-label for="random_send_max_seconds" value="最大送信間隔 (秒)" :required="true" />
                                <x-text-input type="number" id="random_send_max_seconds" name="random_send_max_seconds"
                                    class="mt-1 block w-full" :value="old('random_send_max_seconds', $settings->random_send_max_seconds ?? 10)" required min="0"
                                    :hasError="$errors->has('random_send_max_seconds')" />
                                <x-input-error :messages="$errors->get('random_send_max_seconds')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    メール1通を送信する際の、ランダムな間隔の最大秒数を設定します。
                                </p>
                            </div>
                        </div>

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

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timingTypeRadios = document.querySelectorAll('input[name="send_timing_type"]');
            const fixedSettingsBlock = document.getElementById('fixed_settings_block');
            const randomSettingsBlock = document.getElementById('random_settings_block');

            function toggleSettingsVisibility() {
                const selectedType = document.querySelector('input[name="send_timing_type"]:checked').value;
                if (selectedType === 'random') {
                    fixedSettingsBlock.style.display = 'none';
                    randomSettingsBlock.style.display = 'block';
                } else {
                    fixedSettingsBlock.style.display = 'block';
                    randomSettingsBlock.style.display = 'none';
                }
            }

            // 初期表示
            toggleSettingsVisibility();

            // ラジオボタン変更時に切り替え
            timingTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleSettingsVisibility);
            });
        });
    </script>
    @endpush

@endsection
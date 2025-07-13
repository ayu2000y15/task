@extends('layouts.app')

@section('title', '投稿タイプ作成')

@section('breadcrumbs')
    <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('admin.board-post-types.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">投稿タイプ管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">新規作成</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">投稿タイプ作成</h1>

        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="p-6 sm:p-8">
                    <div
                        class="mb-2 p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>自動作成される内容：</strong><br>
                        投稿タイプを作成すると、カスタム項目管理にも同じ名前のカテゴリが自動的に作成されます。
                        これにより、この投稿タイプ専用のカスタム項目を設定できるようになります。
                    </div>
                <form action="{{ route('admin.board-post-types.store') }}" method="POST">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="display_name" value="表示名" :required="true" />
                            <x-text-input type="text" id="display_name" name="display_name" class="mt-1 block w-full"
                                :value="old('display_name')" required placeholder="例: 企画書" />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                ユーザーに表示される名前です
                            </p>
                        </div>

                        <div>
                            <x-input-label for="name" value="システム名" />
                            <x-text-input type="text" id="name" name="name" class="mt-1 block w-full"
                                :value="old('name')" placeholder="例: proposal（自動生成されます）" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                システム内部で使用される識別子です。空白の場合、表示名から自動生成されます
                            </p>
                        </div>

                        <div>
                            <x-input-label for="description" value="説明" />
                            <textarea id="description" name="description" rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                placeholder="この投稿タイプの説明を入力してください">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="order" value="表示順序" />
                            <x-text-input type="number" id="order" name="order" class="mt-1 block w-full"
                                :value="old('order')" min="0" placeholder="空白の場合は最後に追加されます" />
                            <x-input-error :messages="$errors->get('order')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                数値が小さいほど上位に表示されます
                            </p>
                        </div>

                        <div class="space-y-4">

                            <div class="flex items-center">
                                <input id="is_active" name="is_active" type="checkbox" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                    有効にする
                                </label>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                                無効にすると、投稿作成時に選択できなくなります
                            </p>
                        </div>

                        <div class="flex items-center justify-end space-x-3">
                            <x-secondary-button as="a" href="{{ route('admin.board-post-types.index') }}">
                                キャンセル
                            </x-secondary-button>
                            <x-primary-button type="submit">
                                <i class="fas fa-save mr-1"></i>作成
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const displayNameInput = document.getElementById('display_name');
        const nameInput = document.getElementById('name');

        displayNameInput.addEventListener('input', function() {
            if (nameInput.value === '' || nameInput.dataset.autoGenerated === 'true') {
                // 表示名をシステム名に変換（英数字とアンダースコアのみ）
                const generatedName = this.value
                    .toLowerCase()
                    .replace(/\s+/g, '_')
                    .replace(/[^a-z0-9_]/g, '')
                    .replace(/_+/g, '_')
                    .replace(/^_|_$/g, '');

                nameInput.value = generatedName;
                nameInput.dataset.autoGenerated = 'true';
            }
        });

        nameInput.addEventListener('input', function() {
            if (this.value !== '') {
                this.dataset.autoGenerated = 'false';
            }
        });
    });
    </script>
    @endpush
@endsection

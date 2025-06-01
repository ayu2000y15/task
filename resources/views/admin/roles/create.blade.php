@extends('layouts.app')

@section('title', 'ロール作成')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                ロール作成
            </h1>
            <x-secondary-button as="a" href="{{ route('admin.roles.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">新しいロール情報</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="ロール名 (システム名)" required />
                            <x-text-input type="text" id="name" name="name" class="mt-1 block w-full" :value="old('name')"
                                required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">半角英数字とアンダースコア (_) のみ使用可能です。(例:
                                project_manager)</p>
                        </div>

                        <div>
                            <x-input-label for="display_name" value="表示名" required />
                            <x-text-input type="text" id="display_name" name="display_name" class="mt-1 block w-full"
                                :value="old('display_name')" required />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">画面に表示される分かりやすい名前を入力してください。(例:
                                プロジェクト管理者)</p>
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('admin.roles.index') }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 作成する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
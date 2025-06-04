@extends('layouts.tool')

@section('title', '新規管理連絡先 作成')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.managed-contacts.index') }}"
        class="hover:text-blue-600 dark:hover:text-blue-400">管理連絡先一覧</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">新規作成</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-3xl mx-auto mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    新規管理連絡先 作成
                </h1>
                <x-secondary-button as="a" href="{{ route('tools.sales.managed-contacts.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
                </x-secondary-button>
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">連絡先情報</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('tools.sales.managed-contacts.store') }}" method="POST">
                    @csrf
                    @include('tools.sales.managed_contacts._form', ['managedContact' => new \App\Models\ManagedContact()])
                    {{-- 空のモデルを渡す --}}

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('tools.sales.managed-contacts.index') }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-plus mr-2"></i> 作成する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
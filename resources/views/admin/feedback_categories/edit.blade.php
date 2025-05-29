@extends('layouts.app')

@section('title', 'フィードバックカテゴリ編集 - ' . $feedbackCategory->name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">フィードバックカテゴリ編集: {{ $feedbackCategory->name }}
            </h1>
            <a href="{{ route('admin.feedback-categories.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> カテゴリ一覧に戻る
            </a>
        </div>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('admin.feedback-categories.update', $feedbackCategory) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-y-6">
                        <div>
                            <x-input-label for="name" value="カテゴリ名" :required="true" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $feedbackCategory->name)" required :hasError="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="flex items-center pt-2">
                            <x-checkbox-input id="is_active" name="is_active" value="1" :checked="old('is_active', $feedbackCategory->is_active)" label="有効にする" />
                            <x-input-error :messages="$errors->get('is_active')" class="mt-0 ml-2" />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end space-x-3">
                        <a href="{{ route('admin.feedback-categories.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            キャンセル
                        </a>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 更新する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
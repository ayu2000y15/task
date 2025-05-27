@extends('layouts.app')

@section('title', '新規工程テンプレート作成')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">新規工程テンプレート作成</h1>
            <x-secondary-button onclick="location.href='{{ route('process-templates.index') }}'">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>一覧へ戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('process-templates.store') }}" method="POST">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="テンプレート名" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')"
                                required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="description" value="説明" />
                            <x-textarea-input id="description" name="description" class="mt-1 block w-full"
                                rows="4">{{ old('description') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <x-primary-button>
                            <i class="fas fa-plus mr-2"></i>作成して工程項目を編集
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
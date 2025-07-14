@extends('layouts.app')

@section('title', 'フォームカテゴリ編集: ' . $formCategory->display_name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                フォームカテゴリ編集: <span class="font-normal">{{ $formCategory->display_name }}</span>
            </h1>
            <x-secondary-button as="a" href="{{ route('admin.form-categories.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('admin.form-categories.update', $formCategory) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('admin.form_categories._form', ['formCategory' => $formCategory])
                </form>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'カスタム項目定義作成')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">カスタム項目定義 作成</h1>
            <x-secondary-button as="a" :href="route('admin.form-definitions.index', ['category' => $category])">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('admin.form-definitions.store') }}" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="category" value="{{ $category }}">
                    @include('admin.form_definitions._form', ['formFieldDefinition' => new \App\Models\FormFieldDefinition(), 'optionsText' => ''])
                </form>
            </div>
        </div>
    </div>
@endsection
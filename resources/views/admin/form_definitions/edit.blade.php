@extends('layouts.app')

@section('title', 'カスタム項目定義編集: ' . $formFieldDefinition->label)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">カスタム項目定義 編集: <span
                    class="font-normal">{{ $formFieldDefinition->label }}</span></h1>
            <x-secondary-button as="a" :href="route('admin.form-definitions.index')">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                {{-- $formFieldDefinition がnullでないこと、idプロパティが存在することが前提です --}}
                @if($formFieldDefinition && $formFieldDefinition->exists) {{-- idの存在確認よりもexistsでモデルがDBに存在するかを確認する方が堅牢 --}}
                    <form action="{{ route('admin.form-definitions.update', $formFieldDefinition) }}" {{-- ★
                        パラメータ名を修正し、モデルインスタンスを直接渡す形に変更 --}} method="POST">
                        @method('PUT')
                        {{-- _form.blade.php をインクルードしてフォームの本体を表示 --}}
                        {{-- $fieldTypes と $optionsText はコントローラーから渡される想定 --}}
                        @include('admin.form_definitions._form', compact('formFieldDefinition', 'fieldTypes', 'optionsText'))
                    </form>
                @else
                    {{-- $formFieldDefinition またはそのIDが利用できない場合のエラー表示や代替処理 --}}
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">エラー:</strong>
                        <span class="block sm:inline">編集対象のフォーム定義が見つかりません。</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
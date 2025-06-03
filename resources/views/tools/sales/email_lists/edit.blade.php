@extends('layouts.tool')

@section('title', 'メールリスト編集 - ' . $emailList->name)

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.email-lists.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">メールリスト管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200 truncate" title="{{ $emailList->name }}">{{ Str::limit($emailList->name, 20) }}</span>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">編集</span>
@endsection

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- ページ上部のタイトルとボタンセクションの幅調整 --}}
    <div class="max-w-2xl mx-auto mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                メールリスト編集: <span class="font-normal text-xl truncate" title="{{ $emailList->name }}">{{ Str::limit($emailList->name, 30) }}</span>
            </h1>
            <div class="flex space-x-2 flex-shrink-0">
                <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> メールリスト一覧へ戻る
                </x-secondary-button>
                @can('tools.sales.access') {{-- 適切な権限名に置き換えてください --}}
                    <form action="{{ route('tools.sales.email-lists.destroy', $emailList) }}" method="POST" class="inline-block"
                          onsubmit="return confirm('本当に「{{ $emailList->name }}」を削除しますか？この操作は元に戻せません。');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit" class="py-1 px-3 text-xs"> {{-- ボタンサイズ調整 --}}
                            <i class="fas fa-trash mr-1"></i>削除
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="max-w-2xl mx-auto mb-4"> {{-- エラーメッセージも幅を合わせる --}}
            <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
                <p class="font-bold">エラーが発生しました。</p>
                <ul class="list-disc list-inside text-sm mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">リスト情報編集</h2>
        </div>
        <div class="p-6 sm:p-8">
            <form action="{{ route('tools.sales.email-lists.update', $emailList) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-6">
                    {{-- リスト名 --}}
                    <div>
                        <x-input-label for="name" value="リスト名" :required="true" />
                        <x-text-input type="text" id="name" name="name" class="mt-1 block w-full"
                            :value="old('name', $emailList->name)" required :hasError="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- 説明 --}}
                    <div>
                        <x-input-label for="description" value="説明" />
                        <x-textarea-input id="description" name="description" class="mt-1 block w-full"
                            rows="4" :hasError="$errors->has('description')">{{ old('description', $emailList->description) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> 更新する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
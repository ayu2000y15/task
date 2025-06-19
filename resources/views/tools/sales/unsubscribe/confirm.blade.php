@extends('layouts.guest')

@section('title', '配信停止の確認')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg p-8 text-center shadow-md">

        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
            <i class="fas fa-exclamation-triangle fa-2x text-yellow-600"></i>
        </div>

        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">配信停止の確認</h1>

        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            メールアドレス <strong class="dark:text-gray-300">{{ $email ?? '不明なアドレス' }}</strong> へのメール配信を本当に停止しますか？
        </p>

        <form method="POST" action="{{ route('unsubscribe.process') }}">
            @csrf
            <input type="hidden" name="identifier" value="{{ $identifier }}">

            <button type="submit"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 transition-colors duration-200">
                はい、配信を停止する
            </button>
        </form>

        <div class="mt-4">
            {{-- トップページなど、適切なキャンセル先のURLに変更してください --}}
            <a href="{{ url('/') }}"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                キャンセル
            </a>
        </div>

    </div>
@endsection
@extends('layouts.guest') {{-- または適切な公開向けレイアウト --}}

@section('title', 'ご依頼ありがとうございます')

@section('content')
    <div class="text-center">
        <div class="mb-4 text-6xl text-green-500">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4">ご依頼ありがとうございます</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            衣装製作のご依頼を承りました。<br>
            担当者より追ってご連絡させていただきますので、今しばらくお待ちください。
        </p>
        <a href="{{ route('external-form.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
            続けて別の依頼をする
        </a>
        {{-- <a href="/"
            class="ml-4 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">トップページへ戻る</a>
        --}}
    </div>
@endsection
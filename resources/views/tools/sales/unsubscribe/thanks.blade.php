@extends('layouts.guest')

@section('title', '配信停止手続き')

@section('content')
    <div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg p-8 text-center">
        @if(isset($error) && $error)
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-times fa-2x text-red-600"></i>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">配信停止処理エラー</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $message ?? '配信停止処理中に問題が発生しました。お手数ですが、再度お試しいただくか、管理者にご連絡ください。' }}
            </p>
        @elseif(isset($alreadyUnsubscribed) && $alreadyUnsubscribed)
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <i class="fas fa-info-circle fa-2x text-blue-600"></i>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">配信停止済み</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                メールアドレス <strong class="dark:text-gray-300">{{ $email ?? '' }}</strong> は、<br>
                {{-- @if(isset($listName) && $listName)
                メールリスト「<strong class="dark:text-gray-300">{{ $listName }}</strong>」から
                @endif --}}
                既に配信停止されています。
            </p>
        @else
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                <i class="fas fa-check fa-2x text-green-600"></i>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">配信停止手続き完了</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                メールアドレス <strong class="dark:text-gray-300">{{ $email ?? '' }}</strong> への<br>
                {{-- @if(isset($listName) && $listName)
                メールリスト「<strong class="dark:text-gray-300">{{ $listName }}</strong>」からの
                @endif --}}
                メール配信を停止しました。
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                反映までにお時間がかかる場合がございます。
            </p>
        @endif

        {{-- <div class="mt-6">
            <a href="{{ url('/') }}"
                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 hover:underline">
                トップページへ戻る
            </a>
        </div> --}}
    </div>
@endsection
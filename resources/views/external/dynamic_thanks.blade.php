<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信完了 - {{ $formCategory->form_title ?: $formCategory->display_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-2xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            {{-- 成功アイコン --}}
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-8">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            {{-- メッセージ --}}
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                送信が完了しました
            </h1>

            @if($formCategory->thank_you_title)
                <h2 class="text-xl font-semibold text-gray-700 mb-6">
                    {{ $formCategory->thank_you_title }}
                </h2>
            @endif

            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                @if($formCategory->thank_you_message)
                    <div class="text-gray-600 text-lg leading-relaxed mb-6">
                        {!! nl2br(e($formCategory->thank_you_message)) !!}
                    </div>
                @else
                    <div class="text-gray-600 text-lg leading-relaxed mb-6">
                        <p>{{ $formCategory->form_title ?: $formCategory->display_name }}のお申し込みありがとうございました。</p>

                        @if($formCategory->requires_approval)
                            <p class="mt-4">
                                お申し込み内容を確認させていただき、後日担当者よりご連絡いたします。<br>
                                なお、内容によってはお時間をいただく場合がございますので、予めご了承ください。
                            </p>
                        @else
                            <p class="mt-4">
                                お申し込み内容を確認後、担当者よりご連絡いたします。
                            </p>
                        @endif
                    </div>
                @endif

                {{-- 申請ステータス --}}
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-900">申請ステータス</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ now()->format('Y年n月j日 H:i') }} に受付</p>
                        </div>
                        <div>
                            @if($formCategory->requires_approval)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    承認待ち
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    受付完了
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 連絡先情報（もしあれば） --}}
            @if($formCategory->notification_emails)
                <div class="bg-blue-50 rounded-lg p-6 mb-8">
                    <h3 class="font-medium text-blue-900 mb-2">お問い合わせ</h3>
                    <p class="text-sm text-blue-700">
                        ご不明な点がございましたら、下記までお気軽にお問い合わせください。
                    </p>
                    <div class="mt-3">
                        @foreach($formCategory->notification_emails as $email)
                            <a href="mailto:{{ $email }}" class="text-blue-600 hover:text-blue-500 text-sm">
                                {{ $email }}
                            </a>
                            @if(!$loop->last)<br>@endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- フッター --}}
        <div class="text-center mt-12 text-gray-500 text-sm">
            <p>{{ config('app.name') }}</p>
        </div>
    </div>
</body>

</html>

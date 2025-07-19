<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formCategory->thank_you_title ?: 'お申し込みありがとうございました' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            {{-- 成功アイコン --}}
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            {{-- タイトル --}}
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                {{ $formCategory->thank_you_title ?: 'お申し込みありがとうございました' }}
            </h1>

            {{-- メッセージ --}}
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                @if($formCategory->thank_you_message)
                    <div class="text-lg text-gray-700 leading-relaxed">
                        {!! nl2br(e($formCategory->thank_you_message)) !!}
                    </div>
                @else
                    <div class="text-lg text-gray-700 leading-relaxed">
                        <p class="mb-4">
                            {{ $formCategory->form_title ?: $formCategory->display_name }}へのお申し込みを受け付けました。
                        </p>

                        @if($formCategory->requires_approval)
                            <p class="mb-4">
                                ご提出いただいた内容を確認の上、担当者よりご連絡させていただきます。
                            </p>
                        @else
                            <p class="mb-4">
                                ご提出いただいた内容をもとに、担当者よりご連絡させていただきます。
                            </p>
                        @endif

                        <p>
                            しばらくお待ちください。
                        </p>
                    </div>
                @endif
            </div>

            @if($formCategory->delivery_estimate_text)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8 text-center">
                    <p class="text-blue-800 font-semibold">
                        <i class="fas fa-shipping-fast mr-2"></i>
                        お申込みいただいた製品は、<strong>{{ $formCategory->delivery_estimate_text }}</strong>ごろの到着を予定しております。
                    </p>
                </div>
            @endif

            {{-- 注意事項 --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-blue-900 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>
                    ご注意事項
                </h2>
                <div class="text-left text-sm text-blue-800 space-y-2">
                    <p>• お申し込み内容の確認メールをお送りしております。</p>
                    <p>• 届かない場合は迷惑メールフォルダもご確認ください。</p>
                    <p>• ご不明な点がございましたら、お気軽にお問い合わせください。</p>
                    @if($formCategory->requires_approval)
                        <p>• 確認にお時間をいただく場合がございます。予めご了承ください。</p>
                    @endif
                </div>
            </div>

            {{-- 戻るボタン --}}
            {{-- <div class="space-y-4">
                <a href="{{ route('external-form.show', $formCategory->slug) }}"
                    class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    フォームに戻る
                </a>
            </div> --}}
        </div>

        {{-- フッター --}}
        <div class="text-center mt-12 text-gray-500 text-sm">
            <p>{{ config('app.name') }}</p>
        </div>
    </div>
</body>

</html>
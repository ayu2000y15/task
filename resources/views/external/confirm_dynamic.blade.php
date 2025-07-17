<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>入力内容の確認 - {{ $formCategory->form_title ?: $formCategory->display_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Font Awesomeの読み込み（必要に応じて） --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                入力内容の確認
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300">
                以下の内容でお間違いありませんか？
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <div class="p-8 space-y-6">
                {{-- 依頼基本情報 --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">依頼基本情報</h2>
                    <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">お名前</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                {{ $validatedData['submitter_name'] }}
                            </dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">メールアドレス</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                {{ $validatedData['submitter_email'] }}
                            </dd>
                        </div>
                        <div class="hidden">
                            <div class=" py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">備考・ご要望など</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0 ">
                                    {!! nl2br($validatedData['submitter_notes']) ?: '入力なし' !!}
                                </dd>
                            </div>
                        </div>
                    </dl>
                </div>

                {{-- カスタム項目 --}}
                @if($customFormFields->count() > 0)
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">詳細情報</h2>
                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($customFormFields as $field)
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $field->label }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0 break-words">
                                        @php $value = $displayData[$field->name] ?? null; @endphp

                                        @if(in_array($field->type, ['file', 'file_multiple']) && !empty($value))
                                            <div class="space-y-3">
                                                @foreach($value as $file)
                                                    <div
                                                        class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                                        @if(isset($file['preview_src']))
                                                            <img src="{{ $file['preview_src'] }}" alt="プレビュー"
                                                                class="w-16 h-16 object-cover rounded-md mr-4">
                                                        @else
                                                            <div
                                                                class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-md flex items-center justify-center mr-4 flex-shrink-0">
                                                                <i class="fas fa-file-alt text-3xl text-gray-500 dark:text-gray-400"></i>
                                                            </div>
                                                        @endif
                                                        <div class="flex-grow min-w-0">
                                                            <p class="font-medium truncate">{{ $file['original_name'] }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ round($file['size'] / 1024, 2) }} KB
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($field->type === 'image_select' && !empty($value) && is_array($value))
                                            <div class="flex flex-wrap gap-4">
                                                @foreach($value as $image)
                                                    <div class="text-center">
                                                        {{-- ★ asset()ヘルパーを削除し、直接URLを使用 --}}
                                                        <img src="{{ $image['url'] }}" alt="{{ $image['label'] }}" class="w-24 h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                                        <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">{{ $image['label'] }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($field->type === 'textarea' && !empty($value))
                                            <div>{!! nl2br(e($value)) !!}</div>
                                        @elseif($field->type === 'url' && !empty($value))
                                            <a href="{{ $value }}" target="_blank" rel="noopener noreferrer"
                                                class="text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                                                {{ $value }}
                                            </a>
                                        @elseif(is_array($value))
                                            {{ !empty($value) ? implode(', ', $value) : '入力なし' }}
                                        @else
                                            {{ $value ?: '入力なし' }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>

            <div class="px-8 py-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                <form method="POST" action="{{ route('external-form.store', $formCategory->slug) }}">
                    @csrf
                    <div class="flex flex-col sm:flex-row justify-end space-y-4 sm:space-y-0 sm:space-x-4">
                        <button type="submit" name="action" value="back"
                            class="w-full sm:w-auto bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 py-3 px-6 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200 font-medium">
                            修正する
                        </button>
                        <button type="submit" name="action" value="submit"
                            class="w-full sm:w-auto bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 transition duration-200 font-semibold text-lg">
                            この内容で送信する
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>

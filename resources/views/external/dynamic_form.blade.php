<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formCategory->form_title ?: $formCategory->display_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        {{-- ヘッダー --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                {{ $formCategory->form_title ?: $formCategory->display_name }}
            </h1>
            @if($formCategory->form_description)
                <p class="text-lg text-gray-600 leading-relaxed">
                    {!! nl2br(e($formCategory->form_description)) !!}
                </p>
            @endif
        </div>

        {{-- エラー表示 --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">入力エラーがあります</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- フォーム --}}
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <form method="POST" action="{{ route('external-form.store', $formCategory->slug) }}" enctype="multipart/form-data" class="p-8 space-y-6">
                @csrf

                {{-- 依頼基本情報 --}}
                <div class="border-b border-gray-200 pb-8 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">依頼基本情報</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- お名前 --}}
                        <div class="form-group">
                            <label for="submitter_name" class="block text-sm font-medium text-gray-700 mb-2">
                                お名前 <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="submitter_name"
                                   name="submitter_name"
                                   value="{{ old('submitter_name') }}"
                                   placeholder="山田 太郎"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('submitter_name') border-red-500 @enderror"
                                   required>
                            @error('submitter_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- メールアドレス --}}
                        <div class="form-group">
                            <label for="submitter_email" class="block text-sm font-medium text-gray-700 mb-2">
                                メールアドレス <span class="text-red-500">*</span>
                            </label>
                            <input type="email"
                                   id="submitter_email"
                                   name="submitter_email"
                                   value="{{ old('submitter_email') }}"
                                   placeholder="example@email.com"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('submitter_email') border-red-500 @enderror"
                                   required>
                            @error('submitter_email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- 備考・ご要望など --}}
                    <div class="form-group mt-6">
                        <label for="submitter_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            備考・ご要望など
                        </label>
                        <textarea id="submitter_notes"
                                  name="submitter_notes"
                                  rows="4"
                                  placeholder="ご質問やご要望がございましたらお聞かせください。"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('submitter_notes') border-red-500 @enderror">{{ old('submitter_notes') }}</textarea>
                        @error('submitter_notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- カスタム項目セクション --}}
                @if($customFormFields && count($customFormFields) > 0)
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">詳細情報</h2>

                        @foreach($customFormFields as $field)
                            <div class="form-group mb-6">
                                <label for="custom_{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ $field['label'] }}
                                    @if($field['is_required'])
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                @if($field['help_text'])
                                    <p class="text-sm text-gray-500 mb-2">{{ $field['help_text'] }}</p>
                                @endif

                                @switch($field['type'])
                                    @case('text')
                                    @case('email')
                                    @case('number')
                                    @case('date')
                                    @case('tel')
                                    @case('url')
                                        <input type="{{ $field['type'] }}"
                                               id="custom_{{ $field['name'] }}"
                                               name="custom_{{ $field['name'] }}"
                                               value="{{ old('custom_' . $field['name']) }}"
                                               placeholder="{{ $field['placeholder'] }}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('custom_' . $field['name']) border-red-500 @enderror"
                                               @if($field['is_required']) required @endif>
                                        @break

                                    @case('textarea')
                                        <textarea id="custom_{{ $field['name'] }}"
                                                  name="custom_{{ $field['name'] }}"
                                                  rows="4"
                                                  placeholder="{{ $field['placeholder'] }}"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('custom_' . $field['name']) border-red-500 @enderror"
                                                  @if($field['is_required']) required @endif>{{ old('custom_' . $field['name']) }}</textarea>
                                        @break

                                    @case('select')
                                        <select id="custom_{{ $field['name'] }}"
                                                name="custom_{{ $field['name'] }}"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('custom_' . $field['name']) border-red-500 @enderror"
                                                @if($field['is_required']) required @endif>
                                            <option value="">選択してください</option>
                                            @if($field['options'])
                                                @foreach(explode(',', $field['options']) as $option)
                                                    @php
                                                        $parts = explode(':', $option, 2);
                                                        $value = trim($parts[0]);
                                                        $label = count($parts) > 1 ? trim($parts[1]) : $value;
                                                    @endphp
                                                    <option value="{{ $value }}" @if(old('custom_' . $field['name']) == $value) selected @endif>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @break

                                    @case('radio')
                                        <div class="space-y-3">
                                            @if($field['options'])
                                                @foreach(explode(',', $field['options']) as $option)
                                                    @php
                                                        $parts = explode(':', $option, 2);
                                                        $value = trim($parts[0]);
                                                        $label = count($parts) > 1 ? trim($parts[1]) : $value;
                                                    @endphp
                                                    <div class="flex items-center">
                                                        <input type="radio"
                                                               id="custom_{{ $field['name'] }}_{{ $loop->index }}"
                                                               name="custom_{{ $field['name'] }}"
                                                               value="{{ $value }}"
                                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                                               @if(old('custom_' . $field['name']) == $value) checked @endif
                                                               @if($field['is_required']) required @endif>
                                                        <label for="custom_{{ $field['name'] }}_{{ $loop->index }}" class="ml-3 text-sm text-gray-700">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        @break

                                    @case('checkbox')
                                        <div class="space-y-3">
                                            @if($field['options'])
                                                @foreach(explode(',', $field['options']) as $option)
                                                    @php
                                                        $parts = explode(':', $option, 2);
                                                        $value = trim($parts[0]);
                                                        $label = count($parts) > 1 ? trim($parts[1]) : $value;
                                                        $oldValues = old('custom_' . $field['name'], []);
                                                    @endphp
                                                    <div class="flex items-center">
                                                        <input type="checkbox"
                                                               id="custom_{{ $field['name'] }}_{{ $loop->index }}"
                                                               name="custom_{{ $field['name'] }}[]"
                                                               value="{{ $value }}"
                                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                               @if(in_array($value, $oldValues)) checked @endif>
                                                        <label for="custom_{{ $field['name'] }}_{{ $loop->index }}" class="ml-3 text-sm text-gray-700">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        @break

                                    @case('file')
                                        <input type="file"
                                               id="custom_{{ $field['name'] }}"
                                               name="custom_{{ $field['name'] }}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('custom_' . $field['name']) border-red-500 @enderror"
                                               @if($field['is_required']) required @endif>
                                        @break

                                    @case('file_multiple')
                                        <input type="file"
                                               id="custom_{{ $field['name'] }}"
                                               name="custom_{{ $field['name'] }}[]"
                                               multiple
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('custom_' . $field['name']) border-red-500 @enderror"
                                               @if($field['is_required']) required @endif>
                                        <p class="mt-2 text-sm text-gray-500">複数のファイルを選択できます</p>
                                        @break
                                @endswitch

                                @error('custom_' . $field['name'])
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- 送信ボタン --}}
                <div class="pt-6 border-t border-gray-200">
                    <button type="submit"
                            class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-500 focus:ring-opacity-50 transition duration-200 font-medium text-lg">
                        送信する
                    </button>
                </div>
            </form>
        </div>

        {{-- フッター --}}
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>

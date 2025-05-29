@extends('layouts.guest')

@section('title', '衣装製作依頼')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6 text-center">衣装製作依頼フォーム</h1>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700/50 dark:text-red-200 dark:border-red-600">
            <div class="font-bold">入力内容にエラーがあります。</div>
            <ul class="mt-1 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('external-form.store') }}" method="POST" enctype="multipart/form-data"> {{-- enctypeを追加 --}}
        @csrf
        <div class="space-y-6">
            <div>
                <x-input-label for="submitter_name" value="お名前" />
                <x-text-input id="submitter_name" name="submitter_name" type="text" class="mt-1 block w-full" :value="old('submitter_name')" :hasError="$errors->has('submitter_name')" />
                <x-input-error :messages="$errors->get('submitter_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="submitter_email" value="メールアドレス" />
                <x-text-input id="submitter_email" name="submitter_email" type="email" class="mt-1 block w-full" :value="old('submitter_email')" :hasError="$errors->has('submitter_email')" />
                <x-input-error :messages="$errors->get('submitter_email')" class="mt-2" />
            </div>

            @if(!empty($customFormFields))
                <div class="pt-4 border-t dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">依頼内容詳細</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">以下の項目にご入力ください。</p>
                    @foreach ($customFormFields as $field)
                        @php
                            $inputName = 'custom_fields[' . $field['name'] . ']';
                            $fileInputName = ($field['type'] === 'file_multiple') ? $inputName . '[]' : $inputName;
                            $errorKey = 'custom_fields.' . $field['name'];
                        @endphp
                        <div class="mb-4"> {{-- 各フィールドをdivで囲む --}}
                            <x-input-label :for="$field['name']" :value="$field['label']" :required="$field['required']" />
                            @switch($field['type'])
                                @case('text')
                                @case('number')
                                @case('date')
                                @case('color')
                                @case('tel')   {{-- ★追加 --}}
                                @case('email') {{-- ★追加 --}}
                                @case('url')   {{-- ★追加 --}}
                                    <x-text-input :id="$field['name']" :name="$inputName" :type="$field['type']" class="mt-1 block w-full"
                                                  :value="old($errorKey)" :required="$field['required']" :placeholder="$field['placeholder'] ?? ''"
                                                  :maxlength="$field['maxlength'] ?? null" :hasError="$errors->has($errorKey)" />
                                    @break
                                @case('textarea')
                                    <x-textarea-input :id="$field['name']" :name="$inputName" class="mt-1 block w-full"
                                                      :required="$field['required']" :placeholder="$field['placeholder'] ?? ''"
                                                      :maxlength="$field['maxlength'] ?? null" :hasError="$errors->has($errorKey)">{{ old($errorKey) }}</x-textarea-input>
                                    @break
                                @case('select')
                                    @php
                                        $optionsArray = [];
                                        if (!empty($field['options_string'])) { // ExternalFormControllerから渡されるのは options_string
                                            $pairs = explode(',', $field['options_string']);
                                            foreach ($pairs as $pair) {
                                                $parts = explode(':', trim($pair), 2);
                                                $optionsArray[trim($parts[0])] = count($parts) > 1 ? trim($parts[1]) : trim($parts[0]);
                                            }
                                        }
                                    @endphp
                                    <x-select-input :id="$field['name']" :name="$inputName" class="mt-1 block w-full"
                                                    :options="$optionsArray" :selected="old($errorKey)"
                                                    :required="$field['required']" :emptyOptionText="$field['placeholder'] ?: '選択してください'"
                                                    :hasError="$errors->has($errorKey)" />
                                    @break
                                @case('checkbox')
                                    <div class="mt-1">
                                        <x-checkbox-input :id="$field['name']" :name="$inputName" value="1"
                                                          :checked="old($errorKey, false)" :label="$field['label']" {{-- ラベルは重複するのでここでは空でも可 --}}
                                                          :hasError="$errors->has($errorKey)" />
                                    </div>
                                    @break
                                @case('file_multiple') {{-- ★追加: ファイル（複数） --}}
                                     <input type="file" id="{{ $field['name'] }}" name="{{ $fileInputName }}" multiple
                                           class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-md file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-indigo-50 file:text-indigo-700
                                                  dark:file:bg-indigo-700/20 dark:file:text-indigo-300
                                                  hover:file:bg-indigo-100 dark:hover:file:bg-indigo-600/30
                                                  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800
                                                  {{ $errors->has($errorKey) || $errors->has($errorKey.'.*') ? 'border-red-500 dark:border-red-600 ring-red-500' : 'border-gray-300 dark:border-gray-600' }} rounded-md shadow-sm">
                                     <x-input-error :messages="$errors->get($errorKey)" class="mt-2" /> {{-- 配列全体のバリデーションエラー --}}
                                     @foreach($errors->get($errorKey.'.*') as $message) {{-- 個別ファイルのエラー --}}
                                         <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                     @endforeach
                                    @break
                                @default
                                    <p class="text-red-500 mt-1">未対応のフィールドタイプです: {{ $field['type'] }}</p>
                            @endswitch
                            @if($fieldType !== 'checkbox' && $fieldType !== 'file_multiple') {{-- file_multipleのエラーは個別表示するので除外 --}}
                                <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div>
                <x-input-label for="submitter_notes" value="備考・ご要望など" />
                <x-textarea-input id="submitter_notes" name="submitter_notes" class="mt-1 block w-full" rows="4">{{ old('submitter_notes') }}</x-textarea-input>
                <x-input-error :messages="$errors->get('submitter_notes')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end mt-8 pt-6 border-t dark:border-gray-700">
            <x-primary-button type="submit">
                <i class="fas fa-paper-plane mr-2"></i> 上記内容で依頼する
            </x-primary-button>
        </div>
    </form>
@endsection
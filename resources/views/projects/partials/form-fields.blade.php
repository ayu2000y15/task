@props([
    'field',
    'project' => null,
    'fieldNamePrefix' => 'attributes',
    'prefillValues' => []
])

@php
    // --- 変数準備 ---
    $baseFieldName = $field['name'];
    $fieldLabel = $field['label'];
    $fieldType = $field['type'];
    $fieldRequired = $field['is_required'] ?? ($field['required'] ?? false);
    $fieldPlaceholder = $field['placeholder'] ?? '';
    $maxLength = isset($field['maxlength']) && is_numeric($field['maxlength']) ? (int) $field['maxlength'] : null;

    // 入力フィールドのname属性を構築 (例: attributes[field_name])
    $inputName = $fieldNamePrefix . '[' . $baseFieldName . ']';
    // チェックボックスグループや複数ファイル用の配列名 (例: attributes[field_name][])
    $inputNameArray = $inputName . '[]';

    // バリデーションエラーメッセージを取得するためのキー (例: attributes.field_name)
    $errorDotKey = str_replace(['[', ']'], ['.', ''], rtrim($inputName, '[]'));

    // --- 選択肢の処理 (★文字化け修正の核心) ---
    // optionsはJSON文字列で保存されていることを想定し、json_decodeでPHP配列に変換する
    $fieldOptions = [];
    if (!empty($field['options'])) {
        $decoded = is_array($field['options']) ? $field['options'] : json_decode($field['options'], true);
        if (is_array($decoded)) {
            $fieldOptions = $decoded;
        }
    }

    // --- 値の決定（優先順位: old > prefill > 既存DB値） ---
    $currentValue = old(
        $errorDotKey,
        $prefillValues[$baseFieldName]
        ?? ($project->attributes[$baseFieldName] ?? null)
    );
@endphp

{{-- 全体をdivで囲み、フィールドタイプに応じてclassを付与 --}}
<div class="form-field-group field-type-{{ $fieldType }}">

    {{-- ラベル（チェックボックスグループと単体チェックボックス以外で表示） --}}
    @if ($fieldType !== 'checkbox')
        <x-input-label :for="$baseFieldName" :value="$fieldLabel" :required="$fieldRequired" />
    @endif

    {{-- ヘルプテキスト --}}
    @if(!empty($field['help_text']))
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $field['help_text'] }}</p>
    @endif

    {{-- 入力フィールド本体 --}}
    <div class="mt-2">
        @switch($fieldType)
            @case('text')
            @case('number')
            @case('color')
            @case('date')
            @case('tel')
            @case('email')
            @case('url')
                <x-text-input :id="$baseFieldName" :name="$inputName" :type="$fieldType" class="w-full"
                              :value="$currentValue" :required="$fieldRequired" :placeholder="$fieldPlaceholder"
                              :maxlength="$maxLength" :hasError="$errors->has($errorDotKey)"/>
                @break

            @case('textarea')
                <x-textarea-input :id="$baseFieldName" :name="$inputName" class="w-full"
                                  :required="$fieldRequired" :placeholder="$fieldPlaceholder"
                                  :maxlength="$maxLength" :hasError="$errors->has($errorDotKey)">{{ $currentValue }}</x-textarea-input>
                @break

            @case('select')
                <x-select-input :id="$baseFieldName" :name="$inputName" class="w-full"
                                :options="$fieldOptions" :selected="$currentValue"
                                :required="$fieldRequired" :emptyOptionText="$fieldPlaceholder ?: '選択してください'"
                                :hasError="$errors->has($errorDotKey)"/>
                @break

            @case('radio')
                <div class="space-y-2 pt-1">
                    @forelse($fieldOptions as $optionValue => $optionLabel)
                        <label class="flex items-center">
                            <input type="radio" name="{{ $inputName }}" value="{{ $optionValue }}"
                                   class="form-radio h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:bg-gray-700 dark:border-gray-600"
                                   @if($currentValue == $optionValue) checked @endif >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">{{ $optionLabel }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-yellow-600 dark:text-yellow-400">選択肢が設定されていません。</p>
                    @endforelse
                </div>
                @break

            @case('checkbox')
                @if(!empty($fieldOptions))
                    {{-- 複数の選択肢がある場合 (チェックボックスグループ) --}}
                    <div class="space-y-2">
                         <x-input-label :value="$fieldLabel" :required="$fieldRequired" class="mb-1"/>
                        @foreach($fieldOptions as $optionValue => $optionLabel)
                            @php
                                $checkedValues = is_array($currentValue) ? $currentValue : [];
                            @endphp
                            <label class="flex items-center">
                                <input type="checkbox" name="{{ $inputNameArray }}" value="{{ $optionValue }}"
                                       class="form-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                                       @if(in_array($optionValue, $checkedValues)) checked @endif >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">{{ $optionLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    {{-- 選択肢がない場合 (単一のOn/Offチェックボックス) --}}
                    <label class="flex items-center">
                        <input type="hidden" name="{{ $inputName }}" value="0">
                        <input type="checkbox" id="{{ $baseFieldName }}" name="{{ $inputName }}" value="1"
                               class="form-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                               @if(filter_var($currentValue, FILTER_VALIDATE_BOOLEAN)) checked @endif >
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $fieldLabel }}</span>
                    </label>
                @endif
                @break

            @case('image_select')
                @php
                    $min = $field['min_selections'] ?? null;
                    $max = $field['max_selections'] ?? null;
                    // old()やprefillされた値、またはDBの既存値を使用
                    $checkedValues = is_array($currentValue) ? $currentValue : [];
                @endphp

                @if($max)
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                        <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                        最大{{ $max }}個まで選択できます。
                    </p>
                @endif

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"
                     x-data="imageSelector({ max: '{{ $max ?? '' }}' })"
                     x-init="check()">
                    @forelse($fieldOptions as $optionValue => $imageUrl)
                        <label
                            class="block border rounded-lg overflow-hidden transition-all duration-200 bg-white dark:bg-gray-700/50 cursor-pointer"
                            :class="{ 'ring-2 ring-blue-500 border-blue-500 shadow-md': $el.querySelector('input[type=checkbox]').checked }">

                            <div class="bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-2 aspect-square">
                                <img src="{{ $imageUrl }}" alt="{{ $optionValue }}" class="max-w-full h-40 object-contain">
                            </div>

                            <div class="p-3 border-t dark:border-gray-600">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                        name="{{ $inputNameArray }}"
                                        value="{{ $optionValue }}"
                                        x-on:click="check()"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                                        @if(in_array($optionValue, $checkedValues)) checked @endif>

                                    <span class="ml-2 block text-sm font-medium text-gray-800 dark:text-gray-200 truncate" title="{{ $optionValue }}">
                                        {{ $optionValue }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <p class="text-xs text-yellow-600 dark:text-yellow-400 col-span-full">選択肢が設定されていません。</p>
                    @endforelse
                </div>
                <div x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></div>
                @break
            @case('file')
            @case('file_multiple')
                {{-- 新規作成画面で、かつ外部申請からのプリフィルデータがある場合に表示 --}}
                @if(!$project && !empty($currentValue))
                    <div class="mb-4 text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-semibold mb-2"><i class="fas fa-copy mr-1 text-blue-500"></i>以下のファイルが案件にコピーされます：</p>
                        @php
                            // fileタイプは単一の配列、file_multipleは配列の配列なので、常にループできるよう正規化
                            $prefilledFiles = ($fieldType === 'file_multiple') ? $currentValue : [$currentValue];
                        @endphp
                        <ul class="list-disc list-inside pl-4 space-y-1 bg-gray-50 dark:bg-gray-900 p-3 rounded-md border dark:border-gray-700">
                            @foreach($prefilledFiles as $key => $fileInfo)
                                @if(is_array($fileInfo) && !empty($fileInfo['path']))
                                    <li class="flex items-center justify-between">
                            <div class="flex-grow">
                                <i class="fas fa-file-alt mr-1 text-gray-400"></i>
                                <span>{{ $fileInfo['original_name'] ?? basename($fileInfo['path']) }}</span>
                                <span class="text-gray-400 text-xs">({{ \Illuminate\Support\Number::fileSize($fileInfo['size'] ?? 0) }})</span>

                                {{-- 隠しフィールドは変更なし --}}
                                <input type="hidden" name="{{ $fieldNamePrefix }}[_prefilled_files][{{ $baseFieldName }}][{{ $key }}][path]" value="{{ $fileInfo['path'] }}">
                                <input type="hidden" name="{{ $fieldNamePrefix }}[_prefilled_files][{{ $baseFieldName }}][{{ $key }}][original_name]" value="{{ $fileInfo['original_name'] }}">
                                <input type="hidden" name="{{ $fieldNamePrefix }}[_prefilled_files][{{ $baseFieldName }}][{{ $key }}][size]" value="{{ $fileInfo['size'] }}">
                                <input type="hidden" name="{{ $fieldNamePrefix }}[_prefilled_files][{{ $baseFieldName }}][{{ $key }}][mime_type]" value="{{ $fileInfo['mime_type'] }}">
                            </div>
                            {{-- 「コピーしない」チェックボックスを追加 --}}
                            <label class="ml-4 text-xs flex-shrink-0">
                                <input type="checkbox" name="{{ $fieldNamePrefix }}[_delete_prefilled_files][]" value="{{ $fileInfo['path'] }}" class="form-checkbox h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-1 text-red-600 dark:text-red-400">コピーしない</span>
                            </label>
                        </li>                                    @endif
                                @endforeach
                            </ul>
                        <p class="text-xs text-gray-500 mt-2">※ここからファイルを追加でアップロードすることも可能です。</p>
                    </div>
                @endif

                <input type="file" id="{{ $baseFieldName }}" name="{{ $fieldType === 'file_multiple' ? $inputNameArray : $inputName }}"
                    @if($fieldType === 'file_multiple') multiple @endif
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                            file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-700/20 dark:file:text-indigo-300
                            hover:file:bg-indigo-100 dark:hover:file:bg-indigo-600/30
                            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">

                @if($project && !empty($currentValue))
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <p class="font-medium mb-1">現在のファイル:</p>
                        @php
                            $files = ($fieldType === 'file_multiple') ? $currentValue : [$currentValue];
                        @endphp
                        <ul class="list-disc list-inside pl-4 space-y-1">
                            @foreach($files as $fileInfo)
                                @if(is_array($fileInfo) && !empty($fileInfo['path']))
                                    <li>
                                        <a href="{{ Storage::url($fileInfo['path']) }}" target="_blank" class="text-blue-600 hover:underline" title="ダウンロード: {{ $fileInfo['original_name'] }}">
                                            <i class="fas fa-file-download mr-1"></i>{{ Str::limit($fileInfo['original_name'], 30) }}
                                        </a>
                                        <span class="text-gray-400 text-xs">({{ \Illuminate\Support\Number::fileSize($fileInfo['size'] ?? 0) }})</span>
                                        <label class="ml-2 text-xs">
                                            <input type="checkbox" name="{{ $fieldNamePrefix }}[_delete_files][]" value="{{ $fileInfo['path'] }}"> 削除
                                        </label>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
                @break

            @default
                <p class="text-red-500 mt-1">未対応のフィールドタイプです: {{ $fieldType }}</p>
        @endswitch
        @if ($fieldType === 'file_multiple' || $fieldType === 'image_select' || ($fieldType === 'checkbox' && !empty($fieldOptions)))
             <x-input-error :messages="$errors->get($errorDotKey . '.*')" class="mt-2" />
        @endif

        <x-input-error :messages="$errors->get($errorDotKey)" class="mt-2" />
        @if ($fieldType === 'file_multiple' || ($fieldType === 'checkbox' && !empty($fieldOptions)))
             <x-input-error :messages="$errors->get($errorDotKey . '.*')" class="mt-2" />
        @endif
    </div>
</div>

@if($fieldType === 'image_select')
    @push('scripts')
        @once
            <script>
                function imageSelector(config) {
                    return {
                        max: config.max ? parseInt(config.max) : null,
                        error: '',
                        check() {
                            if (this.max === null) return;
                            const checkboxes = this.$el.querySelectorAll('input[type=checkbox]');
                            const checkedCount = this.$el.querySelectorAll('input[type=checkbox]:checked').length;
                            this.error = '';
                            if (checkedCount >= this.max) {
                                this.error = `選択できるのは${this.max}個までです。`;
                                checkboxes.forEach(checkbox => {
                                    if (!checkbox.checked) checkbox.disabled = true;
                                });
                            } else {
                                checkboxes.forEach(checkbox => checkbox.disabled = false);
                            }
                        }
                    };
                }
            </script>
        @endonce
    @endpush
@endif

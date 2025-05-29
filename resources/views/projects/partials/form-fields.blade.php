@props([
    'field',
    'project' => null,
    'fieldNamePrefix' => 'attributes',
    'prefillValues' => []
])

@php
    $baseFieldName = $field['name'];
    $inputName = $fieldNamePrefix . '[' . $baseFieldName . ']';
    $fileInputName = ($field['type'] === 'file_multiple') ? $inputName . '[]' : $inputName;
    $errorDotKey = $fieldNamePrefix . '.' . $baseFieldName;
    $errorDotKeyArrayItem = $errorDotKey . '.*';

    $fieldLabel = $field['label'];
    $fieldType = $field['type'];
    $fieldRequired = $field['required'] ?? false;
    $fieldPlaceholder = $field['placeholder'] ?? '';
    $fieldOptions = [];
    if (!empty($field['options'])) {
        $optionsArray = explode(',', $field['options']);
        foreach ($optionsArray as $option) {
            $parts = explode(':', trim($option), 2);
            if (count($parts) === 2) {
                $fieldOptions[trim($parts[0])] = trim($parts[1]);
            } else {
                $fieldOptions[trim($parts[0])] = trim($parts[0]);
            }
        }
    }

    $currentValue = null;
    if (old($errorDotKey) !== null) {
        $currentValue = old($errorDotKey);
    } elseif (isset($prefillValues[$baseFieldName]) && $prefillValues[$baseFieldName] !== null) { // プリフィル値を優先
        $currentValue = $prefillValues[$baseFieldName];
    } elseif ($project && isset($project->attributes[$baseFieldName])) {
        $currentValue = $project->attributes[$baseFieldName];
    }

    $maxLength = isset($field['maxlength']) && is_numeric($field['maxlength']) ? (int) $field['maxlength'] : null;
@endphp

<div class="{{ $fieldType === 'checkbox' ? 'flex items-center pt-5' : '' }}">
    @if ($fieldType !== 'checkbox' && $fieldType !== 'radio')
        <x-input-label :for="$field['name']" :value="$fieldLabel" :required="$fieldRequired" />
    @endif

    @switch($fieldType)
        @case('text')
        @case('number')
        @case('color')
        @case('date')
        @case('tel')
        @case('email')
        @case('url')
            <x-text-input :id="$field['name']" :name="$inputName" :type="$fieldType" class="mt-1 block w-full"
                          :value="$currentValue" :required="$fieldRequired" :placeholder="$fieldPlaceholder"
                          :maxlength="$maxLength" :hasError="$errors->has($errorDotKey)"/>
            @break
        @case('textarea')
            <x-textarea-input :id="$field['name']" :name="$inputName" class="mt-1 block w-full"
                              :required="$fieldRequired" :placeholder="$fieldPlaceholder"
                              :maxlength="$maxLength" :hasError="$errors->has($errorDotKey)">{{ $currentValue }}</x-textarea-input>
            @break
        @case('select')
            <x-select-input :id="$field['name']" :name="$inputName" class="mt-1 block w-full"
                            :options="$fieldOptions" :selected="$currentValue"
                            :required="$fieldRequired" :emptyOptionText="$fieldPlaceholder ?: '選択してください'"
                            :hasError="$errors->has($errorDotKey)"/>
            @break
        @case('checkbox')
            <x-checkbox-input :id="$field['name']" :name="$inputName" value="1"
                              :checked="filter_var($currentValue, FILTER_VALIDATE_BOOLEAN)" :label="$fieldLabel"
                              :hasError="$errors->has($errorDotKey)"/>
            @if($errors->has($errorDotKey))
                <p class="text-xs text-red-600 ml-1">{{ $errors->first($errorDotKey) }}</p>
            @endif
            @break
        @case('radio')
            <div class="mt-1">
                 <x-input-label :value="$fieldLabel" :required="$fieldRequired" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"/>
                @if(empty($fieldOptions))
                    <p class="text-xs text-yellow-600 dark:text-yellow-400">選択肢が設定されていません。</p>
                @else
                    <div class="space-y-1 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-x-4 sm:gap-y-1">
                    @foreach($fieldOptions as $optionValue => $optionLabel)
                        <x-radio-input :id="$inputName.'_'.Str::slug($optionValue)" :name="$inputName" :value="$optionValue" :label="$optionLabel" :checked="$currentValue == $optionValue" />
                    @endforeach
                    </div>
                @endif
                <x-input-error :messages="$errors->get($errorDotKey)" class="mt-1" />
            </div>
            @break
        @case('file_multiple')
            <input type="file" id="{{ $field['name'] }}" name="{{ $fileInputName }}" multiple
                   class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-md file:border-0
                          file:text-sm file:font-semibold
                          file:bg-indigo-50 file:text-indigo-700
                          dark:file:bg-indigo-700/20 dark:file:text-indigo-300
                          hover:file:bg-indigo-100 dark:hover:file:bg-indigo-600/30
                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800
                          {{ $errors->has($errorDotKey) || $errors->has($errorDotKeyArrayItem) ? 'border-red-500 dark:border-red-600 ring-1 ring-red-500' : 'border-gray-300 dark:border-gray-600' }} rounded-md shadow-sm">

            @if($project && isset($project->attributes[$baseFieldName]) && is_array($project->attributes[$baseFieldName]) && !empty($project->attributes[$baseFieldName]))
                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    <p class="font-medium mb-1">現在のファイル:</p>
                    <ul class="list-disc list-inside pl-4 space-y-1">
                        @foreach($project->attributes[$baseFieldName] as $fileInfo)
                            @if(is_array($fileInfo) && isset($fileInfo['path']) && isset($fileInfo['original_name']))
                                 <li>
                                    <a href="{{ Storage::url($fileInfo['path']) }}" target="_blank" class="text-blue-600 hover:underline" title="ダウンロード: {{ $fileInfo['original_name'] }}">
                                        <i class="fas fa-file-download mr-1"></i>{{ Str::limit($fileInfo['original_name'], 30) }}
                                    </a>
                                    <span class="text-gray-400 text-xs">({{ \Illuminate\Support\Number::fileSize($fileInfo['size'] ?? 0) }})</span>
                                     {{-- 既存ファイル削除用チェックボックス（編集画面用） --}}
                                     @if(Route::currentRouteName() == 'projects.edit' || Route::currentRouteName() == 'projects.update')
                                        <label class="ml-2 text-xs">
                                            <input type="checkbox" name="{{ $fieldNamePrefix }}[{{ $baseFieldName }}_delete][]" value="{{ $fileInfo['path'] }}"> 削除する
                                        </label>
                                     @endif
                                </li>
                            @elseif(is_string($fileInfo)) {{-- 古い形式（ファイルパス文字列の配列）の場合 --}}
                                <li>
                                    <a href="{{ Storage::url($fileInfo) }}" target="_blank" class="text-blue-600 hover:underline">{{ basename($fileInfo) }}</a>
                                     @if(Route::currentRouteName() == 'projects.edit' || Route::currentRouteName() == 'projects.update')
                                     <label class="ml-2 text-xs">
                                        <input type="checkbox" name="{{ $fieldNamePrefix }}[{{ $baseFieldName }}_delete][]" value="{{ $fileInfo }}"> 削除する
                                     </label>
                                     @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
            <x-input-error :messages="$errors->get($errorDotKey)" class="mt-2" />
            @foreach($errors->get($errorDotKeyArrayItem) as $message)
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @endforeach
            @break
        @default
            <p class="text-red-500 mt-1">未対応のフィールドタイプです: {{ $fieldType }}</p>
    @endswitch

    @if ($fieldType !== 'checkbox' && $fieldType !== 'radio' && $fieldType !== 'file_multiple')
      <x-input-error :messages="$errors->get($errorDotKey)" class="mt-2" />
    @endif
</div>
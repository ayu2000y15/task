@extends('layouts.guest')

@section('title', '製作依頼')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6 text-center">製作依頼フォーム</h1>

    {{-- Global error display for file validation --}}
    <div id="globalFileError" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700/50 dark:text-red-200 dark:border-red-600" role="alert">
        <div class="font-bold">ファイルエラー</div>
        <p id="globalFileErrorMessage"></p>
    </div>

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

    <form action="{{ route('external-form.store') }}" method="POST" enctype="multipart/form-data" id="externalRequestForm">
        @csrf
        <div class="space-y-6">
            <div>
                <x-input-label for="submitter_name" value="お名前" :required="true" />
                <x-text-input id="submitter_name" name="submitter_name" type="text" class="mt-1 block w-full" :value="old('submitter_name')" :hasError="$errors->has('submitter_name')" required />
                <x-input-error :messages="$errors->get('submitter_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="submitter_email" value="メールアドレス" :required="true" />
                <x-text-input id="submitter_email" name="submitter_email" type="email" class="mt-1 block w-full" :value="old('submitter_email')" :hasError="$errors->has('submitter_email')" required />
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
                            $clientSideAccept = '';
                            if ($field['type'] === 'file_multiple') {
                                $clientSideAccept = '.jpg,.jpeg,.png,.gif,.txt,.pdf,image/jpeg,image/png,image/gif,text/plain,application/pdf';
                            }
                        @endphp
                        <div class="mb-4">
                            <x-input-label :for="$field['name']" :value="$field['label']" :required="$field['required']" />
                            @switch($field['type'])
                                @case('text')
                                @case('number')
                                @case('date')
                                @case('color')
                                @case('tel')
                                @case('email')
                                @case('url')
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
                                        if (!empty($field['options_string'])) {
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
                                                          :checked="old($errorKey, false)" :label="$field['label']"
                                                          :hasError="$errors->has($errorKey)" />
                                    </div>
                                    @break
                                @case('radio')
                                    @php
                                        $optionsArray = [];
                                        if (!empty($field['options_string'])) {
                                            $pairs = explode(',', $field['options_string']);
                                            foreach ($pairs as $pair) {
                                                $parts = explode(':', trim($pair), 2);
                                                if (count($parts) === 2) {
                                                    $optionsArray[trim($parts[0])] = trim($parts[1]);
                                                } elseif (!empty(trim($parts[0]))) {
                                                    $optionsArray[trim($parts[0])] = trim($parts[0]);
                                                }
                                            }
                                        }
                                    @endphp
                                    @if(!empty($optionsArray))
                                        <div class="mt-2 space-y-2">
                                            @foreach($optionsArray as $value => $label)
                                                @php
                                                    $optionId = $field['name'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
                                                @endphp
                                                <label for="{{ $optionId }}" class="flex items-center cursor-pointer">
                                                    <input type="radio" id="{{ $optionId }}" name="{{ $inputName }}" value="{{ $value }}"
                                                           class="text-indigo-600 border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-gray-800"
                                                           {{ old($errorKey, $field['default_value'] ?? null) == $value ? 'checked' : '' }}
                                                           @if($field['required']) required @endif>
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-red-500 dark:text-red-400 mt-1">このラジオボタンの選択肢が定義されていません。</p>
                                    @endif
                                    @break
                                @case('file_multiple')
                                    <div class="mt-1" x-data="{
                                        files: [], // Alpine.jsで管理する有効なファイルのリスト (Fileオブジェクトの配列)
                                        fieldError: '', // このフィールド固有のエラーメッセージ
                                        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'text/plain', 'application/pdf'],
                                        allowedExtensions: ['.jpg', '.jpeg', '.png', '.gif', '.txt', '.pdf'],

                                        handleFileSelect(event) {
                                            this.fieldError = '';
                                            document.getElementById('globalFileError').classList.add('hidden');

                                            const newlySelectedFiles = Array.from(event.target.files);

                                            if (newlySelectedFiles.length === 0) {
                                                // ユーザーがファイル選択ダイアログでキャンセルした場合、input要素の値をリセットして、
                                                // 再度同じファイルを選択できるようにする（既存のthis.filesは変更しない）。
                                                // event.target.value = null; // ★この行は削除またはコメントアウトのままにする
                                                return;
                                            }

                                            let combinedFileCandidates = [...this.files];
                                            let invalidFileMessages = [];

                                            newlySelectedFiles.forEach(newFile => {
                                                const fileType = newFile.type;
                                                const fileName = newFile.name.toLowerCase();
                                                const fileExtension = fileName.substring(fileName.lastIndexOf('.'));
                                                let isAllowed = this.allowedTypes.includes(fileType) || this.allowedExtensions.includes(fileExtension);

                                                if (!fileType && fileExtension) {
                                                    isAllowed = this.allowedExtensions.includes(fileExtension);
                                                }

                                                if (isAllowed) {
                                                    if (!combinedFileCandidates.some(existingFile => existingFile.name === newFile.name && existingFile.size === newFile.size)) {
                                                        combinedFileCandidates.push(newFile);
                                                    }
                                                } else {
                                                    invalidFileMessages.push(`ファイル「${newFile.name}」は許可されていない形式です。`);
                                                }
                                            });

                                            if (invalidFileMessages.length > 0) {
                                                this.fieldError = invalidFileMessages.join('\\n') + '\\n許可されている形式: JPG, PNG, GIF, TXT, PDF';
                                            }

                                            this.files = combinedFileCandidates.filter(file => {
                                                const fileType = file.type;
                                                const fileName = file.name.toLowerCase();
                                                const fileExtension = fileName.substring(fileName.lastIndexOf('.'));
                                                let isAllowed = this.allowedTypes.includes(fileType) || this.allowedExtensions.includes(fileExtension);
                                                if (!fileType && fileExtension) { isAllowed = this.allowedExtensions.includes(fileExtension); }
                                                return isAllowed;
                                            });

                                            const dataTransfer = new DataTransfer();
                                            this.files.forEach(file => dataTransfer.items.add(file));
                                            this.$refs.fileInput.files = dataTransfer.files;

                                            // ★以下の行を削除またはコメントアウト
                                            // event.target.value = null;
                                        },
                                        formatFileSize(bytes) {
                                            if (bytes === 0) return '0 Bytes';
                                            const k = 1024;
                                            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                                            if (bytes < k) return bytes + ' ' + sizes[0];
                                            const i = Math.floor(Math.log(bytes) / Math.log(k));
                                            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                                        },
                                        removeFile(index) {
                                            this.files.splice(index, 1);

                                            const dataTransfer = new DataTransfer();
                                            this.files.forEach(file => dataTransfer.items.add(file));
                                            this.$refs.fileInput.files = dataTransfer.files;

                                            const remainingFiles = Array.from(this.$refs.fileInput.files);
                                            let stillHasInvalid = false;
                                            if (remainingFiles.length > 0) {
                                                stillHasInvalid = remainingFiles.some(f => {
                                                    const ft = f.type;
                                                    const fn = f.name.toLowerCase();
                                                    const fe = fn.substring(fn.lastIndexOf('.'));
                                                    let isAllowed = this.allowedTypes.includes(ft) || this.allowedExtensions.includes(fe);
                                                    if (!ft && fe) isAllowed = this.allowedExtensions.includes(fe);
                                                    return !isAllowed;
                                                });
                                            }
                                            if (!stillHasInvalid) {
                                               this.fieldError = '';
                                            }
                                            if (this.files.length === 0 && !stillHasInvalid) {
                                                document.getElementById('globalFileError').classList.add('hidden');
                                            }
                                        }
                                    }">
                                        <label for="{{ $field['name'] }}_file_input"
                                               class="cursor-pointer inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                            <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M3 17.25V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5A2.25 2.25 0 0118.75 19.5H5.25A2.25 2.25 0 013 17.25z" />
                                            </svg>
                                            ファイルを追加
                                        </label>
                                        <input type="file" id="{{ $field['name'] }}_file_input" name="{{ $fileInputName }}" multiple
                                               x-ref="fileInput"
                                               @change="handleFileSelect($event)"
                                               class="hidden file-input-for-validation"
                                               accept="{{ $clientSideAccept }}">

                                        <p x-show="fieldError" x-text="fieldError" class="text-xs text-red-600 dark:text-red-400 mt-1 whitespace-pre-line"></p>

                                        <div x-show="files.length > 0" class="mt-3 space-y-2">
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">選択されたファイル (<span x-text="files.length"></span>件):</p>
                                            <ul class="border border-gray-200 dark:border-gray-600 rounded-md divide-y divide-gray-200 dark:divide-gray-600">
                                                <template x-for="(file, index) in files" :key="file.name + '-' + file.size + '-' + index + '-' + file.lastModified">
                                                    <li class="px-3 py-2 flex items-center justify-between text-sm">
                                                        <span class="text-gray-700 dark:text-gray-300 truncate">
                                                            <svg class="inline-block w-4 h-4 mr-1 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                            <span x-text="file.name"></span>
                                                            <span class="text-gray-500 dark:text-gray-400 ml-1" x-text="'(' + formatFileSize(file.size) + ')'"></span>
                                                        </span>
                                                        <button @click.prevent="removeFile(index)" type="button" class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="削除">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                        </button>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
                                    @if ($errors->has($errorKey . '.*'))
                                        <div class="mt-2 space-y-1">
                                            @foreach ($errors->get($errorKey . '.*') as $message)
                                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @endforeach
                                        </div>
                                    @endif
                                    @break
                                @default
                                    <p class="text-red-500 mt-1">未対応のフィールドタイプです: {{ $field['type'] }}</p>
                            @endswitch

                            @if(!in_array($field['type'], ['checkbox', 'file_multiple']))
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('externalRequestForm');
    const globalFileErrorDiv = document.getElementById('globalFileError');
    const globalFileErrorMessageP = document.getElementById('globalFileErrorMessage');

    form.addEventListener('submit', function (event) {
        let hasAnyInvalidFilesAcrossAllInputs = false;
        let formWideErrorMessages = [];
        const fileInputElements = form.querySelectorAll('input[type="file"].file-input-for-validation');

        fileInputElements.forEach(inputElement => {
            const fieldLabel = inputElement.closest('.mb-4').querySelector('label').textContent.trim().replace(/\s*\*$/, '');
            const alpineComponentEl = inputElement.closest('[x-data]');
            if (!alpineComponentEl || !alpineComponentEl.__x) {
                console.warn('Alpine component not found for file input:', inputElement);
                return;
            }
            const alpineData = alpineComponentEl.__x.$data;

            if (inputElement.files.length > 0) {
                Array.from(inputElement.files).forEach(file => {
                    const fileType = file.type;
                    const fileName = file.name.toLowerCase();
                    const fileExtension = fileName.substring(fileName.lastIndexOf('.'));
                    let isAllowed = alpineData.allowedTypes.includes(fileType) || alpineData.allowedExtensions.includes(fileExtension);
                    if (!fileType && fileExtension) {
                        isAllowed = alpineData.allowedExtensions.includes(fileExtension);
                    }

                    if (!isAllowed) {
                        hasAnyInvalidFilesAcrossAllInputs = true;
                        let specificErrorMessage = `「${fieldLabel}」のファイル「${file.name}」は許可されていない形式です。`;
                        if (!formWideErrorMessages.includes(specificErrorMessage)) {
                           formWideErrorMessages.push(specificErrorMessage);
                        }
                    }
                });
            }
        });

        if (hasAnyInvalidFilesAcrossAllInputs) {
            event.preventDefault();
            globalFileErrorMessageP.innerHTML = formWideErrorMessages.join('<br>') + '<br>許可されている形式: JPG, PNG, GIF, TXT, PDF';
            globalFileErrorDiv.classList.remove('hidden');
            window.scrollTo(0, 0);
        } else {
            globalFileErrorDiv.classList.add('hidden');
        }
    });
});
</script>
@endpush

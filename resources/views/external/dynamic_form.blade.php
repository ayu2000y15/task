<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formCategory->form_title ?: $formCategory->display_name }}</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
        }
        .file-drop-zone {
            border: 2px dashed #cbd5e0;
            border-radius: 0.75rem;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            position: relative;
        }
        .file-drop-zone:hover, .file-drop-zone.dragover {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .file-input-hidden {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .file-preview-item {
            transition: all 0.2s ease;
        }
        .file-preview-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .delete-file-btn {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .file-preview-item:hover .delete-file-btn {
            opacity: 1;
        }
        .upload-icon-animation {
            animation: uploadPulse 2s ease-in-out infinite;
        }
        @keyframes uploadPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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
<form method="POST" action="{{ route('external-form.confirm', $formCategory->slug) }}" enctype="multipart/form-data" class="p-8 space-y-6">                @csrf

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
                    <div class="hidden form-group mt-6">
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
                                            @if(is_array($field['options']))
                                                @foreach($field['options'] as $value => $label)
                                                    <option value="{{ $value }}" @if(old('custom_' . $field['name']) == $value) selected @endif>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @break

                                    @case('radio')
                                        <div class="space-y-3">
                                            @if(is_array($field['options']))
                                                @foreach($field['options'] as $value => $label)
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
                                            @if(is_array($field['options']))
                                                @foreach($field['options'] as $value => $label)
                                                    @php
                                                        $oldValues = old('custom_' . $field['name'], []);
                                                    @endphp
                                                    <div class="flex items-center">
                                                        <input type="checkbox"
                                                                id="custom_{{ $field['name'] }}_{{ $loop->index }}"
                                                                name="custom_{{ $field['name'] }}[]"
                                                                value="{{ $value }}"
                                                                class="flex-shrink-0 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                                @if(is_array($oldValues) && in_array($value, $oldValues)) checked @endif>
                                                        <label for="custom_{{ $field['name'] }}_{{ $loop->index }}" class="ml-3 text-sm text-gray-700">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        @break

                                    @case('image_select')
    @php
        $max = $field['max_selections'] ?? null;
        $options = is_array($field['options']) ? $field['options'] : [];
        $oldValues = old('custom_' . $field['name'], []);
    @endphp

    @if($max)
        <p class="text-sm text-gray-600 mb-3">
            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
            最大{{ $max }}個まで選択できます。
        </p>
    @endif

    {{-- ↓ 変更箇所 --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-4 image-selector-container"
         data-max-selections="{{ $max }}">
    {{-- ↑ 変更箇所 --}}
        @if(!empty($options))
            @foreach($options as $value => $imageUrl)
                {{-- ↓ 変更箇所 (onclickイベント内でスタイルを制御) --}}
                <label for="custom_{{ $field['name'] }}_{{ $loop->index }}"
                    class="block border rounded-lg overflow-hidden transition-all duration-200 bg-white cursor-pointer">

                    <div class="bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-2">
                        <img src="{{ $imageUrl }}" alt="{{ $value }}" class="max-w-full h-40 object-contain">
                    </div>

                    <div class="p-3 border-t dark:border-gray-700">
                        <div class="flex items-center">
                            <input type="checkbox"
                                id="custom_{{ $field['name'] }}_{{ $loop->index }}"
                                name="custom_{{ $field['name'] }}[]"
                                value="{{ $value }}"
                                {{-- ↓ 変更箇所 --}}
                                onclick="handleImageSelection(this)"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                @if(is_array($oldValues) && in_array($value, $oldValues)) checked @endif>
                            {{-- ↑ 変更箇所 --}}
                            <span class="ml-2 block text-sm font-medium text-gray-800 truncate" title="{{ $value }}">
                                {{ $value }}
                            </span>
                        </div>
                    </div>
                </label>
            @endforeach
        @endif
    </div>
    {{-- エラーメッセージ表示用の要素は任意で設置してください --}}
    <div id="error-custom_{{ $field['name'] }}" class="mt-2 text-sm text-red-600"></div>
    @break

                                    @case('file')
                                    @case('file_multiple')
                                        <div class="file-drop-zone"
                                             onclick="document.getElementById('custom_{{ $field['name'] }}').click()"
                                             ondrop="handleDrop(event, 'custom_{{ $field['name'] }}')"
                                             ondragover="handleDragOver(event)"
                                             ondragleave="handleDragLeave(event)">
                                            <div class="text-gray-600">
                                                <div class="upload-icon-animation mb-4">
                                                    <i class="fas fa-cloud-upload-alt text-5xl text-blue-500"></i>
                                                </div>
                                                <p class="text-xl font-semibold text-gray-800 mb-2">ファイルをアップロード</p>
                                                <p class="text-lg font-medium text-blue-600 mb-1">ドラッグ＆ドロップまたはクリックして選択</p>
                                                <div class="flex items-center justify-center space-x-2 text-sm text-gray-500 mt-3">
                                                    <i class="fas fa-info-circle"></i>
                                                    @if($field['type'] === 'file_multiple')
                                                        <span>複数ファイル選択可能・各ファイル最大10MB（JPG, PNG, GIF, PDF, DOC, XLS, ZIP, TXT）</span>
                                                    @else
                                                        <span>最大10MB（JPG, PNG, GIF, PDF, DOC, XLS, ZIP, TXT）</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <input type="file"
                                               id="custom_{{ $field['name'] }}"
                                               name="custom_{{ $field['name'] }}{{ $field['type'] === 'file_multiple' ? '[]' : '' }}"
                                               {{ $field['type'] === 'file_multiple' ? 'multiple' : '' }}
                                               accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt"
                                               class="file-input-hidden"
                                               onchange="handleFileSelection(this, {{ $field['type'] === 'file_multiple' ? 'true' : 'false' }})">
                                        @php
                                            // ★ 修正: セッションから既存ファイル情報の配列を一度に取得します
                                            $existingFilesArray = old('existing_temp_files', session('existing_temp_files', []));
                                            // ★ 修正: 現在のフィールドに対応するファイル情報を取り出します
                                            $currentFieldFiles = $existingFilesArray[$field['name']] ?? [];
                                        @endphp
                                        {{-- 既存ファイル情報を保持するための隠しフィールド --}}
                                        <input type="hidden"
                                            name="existing_temp_files[{{ $field['name'] }}]"
                                            id="existing_files_hidden_{{ $field['name'] }}"
                                            value="{{ json_encode($currentFieldFiles) }}">
                                        <div class="mt-4" id="preview_custom_{{ $field['name'] }}"></div>
                                        @break
                                @endswitch

                                @error('custom_' . $field['name'])
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('custom_' . $field['name'] . '.*')
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
                        送信内容を確認する
                    </button>
                </div>
            </form>
        </div>

        {{-- フッター --}}
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>{{ config('app.name') }}</p>
        </div>
    </div>
<script>
    // 選択順序をグローバルに管理するオブジェクト（idを格納）
    const selectionOrder = {};

    // -------------------------------------------------------------------------
    // 画像選択フィールド（image_select）の制御ロジック
    // -------------------------------------------------------------------------

    /**
     * 画像選択のクリックを処理し、選択数を制御する関数
     * @param {HTMLInputElement} checkbox - クリックされたチェックボックス要素
     */
    function handleImageSelection(checkbox) {
        const container = checkbox.closest('.image-selector-container');
        if (!container) return;

        const maxSelections = parseInt(container.dataset.maxSelections, 10);
        // 上限設定がない場合は、スタイル更新のみで処理を終了
        if (!maxSelections) {
            updateImageSelectionStates(container);
            return;
        }

        const fieldName = checkbox.name.replace(/\[\]$/, '');
        if (!selectionOrder[fieldName]) {
            selectionOrder[fieldName] = [];
        }
        let order = selectionOrder[fieldName];
        const currentId = checkbox.id;

        // チェックボックスがチェックされた（選択が追加された）場合
        if (checkbox.checked) {
            // 選択された項目のidを順序リストの末尾に追加
            order.push(currentId);

            // リストの長さが上限を超えた場合
            if (order.length > maxSelections) {
                // 最も古い選択（リストの先頭）のidを取得してリストから削除
                const idToUncheck = order.shift();

                // idを使って対応するチェックボックスを確実に見つけ、チェックを外す
                const checkboxToUncheck = document.getElementById(idToUncheck);
                if (checkboxToUncheck) {
                    checkboxToUncheck.checked = false;
                }
            }
        } else {
            // チェックが外された場合、順序リストからそのidを削除
            selectionOrder[fieldName] = order.filter(id => id !== currentId);
        }

        // 全てのチェックボックスの見た目を最新の状態に更新
        updateImageSelectionStates(container);
    }

    /**
     * 【重要修正点】コンテナ内のすべてのチェックボックスのスタイルを更新する
     * disabled（無効化）処理を完全に削除し、スタイル更新のみに専念させます。
     * @param {HTMLElement} container - .image-selector-container要素
     */
    function updateImageSelectionStates(container) {
        const allCheckboxes = container.querySelectorAll('input[type=checkbox]');

        allCheckboxes.forEach(cb => {
            const label = cb.closest('label');

            // ★★★ 修正点： disabled（無効化）に関するロジックを完全に削除 ★★★

            // 選択中のスタイルを適用
            if (cb.checked) {
                label.classList.add('ring-2', 'ring-blue-500', 'border-blue-500', 'shadow-md');
            } else {
                label.classList.remove('ring-2', 'ring-blue-500', 'border-blue-500', 'shadow-md');
            }
        });
    }

    // -------------------------------------------------------------------------
    // ファイルアップロードフィールド（file, file_multiple）の制御ロジック
    // -------------------------------------------------------------------------

    // グローバル変数として、選択されたファイルをフィールドごとに管理します
    const selectedFiles = {};

    /**
     * ドラッグ＆ドロップのイベントハンドラ
     */
    function handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('dragover');
    }

    function handleDragLeave(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');
    }

    function handleDrop(event, inputId) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');
        const files = event.dataTransfer.files;
        const input = document.getElementById(inputId);
        input.files = files;
        handleFileSelection(input, input.multiple);
    }

    /**
     * ファイルが選択された（またはドロップされた）時のメイン処理
     */
    function handleFileSelection(input, multiple) {
        const inputId = input.id;
        if (!selectedFiles[inputId]) {
            selectedFiles[inputId] = [];
        }

        const newFiles = Array.from(input.files);
        const existingFileNames = selectedFiles[inputId].map(f => f.name || f.original_name);

        newFiles.forEach(file => {
            if (!existingFileNames.includes(file.name)) {
                selectedFiles[inputId].push(file);
            }
        });

        syncInputFiles(inputId);
        displayFilePreview(inputId);
    }

    /**
     * ファイルを削除する処理
     */
    function removeFile(inputId, fileIndex) {
        if (!selectedFiles[inputId] || !selectedFiles[inputId][fileIndex]) return;

        selectedFiles[inputId].splice(fileIndex, 1);

        updateHiddenInput(inputId);
        syncInputFiles(inputId);
        displayFilePreview(inputId);
    }

    /**
     * selectedFiles（マスターリスト）の状態を実際の<input type="file">に反映させる関数
     */
    function syncInputFiles(inputId) {
        const input = document.getElementById(inputId);
        const dt = new DataTransfer();
        const clientSideFiles = selectedFiles[inputId].filter(file => file instanceof File);

        clientSideFiles.forEach(file => {
            dt.items.add(file);
        });
        input.files = dt.files;
    }

    /**
     * サーバー由来のファイル情報を保持する隠しフィールドを更新する関数
     */
    function updateHiddenInput(inputId) {
        const fieldName = inputId.replace('custom_', '');
        const hiddenInput = document.getElementById(`existing_files_hidden_${fieldName}`);
        if (hiddenInput) {
            const serverFiles = selectedFiles[inputId].filter(file => !(file instanceof File));
            hiddenInput.value = JSON.stringify(serverFiles);
        }
    }

    /**
     * ファイルプレビューを描画する関数
     */
    function displayFilePreview(inputId) {
        const previewContainer = document.getElementById('preview_' + inputId);
        previewContainer.innerHTML = ''; // プレビューを一旦クリア

        const files = selectedFiles[inputId];
        if (!files || files.length === 0) return;

        files.forEach((file, index) => {
            const fileName = file.name || file.original_name;
            const fileSize = file.size;
            const sizeKB = fileSize ? (fileSize / 1024).toFixed(2) : 'N/A';
            const fileType = file.type || file.mime_type || 'application/octet-stream';

            const previewItemWrapper = document.createElement('div');
            previewItemWrapper.className = 'file-preview-item';

            let innerHTML = '';
            const src = (file instanceof File) ? URL.createObjectURL(file) : (file.preview_src || '');

            innerHTML = `
                <div class='flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 mb-2'>
            `;

            if (fileType.startsWith('image/') && src) {
                innerHTML += `
                    <img src="${src}" alt="プレビュー" class="w-16 h-16 object-cover rounded-md mr-4" ${(file instanceof File) ? 'onload="URL.revokeObjectURL(this.src)"' : ''}>
                `;
            } else {
                let icon = 'fa-file-alt';
                if (fileType.includes('pdf')) { icon = 'fa-file-pdf'; }
                else if (fileType.includes('word')) { icon = 'fa-file-word'; }
                else if (fileType.includes('excel')) { icon = 'fa-file-excel'; }
                else if (fileType.includes('zip')) { icon = 'fa-file-archive'; }
                innerHTML += `
                    <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-md flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas ${icon} text-3xl text-gray-500 dark:text-gray-400"></i>
                    </div>
                `;
            }

            innerHTML += `
                <div class="flex-grow min-w-0">
                    <p class="font-medium truncate">${fileName}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">${sizeKB} KB</p>
                </div>
            `;

            innerHTML += `
                <button type='button' onclick='removeFile("${inputId}", ${index})'
                        class='delete-file-btn bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-all shadow-md ml-3 flex-shrink-0 text-base'>
                    ×
                </button>
            `;

            innerHTML += `</div>`;

            previewItemWrapper.innerHTML = innerHTML;
            previewContainer.appendChild(previewItemWrapper);
        });
    }

    /**
     * ページ読み込み完了時に、各種フィールドの初期状態をセットアップする
     */
    document.addEventListener('DOMContentLoaded', () => {
        // [画像選択] フィールドの初期状態を反映
        const imageContainers = document.querySelectorAll('.image-selector-container');
        imageContainers.forEach(container => {
            const firstInput = container.querySelector('input[type=checkbox]');
            if (!firstInput) return;

            const fieldName = firstInput.name.replace(/\[\]$/, '');
            // 選択順序リストを初期化
            selectionOrder[fieldName] = [];

            // ページ読み込み時にチェック済みのもののidを順序リストに追加
            const checkedInputs = container.querySelectorAll('input[type=checkbox]:checked');
            checkedInputs.forEach(cb => {
                selectionOrder[fieldName].push(cb.id);
            });

            // 全体のスタイルを更新
            updateImageSelectionStates(container);
        });

        // [ファイル] フィールドで、サーバーから渡された既存ファイルを復元
        const hiddenFileInputs = document.querySelectorAll('input[type="hidden"][name^="existing_temp_files"]');
        hiddenFileInputs.forEach(input => {
            try {
                const fieldName = input.name.match(/\[(.*?)\]/)[1];
                const inputId = `custom_${fieldName}`;
                const existingFilesData = JSON.parse(input.value);

                if (Array.isArray(existingFilesData) && existingFilesData.length > 0) {
                    selectedFiles[inputId] = [].concat(existingFilesData);
                    displayFilePreview(inputId);
                }
            } catch (e) {
                console.error("Failed to parse existing files JSON:", e);
            }
        });
    });
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formCategory->form_title ?: $formCategory->display_name }}</title>
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
                                                    <span>最大10MB（JPG, PNG, GIF, PDF, DOC, XLS, ZIP, TXT）</span>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="file"
                                               id="custom_{{ $field['name'] }}"
                                               name="custom_{{ $field['name'] }}"
                                               accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt"
                                               class="file-input-hidden"
                                               @if($field['is_required']) required @endif
                                               onchange="handleFileSelection(this, false)">
                                        <div class="mt-4" id="preview_custom_{{ $field['name'] }}"></div>
                                        @break

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
                                                <p class="text-xl font-semibold text-gray-800 mb-2">ファイルアップロード</p>
                                                <p class="text-lg font-medium text-blue-600 mb-1">ドラッグ＆ドロップまたはクリックして選択</p>
                                                <div class="flex items-center justify-center space-x-2 text-sm text-gray-500 mt-3">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span>複数ファイル選択可能・各ファイル最大10MB（JPG, PNG, GIF, PDF, DOC, XLS, ZIP, TXT）</span>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="file"
                                               id="custom_{{ $field['name'] }}"
                                               name="custom_{{ $field['name'] }}[]"
                                               multiple
                                               accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt"
                                               class="file-input-hidden"
                                               @if($field['is_required']) required @endif
                                               onchange="handleFileSelection(this, true)">
                                        <div class="mt-4" id="preview_custom_{{ $field['name'] }}"></div>
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
        // 選択されたファイルを格納するオブジェクト
        const selectedFiles = {};

        // ドラッグアンドドロップ処理
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

            // 既存のファイルと新しいファイルを結合
            if (!selectedFiles[inputId]) selectedFiles[inputId] = [];

            for (let i = 0; i < files.length; i++) {
                selectedFiles[inputId].push(files[i]);
            }

            updateInputFiles(inputId);
            displayFilePreview(inputId);
        }

        // ファイル選択処理（input changeイベント用）
        function handleFileSelection(input, multiple) {
            const inputId = input.id;

            if (!selectedFiles[inputId]) selectedFiles[inputId] = [];

            // 新しく選択されたファイルを追加
            for (let i = 0; i < input.files.length; i++) {
                selectedFiles[inputId].push(input.files[i]);
            }

            displayFilePreview(inputId);
        }

        // inputのfilesプロパティを更新
        function updateInputFiles(inputId) {
            const input = document.getElementById(inputId);
            const dt = new DataTransfer();

            if (selectedFiles[inputId]) {
                selectedFiles[inputId].forEach(file => {
                    dt.items.add(file);
                });
            }

            input.files = dt.files;
        }

        // ファイルを削除
        function removeFile(inputId, fileIndex) {
            if (selectedFiles[inputId]) {
                selectedFiles[inputId].splice(fileIndex, 1);
            }
            updateInputFiles(inputId);
            displayFilePreview(inputId);
        }

        // ファイルプレビューを表示
        function displayFilePreview(inputId) {
            const previewId = 'preview_' + inputId;
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';

            const files = selectedFiles[inputId];
            if (!files || files.length === 0) return;

            let hasError = false;

            files.forEach((file, index) => {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);

            if (file.size > 10 * 1024 * 1024) {
                    preview.innerHTML += `
                        <div class='p-4 mb-3 bg-red-50 border border-red-200 rounded-xl file-preview-item'>
                            <div class='flex items-center justify-between'>
                                <div class='flex items-center'>
                                    <i class='fas fa-exclamation-triangle text-red-500 text-xl mr-3'></i>
                                    <div>
                                        <p class='text-red-600 text-sm font-medium'>ファイル「${file.name}」は5MBを超えています</p>
                                        <p class='text-red-500 text-xs'>${sizeMB}MB</p>
                                    </div>
                                </div>
                                <button type='button' onclick='removeFile("${inputId}", ${index})'
                                        class='text-red-500 hover:text-red-700 p-1 rounded transition-colors text-xl font-bold'>
                                    ×
                                </button>
                            </div>
                        </div>
                    `;
                    hasError = true;
                    return;
                }

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imagePreview = document.createElement('div');
                        imagePreview.className = 'file-preview-item';
                        imagePreview.innerHTML = `
                            <div class='flex items-start p-4 mb-3 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all'>
                                <img src='${e.target.result}' alt='preview' class='w-20 h-20 object-cover rounded-lg shadow mr-4'>
                                <div class='flex-1 min-w-0'>
                                    <div class='text-sm font-semibold text-gray-900 truncate'>${file.name}</div>
                                    <div class='text-xs text-gray-500 mt-1'>${sizeMB}MB・${file.type}</div>
                                    <div class='flex items-center mt-2'>
                                        <i class='fas fa-check-circle text-green-500 mr-1'></i>
                                        <span class='text-xs text-green-600 font-medium'>アップロード準備完了</span>
                                    </div>
                                </div>
                                <button type='button' onclick='removeFile("${inputId}", ${index})'
                                        class='delete-file-btn bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition-all shadow-lg ml-3 flex-shrink-0 text-xl font-bold'>
                                    ×
                                </button>
                            </div>
                        `;
                        preview.appendChild(imagePreview);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // ファイルタイプに応じたアイコンと色を設定
                    let icon = 'fa-file-alt';
                    let iconColor = 'text-gray-500';

                    if (file.type.includes('pdf')) {
                        icon = 'fa-file-pdf';
                        iconColor = 'text-red-500';
                    } else if (file.type.includes('word') || file.name.toLowerCase().includes('.doc')) {
                        icon = 'fa-file-word';
                        iconColor = 'text-blue-500';
                    } else if (file.type.includes('excel') || file.name.toLowerCase().includes('.xls')) {
                        icon = 'fa-file-excel';
                        iconColor = 'text-green-500';
                    } else if (file.type.includes('zip') || file.name.toLowerCase().includes('.zip')) {
                        icon = 'fa-file-archive';
                        iconColor = 'text-yellow-600';
                    } else if (file.type.includes('text')) {
                        icon = 'fa-file-alt';
                        iconColor = 'text-gray-600';
                    }

                    preview.innerHTML += `
                        <div class='flex items-center p-4 mb-3 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md file-preview-item transition-all'>
                            <div class='w-16 h-16 bg-gray-50 rounded-lg flex items-center justify-center mr-4'>
                                <i class='fas ${icon} text-3xl ${iconColor}'></i>
                            </div>
                            <div class='flex-1 min-w-0'>
                                <div class='text-sm font-semibold text-gray-900 truncate'>${file.name}</div>
                                <div class='text-xs text-gray-500 mt-1'>${sizeMB}MB・${file.type}</div>
                                <div class='flex items-center mt-2'>
                                    <i class='fas fa-check-circle text-green-500 mr-1'></i>
                                    <span class='text-xs text-green-600 font-medium'>アップロード準備完了</span>
                                </div>
                            </div>
                            <button type='button' onclick='removeFile("${inputId}", ${index})'
                                    class='delete-file-btn bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition-all shadow-lg ml-3 flex-shrink-0 text-xl font-bold'>
                                ×
                            </button>
                        </div>
                    `;
                }
            });

            if (hasError) {
                // エラーファイルを配列から除去
                selectedFiles[inputId] = selectedFiles[inputId].filter(file => file.size <= 10 * 1024 * 1024);
                updateInputFiles(inputId);
            }
        }
    </script>
</body>
</html>

@extends('layouts.app')

@section('title', '投稿編集')

@push('styles')
    {{-- Tom SelectのCSS --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')

    {{-- Tom SelectのJS --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    {{-- TinyMCEのCDNと初期化スクリプト --}}
    <script src="https://cdn.tiny.cloud/1/m3870xzvadd7jh67mc2gi5s50oen09a7yebhko8uvquwfy0x/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            tinymce.init({
                selector: 'textarea#body_editor_edit',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount preview fullscreen insertdatetime help',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline | link image media | align lineheight | numlist bullist | emoticons charmap | removeformat | preview | help',
                height: 400,
                language: 'ja',
                content_css: '{{ asset('css/tinymce-content.css') }}', // カスタムCSSを指定
                entity_encoding: 'raw', // HTMLエスケープを防ぐ
                relative_urls: false,
                remove_script_host: false,
                convert_urls: false,
                document_base_url: "{{ url('/') }}/",
                images_upload_url: "{{ route('community.posts.uploadImage') }}",
                automatic_uploads: true,
                file_picker_types: 'image',
                paste_data_images: true,
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', "{{ route('community.posts.uploadImage') }}");
                    const token = document.head.querySelector('meta[name="csrf-token"]');
                    if (token) { xhr.setRequestHeader("X-CSRF-TOKEN", token.content); }
                    else { reject({ message: 'CSRF token not found.', remove: true }); return; }
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = (e) => progress(e.loaded / e.total * 100);
                    xhr.onload = () => {
                        if (xhr.status === 403) { reject({ message: 'HTTP Error: ' + xhr.status + ' - Forbidden.', remove: true }); return; }
                        if (xhr.status < 200 || xhr.status >= 300) { reject('HTTP Error: ' + xhr.status + '. Response: ' + xhr.responseText); return; }
                        let json;
                        try { json = JSON.parse(xhr.responseText); }
                        catch (e) { reject('Invalid JSON: ' + xhr.responseText); return; }
                        if (!json || typeof json.location != 'string') { reject('Invalid JSON response: ' + xhr.responseText); return; }
                        resolve(json.location);
                    };
                    xhr.onerror = () => reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(formData);
                }),
                setup: function (editor) {
                    const suggestionContainer = document.getElementById('mention-suggestions-container');
                    let selectedSuggestionIndex = -1;

                    const hideSuggestions = () => {
                        if (suggestionContainer) suggestionContainer.style.display = 'none';
                        selectedSuggestionIndex = -1;
                    };

                    const showSuggestions = (items) => {
                        if (!suggestionContainer || items.length === 0) {
                            hideSuggestions();
                            return;
                        }
                        suggestionContainer.innerHTML = '';
                        items.forEach((item, index) => {
                            const div = document.createElement('div');
                            div.className = 'mention-suggestion-item';
                            div.innerHTML = `<i class="fas fa-user fa-fw mr-2"></i>${item.text}`;
                            div.dataset.mentionId = item.id;
                            div.addEventListener('click', () => {
                                insertMention(item);
                                hideSuggestions();
                            });
                            suggestionContainer.appendChild(div);
                        });
                        const iframe = editor.getContainer().querySelector('iframe');
                        const iframeRect = iframe.getBoundingClientRect();
                        const cursorRect = editor.selection.getRng().getClientRects()[0];
                        if (cursorRect) {
                            suggestionContainer.style.top = (iframeRect.top + cursorRect.bottom + window.scrollY) + 'px';
                            suggestionContainer.style.left = (iframeRect.left + cursorRect.left + window.scrollX) + 'px';
                            suggestionContainer.style.display = 'block';
                        }
                        selectedSuggestionIndex = -1;
                    };

                    const fetchUsers = (term) => {
                        fetch(`{{ route('community.users.search') }}?query=${encodeURIComponent(term)}`)
                            .then(response => response.json())
                            .then(users => showSuggestions(users))
                            .catch(() => hideSuggestions());
                    };

                    const insertMention = (item) => {
                        const mentionText = `@${item.id}&nbsp;`;
                        const range = editor.selection.getRng();
                        const textBeforeCursor = range.startContainer.textContent.substring(0, range.startOffset);
                        const atIndex = textBeforeCursor.lastIndexOf('@');
                        if (atIndex !== -1) {
                            range.setStart(range.startContainer, atIndex);
                            editor.selection.setRng(range);
                            editor.execCommand('mceInsertContent', false, mentionText);
                        }
                    };

                    editor.on('keyup', (e) => {
                        if (e.key === 'Escape') { hideSuggestions(); return; }
                        const range = editor.selection.getRng();
                        if (!range.startContainer.textContent) return;
                        const textBeforeCursor = range.startContainer.textContent.substring(0, range.startOffset);
                        const mentionMatch = textBeforeCursor.match(/@(\w*)$/);
                        if (mentionMatch) {
                            fetchUsers(mentionMatch[1]);
                        } else {
                            hideSuggestions();
                        }
                    });

                    editor.on('keydown', (e) => {
                        if (!suggestionContainer || suggestionContainer.style.display === 'none') return;
                        const items = suggestionContainer.querySelectorAll('.mention-suggestion-item');
                        if (items.length === 0) return;
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            selectedSuggestionIndex = (selectedSuggestionIndex + 1) % items.length;
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            selectedSuggestionIndex = (selectedSuggestionIndex - 1 + items.length) % items.length;
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (selectedSuggestionIndex > -1) {
                                items[selectedSuggestionIndex].click();
                            }
                        }
                        items.forEach((item, index) => {
                            item.classList.toggle('is-selected', index === selectedSuggestionIndex);
                        });
                    });

                    editor.on('focusout', () => setTimeout(hideSuggestions, 200));
                }
            });

            // Tom Selectの初期化
            let userTomSelectEdit = null;
            if (document.getElementById('readable_users_select_edit')) {
                userTomSelectEdit = new TomSelect('#readable_users_select_edit', {
                    plugins: ['remove_button'],
                    create: false,
                    placeholder: 'ユーザーを検索・選択...'
                });
            }

            // ロール選択時の制御
            const roleSelect = document.getElementById('role_id');
            const userSelectionSection = document.getElementById('user-selection-section');

            function toggleUserSelection() {
                const selectedRole = roleSelect.value;
                const isEveryone = selectedRole === 'everyone';

                if (isEveryone) {
                    // 全ユーザー選択時はユーザー個別指定を無効化
                    userSelectionSection.style.display = 'none';
                    if (userTomSelectEdit) {
                        userTomSelectEdit.clear(); // 選択をクリア
                    }
                } else {
                    // その他のロール選択時はユーザー個別指定を有効化
                    userSelectionSection.style.display = 'block';
                }
            }

            // 初期状態の設定
            toggleUserSelection();

            // ロール変更時のイベントリスナー
            if (roleSelect) {
                roleSelect.addEventListener('change', toggleUserSelection);
            }

            // 投稿タイプ変更時のカスタム項目取得（編集用）
            // 編集時は投稿タイプの変更を無効にしているため、初期表示のみ
            const postTypeInput = document.getElementById('board_post_type_id');
            const customFieldsSection = document.getElementById('custom-fields-section');
            const customFieldsContainer = document.getElementById('custom-fields-container');
            const bodySection = document.getElementById('body-section');
            const bodyEditor = document.getElementById('body_editor_edit');

            // 既存のカスタム項目値を保持
            const existingValues = @json($post->customFieldValues->pluck('value', 'form_field_definition_id')->toArray());

            function loadCustomFields(postTypeId) {
                // 現在の投稿タイプ情報を取得
                const currentPostType = @json($post->boardPostType);
                const isAnnouncement = currentPostType && currentPostType.name === 'announcement';

                // お知らせの場合は本文を表示、それ以外はカスタム項目を表示
                if (isAnnouncement) {
                    bodySection.style.display = 'block';
                    customFieldsSection.style.display = 'none';
                    // 本文を必須にする
                    bodyEditor.setAttribute('required', 'required');
                    const bodyLabel = bodySection.querySelector('label');
                    if (bodyLabel) {
                        bodyLabel.innerHTML = '本文 <span class="text-red-500">*</span>';
                    }
                    // カスタム項目のコンテナをクリア
                    customFieldsContainer.innerHTML = '';
                    return; // お知らせの場合はここで処理終了
                } else {
                    bodySection.style.display = 'none';
                    // 本文の必須を解除
                    bodyEditor.removeAttribute('required');

                    if (!postTypeId) {
                        customFieldsSection.style.display = 'none';
                        customFieldsContainer.innerHTML = '';
                        return;
                    }

                    fetch(`{{ route('community.posts.customFields') }}?post_type_id=${postTypeId}`)
                        .then(response => response.json())
                        .then(data => {
                            customFieldsContainer.innerHTML = '';

                            if (data.fields && data.fields.length > 0) {
                                data.fields.forEach(field => {
                                    const fieldHtml = createCustomFieldHtml(field, existingValues[field.id] || '');
                                    customFieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
                                });
                                customFieldsSection.style.display = 'block';
                            } else {
                                customFieldsSection.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('カスタム項目の取得に失敗しました:', error);
                            customFieldsSection.style.display = 'none';
                            customFieldsContainer.innerHTML = '';
                        });
                }
            }

            function createCustomFieldHtml(field, existingValue = '') {
                const fieldId = `custom_field_${field.id}`;
                const required = field.is_required ? 'required' : '';
                const requiredLabel = field.is_required ? '<span class="text-red-500">*</span>' : '';

                let inputHtml = '';

                switch (field.type) {
                    case 'text':
                    case 'email':
                    case 'url':
                    case 'tel':
                        inputHtml = `<input type="${field.type}" id="${fieldId}" name="${fieldId}"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="${field.placeholder || ''}" value="${existingValue}" ${required}>`;
                        break;
                    case 'textarea':
                        inputHtml = `<textarea id="${fieldId}" name="${fieldId}" rows="3"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="${field.placeholder || ''}" ${required}>${existingValue}</textarea>`;
                        break;
                    case 'date':
                        inputHtml = `<input type="date" id="${fieldId}" name="${fieldId}"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        value="${existingValue}" ${required}>`;
                        break;
                    case 'number':
                        inputHtml = `<input type="number" id="${fieldId}" name="${fieldId}"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="${field.placeholder || ''}" value="${existingValue}" ${required}>`;
                        break;
                    case 'select':
                        let options = '<option value="">選択してください</option>';
                        if (field.options && typeof field.options === 'object') {
                            Object.entries(field.options).forEach(([value, label]) => {
                                const selected = value === existingValue ? 'selected' : '';
                                options += `<option value="${value}" ${selected}>${label}</option>`;
                            });
                        }
                        inputHtml = `<select id="${fieldId}" name="${fieldId}"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" ${required}>
                                        ${options}
                                    </select>`;
                        break;
                    case 'checkbox':
                        const checked = existingValue === '1' ? 'checked' : '';
                        inputHtml = `<div class="flex items-center">
                                        <input type="checkbox" id="${fieldId}" name="${fieldId}" value="1" ${checked}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-indigo-600">
                                        <label for="${fieldId}" class="ml-2 text-sm text-gray-600 dark:text-gray-400">${field.label}</label>
                                    </div>`;
                        break;
                    default:
                        inputHtml = `<input type="text" id="${fieldId}" name="${fieldId}"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="${field.placeholder || ''}" value="${existingValue}" ${required}>`;
                }

                return `
                                <div>
                                    <label for="${fieldId}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        ${field.label} ${requiredLabel}
                                    </label>
                                    ${inputHtml}
                                </div>
                            `;
            }

            // 初期表示時に現在の投稿タイプのカスタム項目を読み込み
            // TinyMCEの初期化後に実行するため、少し遅延させる
            setTimeout(() => {
                if (postTypeInput && postTypeInput.value) {
                    loadCustomFields(postTypeInput.value);
                }
            }, 100);

            // 編集時は投稿タイプの変更を無効にしているため、
            // 投稿タイプ変更時のイベントリスナーは不要
        });
    </script>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- パンくずリスト --}}
        <div class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
            <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
            <a href="{{ route('community.posts.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">社内掲示板</a>
            <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
            <a href="{{ route('community.posts.show', $post) }}"
                class="hover:text-blue-600 dark:hover:text-blue-400 truncate" title="{{ $post->title }}">
                {{ Str::limit($post->title, 30) }}
            </a>
            <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
            <span class="text-gray-700 dark:text-gray-200">編集</span>
        </div>

        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">投稿編集</h1>

        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg ">
            <div class="p-6 sm:p-8">
                <form action="{{ route('community.posts.update', $post) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="board_post_type_display" value="投稿タイプ" />
                            <div
                                class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-sm font-medium
                                        @if($post->boardPostType && $post->boardPostType->name === 'announcement')
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @else
                                            bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300
                                        @endif">
                                    <i class="fas fa-tag mr-1"></i>
                                    {{ $post->boardPostType->display_name ?? '未設定' }}
                                </span>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                    （編集時は変更できません）
                                </span>
                            </div>
                            <!-- 投稿タイプIDを隠しフィールドで保持 -->
                            <input type="hidden" id="board_post_type_id" name="board_post_type_id"
                                value="{{ $post->board_post_type_id }}">
                        </div>

                        <div>
                            <x-input-label for="title" value="タイトル" :required="true" />
                            <x-text-input type="text" id="title" name="title" class="mt-1 block w-full" :value="old('title', $post->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div id="body-section" style="display: block;">
                            <x-input-label for="body_editor_edit" value="本文" :required="true" />
                            <textarea id="body_editor_edit" name="body" class="tinymce-content mt-1 block w-full"
                                required>{{ old('body', $post->body) }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">メンションするには `@` を入力して候補から選択、タグ付けするには
                                `[タグ名]` と入力します。</p>
                        </div>

                        {{-- カスタム項目 --}}
                        <div id="custom-fields-section" style="display: none;">
                            <hr class="dark:border-gray-600">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">追加項目</h3>
                            <div id="custom-fields-container" class="space-y-4">
                                {{-- カスタム項目がここに動的に表示される --}}
                            </div>
                        </div>

                        <hr class="dark:border-gray-600">

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">閲覧範囲</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                ロールまたはユーザーを個別に指定してください。<br>本文中でメンションしたユーザーは自動で閲覧範囲に追加されます。</p>
                        </div>

                        {{-- ロール選択 --}}
                        <div>
                            <x-input-label for="role_id" value="ロールで指定" :required="true" />
                            <select id="role_id" name="role_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">-- ロールを選択してください --</option>
                                {{-- 全ユーザーを一番上に表示 --}}
                                @foreach($roles as $role)
                                    @if($role->id === 'everyone')
                                        <option value="{{ $role->id }}" @selected(old('role_id', $post->role_id ?? 'everyone') == $role->id)>
                                            {{ $role->display_name ?? $role->name }}
                                        </option>
                                    @endif
                                @endforeach
                                {{-- その他のロールを表示 --}}
                                @foreach($roles as $role)
                                    @if($role->id !== 'everyone')
                                        <option value="{{ $role->id }}" @selected(old('role_id', $post->role_id ?? 'everyone') == $role->id)>
                                            {{ $role->display_name ?? $role->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                        </div>

                        {{-- ユーザー選択 --}}
                        <div id="user-selection-section">
                            <x-input-label for="readable_users_select_edit" value="ユーザーを個別に指定" />
                            <select name="readable_user_ids[]" id="readable_users_select_edit" multiple
                                class="mt-1 block w-full">
                                @foreach($allActiveUsers as $id => $name)
                                    <option value="{{ $id }}" {{ in_array($id, old('readable_user_ids', $selectedReadableUserIds)) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('readable_user_ids')" class="mt-2" />
                            <x-input-error :messages="$errors->get('readable_user_ids.*')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('community.posts.show', $post) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 更新する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
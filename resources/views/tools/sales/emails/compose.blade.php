@extends('layouts.tool')

@section('title', '新規メール作成')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">新規メール作成</span>
@endsection

@push('styles')
    {{-- 必要に応じてTinyMCE用の追加CSSがあればここに --}}
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-4xl mx-auto mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    新規メール作成
                </h1>
                <x-secondary-button as="a" href="{{ route('tools.sales.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> 営業ツールダッシュボードへ戻る
                </x-secondary-button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">メール内容</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('tools.sales.emails.send') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="email_list_id" value="送信先メールリスト" :required="true" />
                            <x-select-input id="email_list_id" name="email_list_id" class="mt-1 block w-full"
                                :options="$emailLists" :selected="old('email_list_id')" emptyOptionText="メールリストを選択してください"
                                :hasError="$errors->has('email_list_id')" required />
                            <x-input-error :messages="$errors->get('email_list_id')" class="mt-2" />
                        </div>

                        <hr class="dark:border-gray-600">

                        <div>
                            <x-input-label for="sender_email" value="送信者メールアドレス" :required="true" />
                            <x-text-input type="email" id="sender_email" name="sender_email" class="mt-1 block w-full"
                                :value="old('sender_email', auth()->user()->email)" required
                                :hasError="$errors->has('sender_email')" />
                            <x-input-error :messages="$errors->get('sender_email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="sender_name" value="送信者名" />
                            <x-text-input type="text" id="sender_name" name="sender_name" class="mt-1 block w-full"
                                :value="old('sender_name', config('mail.from.name'))"
                                :hasError="$errors->has('sender_name')" />
                            <x-input-error :messages="$errors->get('sender_name')" class="mt-2" />
                        </div>

                        <hr class="dark:border-gray-600">

                        <div>
                            <x-input-label for="subject" value="件名" :required="true" />
                            <x-text-input type="text" id="subject" name="subject" class="mt-1 block w-full"
                                :value="old('subject')" required :hasError="$errors->has('subject')" />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="body_html" value="本文 (HTML)" :required="true" />
                            {{-- ▼▼▼ TinyMCEを適用するtextarea ▼▼▼ --}}
                            <textarea id="body_html_editor" name="body_html"
                                class="mt-1 block w-full min-h-[400px] tinymce-editor {{ $errors->has('body_html') ? 'border-red-500' : '' }}"
                                rows="18">{{ old('body_html') }}</textarea>
                            {{-- ▲▲▲ ここまで ▲▲▲ --}}
                            <x-input-error :messages="$errors->get('body_html')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">HTML形式でリッチなメールを作成できます。</p>
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            {{-- 下書き保存ボタン (将来的に実装) --}}
                        </div>
                        <div class="flex space-x-3">
                            {{-- テスト送信ボタン (将来的に実装) --}}
                            <x-primary-button type="submit">
                                <i class="fas fa-envelope mr-2"></i> 送信する (キュー投入)
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- ▼▼▼ TinyMCEのCDNと初期化スクリプト ▼▼▼ --}}
    <script src="https://cdn.tiny.cloud/1/kvqx41szc50z5cdiu0wusemey8l79d9ntaktxxzqemmzp668/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            tinymce.init({
                selector: 'textarea#body_html_editor',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount preview fullscreen insertdatetime help',
                toolbar: 'undo redo | blocks fontfamily fontsize | ' +
                    'bold italic underline strikethrough | link image media table | ' +
                    'align lineheight | numlist bullist indent outdent | ' +
                    'emoticons charmap | removeformat | preview fullscreen insertdatetime | help',
                height: 500,
                menubar: 'file edit view insert format tools table help',
                language: 'ja',

                skin: (window.matchMedia('(prefers-color-scheme: dark)').matches || document.documentElement.classList.contains('dark')) ? 'oxide-dark' : 'oxide',
                content_css: (window.matchMedia('(prefers-color-scheme: dark)').matches || document.documentElement.classList.contains('dark')) ? 'dark' : 'default',

                images_upload_url: "{{ route('tools.sales.emails.uploadImage') }}",
                automatic_uploads: true,
                file_picker_types: 'image',
                paste_data_images: true,

                // ▼▼▼ images_upload_handler をトップレベルのオプションとして定義 ▼▼▼
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', "{{ route('tools.sales.emails.uploadImage') }}");

                    const token = document.head.querySelector('meta[name="csrf-token"]');
                    if (token) {
                        xhr.setRequestHeader("X-CSRF-TOKEN", token.content);
                    } else {
                        console.error('CSRF token not found.');
                        reject({ message: 'CSRF token not found.', remove: true });
                        return;
                    }
                    // Acceptヘッダーを追加して、サーバーにJSONレスポンスを期待していることを伝える (LaravelのバリデーションエラーがJSONで返るようにするため)
                    xhr.setRequestHeader('Accept', 'application/json');


                    xhr.upload.onprogress = (e) => {
                        progress(e.loaded / e.total * 100);
                    };

                    xhr.onload = () => {
                        if (xhr.status === 403) {
                            reject({ message: 'HTTP Error: ' + xhr.status + ' - Forbidden. Check server permissions.', remove: true });
                            return;
                        }
                        // 422 Unprocessable Entity (バリデーションエラーなど) の場合
                        if (xhr.status === 422) {
                            let errorMsg = 'Validation Error: ';
                            try {
                                const jsonResponse = JSON.parse(xhr.responseText);
                                if (jsonResponse && jsonResponse.errors) {
                                    for (const key in jsonResponse.errors) {
                                        errorMsg += jsonResponse.errors[key].join(', ') + ' ';
                                    }
                                } else {
                                    errorMsg += xhr.responseText;
                                }
                            } catch (e) {
                                errorMsg += xhr.responseText;
                            }
                            reject({ message: errorMsg.trim(), remove: true }); // remove: true でアップロードUIからファイルを削除
                            return;
                        }
                        if (xhr.status < 200 || xhr.status >= 300) {
                            // 他のHTTPエラー
                            reject({ message: 'HTTP Error: ' + xhr.status + '. Response: ' + xhr.responseText, remove: true });
                            return;
                        }

                        let json;
                        try {
                            json = JSON.parse(xhr.responseText);
                        } catch (e) {
                            // JSONパース失敗時はレスポンス内容をそのまま表示してデバッグしやすくする
                            console.error("Raw server response:", xhr.responseText);
                            reject({ message: 'Invalid JSON response from server: ' + xhr.responseText, remove: true });
                            return;
                        }

                        if (!json || typeof json.location != 'string') {
                            reject({ message: 'Invalid JSON structure in response: ' + xhr.responseText, remove: true });
                            return;
                        }
                        resolve(json.location);
                    };

                    xhr.onerror = () => {
                        reject({ message: 'Image upload failed due to a XHR Transport error. Code: ' + xhr.status, remove: true });
                    };

                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());

                    xhr.send(formData);
                }),

                setup: function (editor) { // setup関数は他の目的（例: changeイベント）のために残す
                    editor.on('change', function () {
                        editor.save();
                    });
                }
            });
        });
    </script>
@endpush
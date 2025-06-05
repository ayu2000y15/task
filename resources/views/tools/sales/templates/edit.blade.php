@extends('layouts.tool')

@section('title', 'メールテンプレート編集 - ' . $template->name)

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.templates.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">メールテンプレート管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200 truncate" title="{{ $template->name }}">{{ Str::limit($template->name, 20) }}</span>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">編集</span>
@endsection

@push('scripts')
{{-- TinyMCEのCDNと初期化スクリプト (APIキーはご自身のものに置き換えてください) --}}
<script src="https://cdn.tiny.cloud/1/m3870xzvadd7jh67mc2gi5s50oen09a7yebhko8uvquwfy0x/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    tinymce.init({
      selector: 'textarea#body_html_editor_template_edit', // ★ IDを変更
      relative_urls: false,    // 生成されるURLを常に絶対パスにする
      remove_script_host: false, // ホスト名を削除しない (絶対パスを維持)
      convert_urls: false,     // URLを相対パスに変換しない
      document_base_url: "{{ url('/') }}/", // ★ アプリケーションのベースURLを明示的に指定

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
      images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.withCredentials = false;
        xhr.open('POST', "{{ route('tools.sales.emails.uploadImage') }}");
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
          xhr.setRequestHeader("X-CSRF-TOKEN", token.content);
        } else {
          reject({ message: 'CSRF token not found.', remove: true });
          return;
        }
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = (e) => progress(e.loaded / e.total * 100);
        xhr.onload = () => {
          if (xhr.status === 403) {
            reject({ message: 'HTTP Error: ' + xhr.status + ' - Forbidden.', remove: true }); return;
          }
          if (xhr.status < 200 || xhr.status >= 300) {
            reject('HTTP Error: ' + xhr.status + '. Response: ' + xhr.responseText); return;
          }
          let json;
          try { json = JSON.parse(xhr.responseText); }
          catch (e) { reject('Invalid JSON: ' + xhr.responseText); return; }
          if (!json || typeof json.location != 'string') {
            reject('Invalid JSON response: ' + xhr.responseText); return;
          }
          resolve(json.location);
        };
        xhr.onerror = () => reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        xhr.send(formData);
      }),
      setup: function (editor) {
        editor.on('change', function () {
          editor.save();
        });
      }
    });
  });
</script>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto mb-6"> {{-- フォーム幅を少し広げる --}}
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                メールテンプレート編集: <span class="font-normal text-xl truncate" title="{{ $template->name }}">{{ Str::limit($template->name, 30) }}</span>
            </h1>
            <div class="flex space-x-2 flex-shrink-0">
                <x-secondary-button as="a" href="{{ route('tools.sales.templates.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> テンプレート一覧へ戻る
                </x-secondary-button>
                @can('tools.sales.access') {{-- 適切な権限で --}}
                    <form action="{{ route('tools.sales.templates.destroy', $template) }}" method="POST" class="inline-block"
                          onsubmit="return confirm('本当にテンプレート「{{ $template->name }}」を削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit" class="py-1 px-3 text-xs">
                            <i class="fas fa-trash mr-1"></i>削除
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">テンプレート情報編集</h2>
        </div>
        <div class="p-6 sm:p-8">
            <form action="{{ route('tools.sales.templates.update', $template) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" value="テンプレート名" :required="true" />
                        <x-text-input type="text" id="name" name="name" class="mt-1 block w-full"
                            :value="old('name', $template->name)" required :hasError="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="subject" value="件名" />
                        <x-text-input type="text" id="subject" name="subject" class="mt-1 block w-full"
                            :value="old('subject', $template->subject)" :hasError="$errors->has('subject')" />
                        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="body_html_editor_template_edit" value="本文 (HTML)" />
                        <textarea id="body_html_editor_template_edit" name="body_html" class="mt-1 block w-full tinymce-editor {{ $errors->has('body_html') ? 'border-red-500' : '' }}"
                            rows="20">{{ old('body_html', $template->body_html) }}</textarea>
                        <x-input-error :messages="$errors->get('body_html')" class="mt-2" />
                        <div
                            class="mt-2 p-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-md text-xs text-gray-600 dark:text-gray-400">
                            <p class="font-semibold mb-1">利用可能なプレースホルダー (本文または件名に記述):</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li><code>{{ '{' }}{{ '{' }}email}}</code> - 受信者のメールアドレス</li>
                                <li><code>{{ '{' }}{{ '{' }}name}}</code> - 受信者の名前</li>
                                <li><code>{{ '{' }}{{ '{' }}company_name}}</code> - 会社名</li>
                                <li><code>{{ '{' }}{{ '{' }}postal_code}}</code> - 郵便番号</li>
                                <li><code>{{ '{' }}{{ '{' }}address}}</code> - 住所</li>
                                <li><code>{{ '{' }}{{ '{' }}phone_number}}</code> - 電話番号</li>
                                <li><code>{{ '{' }}{{ '{' }}fax_number}}</code> - FAX番号</li>
                                <li><code>{{ '{' }}{{ '{' }}url}}</code> - URL</li>
                                <li><code>{{ '{' }}{{ '{' }}representative_name}}</code> - 代表者名</li>
                                <li><code>{{ '{' }}{{ '{' }}establishment_date}}</code> - 設立日 (YYYY年M月D日 形式)</li>
                                <li><code>{{ '{' }}{{ '{' }}industry}}</code> - 業種</li>
                            </ul>
                            <p class="mt-1">例: <code>{{ '{' }}{{ '{' }}company_name}}</code>
                                <code>{{ '{' }}{{ '{' }}name}}</code> 様</p>
                        </div>
                    </div>

                    {{-- <div>
                        <x-input-label for="body_text" value="本文 (プレーンテキスト - 任意)" />
                        <x-textarea-input id="body_text" name="body_text" class="mt-1 block w-full"
                            rows="10" :hasError="$errors->has('body_text')">{{ old('body_text', $template->body_text) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('body_text')" class="mt-2" />
                    </div> --}}
                </div>

                <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('tools.sales.templates.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> テンプレートを更新
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
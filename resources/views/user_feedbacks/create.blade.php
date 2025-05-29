@extends('layouts.app')

@section('title', 'フィードバックを送信')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="feedback-form-page">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                フィードバックを送信
            </h1>
            <a href="{{ route('home.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> ホームに戻る
            </a>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                {{-- ★★★ formタグに onsubmit を追加 ★★★ --}}
                <form action="{{ route('user_feedbacks.store') }}" method="POST" enctype="multipart/form-data"
                    id="feedback-form" onsubmit="return confirm('フィードバックを送信しますか？');">
                    @csrf
                    <div class="grid grid-cols-1 gap-y-6">

                        <div>
                            <x-input-label for="submitter_name" value="お名前" :required="true" />
                            <x-text-input id="submitter_name" name="submitter_name" type="text" class="mt-1 block w-full"
                                :value="old('submitter_name', Auth::user()->name)" required
                                :hasError="$errors->has('submitter_name')" />
                            <x-input-error :messages="$errors->get('submitter_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="title" value="タイトル" :required="true" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                :value="old('title')" required :hasError="$errors->has('title')"
                                placeholder="例: 工程管理画面の表示について" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="feedback_category_id" value="カテゴリ" :required="true" />
                            <x-select-input id="feedback_category_id" name="feedback_category_id" class="mt-1 block w-full"
                                :options="$categories" :selected="old('feedback_category_id')"
                                :hasError="$errors->has('feedback_category_id')" required />
                            <x-input-error :messages="$errors->get('feedback_category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" value="メールアドレス" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                :value="old('email')" :hasError="$errors->has('email')" placeholder="例: user@example.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">返信が必要な場合はご入力ください。</p>
                        </div>

                        <div>
                            <x-input-label for="phone" value="電話番号" />
                            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone')"
                                :hasError="$errors->has('phone')" placeholder="例: 090-1234-5678" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="content" value="内容" :required="true" />
                            <x-textarea-input id="content" name="content" class="mt-1 block w-full" rows="8"
                                :hasError="$errors->has('content')"
                                placeholder="具体的な内容をご記入ください。&#10;例:&#10;・どのような操作をした時に問題が発生しましたか？&#10;・どのような状態になることを期待しますか？">{{ old('content') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('content')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="images" value="画像ファイル (5枚まで・各5MB以内)" />
                            <input type="file" name="images[]" id="images" multiple
                                class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-indigo-50 file:text-indigo-700
                                          dark:file:bg-indigo-700/20 dark:file:text-indigo-300
                                          hover:file:bg-indigo-100 dark:hover:file:bg-indigo-600/30
                                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800
                                          {{ $errors->has('images') || $errors->has('images.*') ? 'border-red-500 dark:border-red-600 ring-1 ring-red-500' : 'border-gray-300 dark:border-gray-600' }} rounded-md shadow-sm"
                                accept="image/jpeg,image/png,image/gif" onchange="previewSelectedImages(event)">
                            <x-input-error :messages="$errors->get('images')" class="mt-2" />
                            @foreach ($errors->get('images.*') as $messageArray)
                                @foreach ((array) $messageArray as $message) {{-- エラーメッセージが配列の場合に対応 --}}
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @endforeach
                            @endforeach
                            <div id="image-preview-container"
                                class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                {{-- プレビュー画像がここに表示されます --}}
                            </div>
                        </div>

                    </div>

                    <div class="mt-8 flex justify-end space-x-3">
                        <a href="{{ route('home.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            キャンセル
                        </a>
                        <x-primary-button type="submit">
                            <i class="fas fa-paper-plane mr-2"></i> 送信する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function previewSelectedImages(event) {
                const previewContainer = document.getElementById('image-preview-container');
                previewContainer.innerHTML = '';
                const files = event.target.files;

                if (files.length > 5) {
                    alert('アップロードできる画像は5枚までです。');
                    event.target.value = ''; // ファイル選択をリセット
                    return;
                }

                Array.from(files).forEach(file => {
                    if (!file.type.startsWith('image/')) { return; }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imgElement = document.createElement('img');
                        imgElement.src = e.target.result;
                        imgElement.alt = file.name;
                        imgElement.classList.add('w-full', 'h-32', 'object-cover', 'rounded-md', 'shadow');
                        previewContainer.appendChild(imgElement);
                    }
                    reader.readAsDataURL(file);
                });
            }
        </script>
    @endpush

@endsection
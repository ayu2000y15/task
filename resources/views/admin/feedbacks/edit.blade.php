@extends('layouts.app')

@section('title', 'フィードバック編集 - ID: ' . $feedback->id)

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="feedback-edit-page">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
            フィードバック編集 <span class="text-lg text-gray-500 dark:text-gray-400">(ID: {{ $feedback->id }})</span>
        </h1>
        <a href="{{ route('admin.feedbacks.index') }}"
            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-arrow-left mr-2"></i> フィードバック一覧に戻る
        </a>
    </div>

    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="p-6 sm:p-8">
            <form action="{{ route('admin.feedbacks.update', $feedback) }}" method="POST" enctype="multipart/form-data" id="feedback-edit-form" onsubmit="return confirm('この内容でフィードバックを更新しますか？');">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-y-6">

                    <div>
                        <x-input-label for="user_name_display" value="送信者" />
                        <x-text-input id="user_name_display" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700" :value="$feedback->user_name . ($feedback->user ? ' (ID:'.$feedback->user->id.')' : '')" readonly />
                    </div>

                    <div>
                        <x-input-label for="title" value="タイトル" :required="true" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $feedback->title)" required :hasError="$errors->has('title')" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="feedback_category_id" value="カテゴリ" :required="true" />
                        <x-select-input id="feedback_category_id" name="feedback_category_id" class="mt-1 block w-full"
                                        :options="$categories"
                                        :selected="old('feedback_category_id', $feedback->feedback_category_id)"
                                        :hasError="$errors->has('feedback_category_id')"
                                        required />
                        <x-input-error :messages="$errors->get('feedback_category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="priority" value="優先度" :required="true" />
                        <x-select-input id="priority" name="priority" class="mt-1 block w-full"
                                        :options="$priorities"
                                        :selected="old('priority', $feedback->priority)"
                                        :hasError="$errors->has('priority')"
                                        required />
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="連絡先メールアドレス" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $feedback->email)" :hasError="$errors->has('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="連絡先電話番号" />
                        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $feedback->phone)" :hasError="$errors->has('phone')" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="content" value="内容" :required="true" />
                        <x-textarea-input id="content" name="content" class="mt-1 block w-full" rows="8" :hasError="$errors->has('content')">{{ old('content', $feedback->content) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>

                    {{-- 管理者用フィールド --}}
                    <hr class="dark:border-gray-700 my-2">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">管理者用設定</h3>

                    <div>
                        <x-input-label for="status" value="対応ステータス" :required="true"/>
                        <x-select-input id="status" name="status" class="mt-1 block w-full"
                                        :options="$statuses"
                                        :selected="old('status', $feedback->status)"
                                        :hasError="$errors->has('status')"
                                        required />
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                     <div>
                        <x-input-label for="assignee_text" value="担当者" />
                        <x-text-input id="assignee_text" name="assignee_text" type="text" class="mt-1 block w-full" :value="old('assignee_text', $feedback->assignee_text)" :hasError="$errors->has('assignee_text')" />
                        <x-input-error :messages="$errors->get('assignee_text')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="admin_memo" value="管理者メモ" />
                        <x-textarea-input id="admin_memo" name="admin_memo" class="mt-1 block w-full" rows="4" :hasError="$errors->has('admin_memo')">{{ old('admin_memo', $feedback->admin_memo) }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('admin_memo')" class="mt-2" />
                    </div>

                    @if($feedback->completed_at)
                    <div>
                        <x-input-label value="完了日時" />
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $feedback->completed_at->format('Y/m/d H:i:s') }}</p>
                    </div>
                    @endif
                    {{-- ここまで管理者用フィールド --}}


                    <div>
                        <x-input-label for="images" value="画像ファイル (合計5枚まで・各5MB以内)" />
                        {{-- 既存ファイルの表示と削除 --}}
                        @if($feedback->files->isNotEmpty())
                            <div class="mt-2 mb-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">既存のファイル:</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                    @foreach($feedback->files as $file)
                                        <div class="relative group border p-1 rounded-md dark:border-gray-600">
                                            <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="block aspect-square preview-image" data-full-image-url="{{ Storage::url($file->file_path) }}">
                                                @if(Str::startsWith($file->mime_type, 'image/'))
                                                    <img src="{{ Storage::url($file->file_path) }}" alt="{{ $file->original_name }}" class="w-full h-full object-cover rounded-md">
                                                @else
                                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-md p-2 text-center">
                                                        <i class="fas fa-file-alt text-3xl text-gray-400 dark:text-gray-500 mb-1"></i>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $file->original_name }}">{{ Str::limit($file->original_name, 15) }}</p>
                                                    </div>
                                                @endif
                                            </a>
                                            <label for="delete_image_{{ $file->id }}" class="absolute top-1 right-1 flex items-center p-0.5 bg-white/70 dark:bg-black/70 rounded-full hover:bg-red-100 dark:hover:bg-red-800 cursor-pointer" title="この画像を削除">
                                                <input type="checkbox" name="delete_images[]" id="delete_image_{{ $file->id }}" value="{{ $file->id }}" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500 dark:bg-gray-900 dark:border-gray-600 dark:focus:ring-red-600">
                                                <i class="fas fa-times text-red-500 dark:text-red-400 ml-1 fa-xs"></i>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">削除したいファイルにチェックを入れてください。</p>
                            </div>
                        @endif

                        {{-- 新規ファイルアップロード --}}
                        <input type="file" name="images[]" id="images" multiple
                               class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-700/20 dark:file:text-indigo-300
                                      hover:file:bg-indigo-100 dark:hover:file:bg-indigo-600/30
                                      focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800
                                      {{ $errors->has('images') || $errors->has('images.*') ? 'border-red-500 dark:border-red-600 ring-1 ring-red-500' : 'border-gray-300 dark:border-gray-600' }} rounded-md shadow-sm"
                               accept="image/jpeg,image/png,image/gif"
                               onchange="previewSelectedImages(event, {{ $feedback->files->count() }})"> {{-- 第2引数に既存ファイル数を渡す --}}
                        <x-input-error :messages="$errors->get('images')" class="mt-2" />
                        @foreach ($errors->get('images.*') as $messageArray)
                            @foreach ((array) $messageArray as $message)
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @endforeach
                        @endforeach
                        <div id="image-preview-container" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                            {{-- 新規選択された画像のプレビューがここに表示されます --}}
                        </div>
                    </div>

                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.feedbacks.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        キャンセル
                    </a>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> 更新する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function previewSelectedImages(event, existingFileCount = 0) {
        const previewContainer = document.getElementById('image-preview-container');
        previewContainer.innerHTML = '';
        const files = event.target.files;
        const totalAllowed = 5;
        const newFilesLimit = totalAllowed - existingFileCount;

        if (files.length > newFilesLimit) {
            alert(`アップロードできる新しい画像は ${newFilesLimit} 枚までです。(既存ファイルと合わせて最大5枚)`);
            event.target.value = '';
            return;
        }

        Array.from(files).forEach(file => {
            if (!file.type.startsWith('image/')){ return; }

            const reader = new FileReader();
            reader.onload = function(e) {
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
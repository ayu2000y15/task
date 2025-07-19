{{-- resources/views/admin/external_submissions/show.blade.php --}}
@extends('layouts.app')

@section('title', '外部フォーム詳細 - ID: ' . $submission->id)

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
            {{ $formCategory ? $formCategory->display_name : 'フォーム種別不明' }} - ID: {{ $submission->id }}
            {{-- ステータスバッジはサーバーサイドでレンダリングされるため、IDは必須ではなくなります --}}
            <span class="px-2 py-0.5 ml-2 inline-flex text-xs leading-5 font-semibold rounded-full
                @switch($submission->status)
                    @case('new') bg-sky-100 text-sky-800 dark:bg-sky-700 dark:text-sky-100 @break
                    @case('in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 @break
                    @case('processed') bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100 @break
                    @case('on_hold') bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300 @break
                    @case('rejected') bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100 @break
                    @default bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100
                @endswitch
            ">
                {{ $statusOptions[$submission->status] ?? $submission->status }}
            </span>
        </h1>
        <x-secondary-button as="a" href="{{ route('admin.external-submissions.index') }}">
            <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
        </x-secondary-button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- 左側: 各情報カード --}}
        <div class="md:col-span-2 space-y-6">
            {{-- 基本情報カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 dark:border-gray-600 pb-2 mb-4">基本情報</h3>
                    <div class="space-y-0">
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">依頼者名:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $submission->submitter_name }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">メールアドレス:</strong>
                            <span class="text-gray-900 dark:text-gray-100 break-all">{{ $submission->submitter_email }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">依頼日時:</strong>
                            <span class="text-gray-900 dark:text-gray-100">{{ $submission->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">依頼者備考:</strong>
                            <p class="whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-2 rounded-md text-gray-900 dark:text-gray-100 break-all">{{ trim($submission->submitter_notes) ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 外部フォームフィールド入力内容カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 dark:border-gray-600 pb-2 mb-4">{{ $formCategory ? $formCategory->display_name : 'フォーム種別不明' }} フィールド入力内容</h3>
                    <div class="space-y-0">
                        @forelse ($displayData as $data)
                            <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 @if(!$loop->last || count($fileFields) > 0) border-b border-gray-200 dark:border-gray-700 @endif">
                                <strong class="font-semibold text-gray-700 dark:text-gray-300">{{ $data['label'] }}:</strong>
                                <div class="break-all text-gray-900 dark:text-gray-100">
                                    @if ($data['type'] === 'textarea')
                                        <p class="whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-2 rounded-md break-all">{{ $data['value'] ? trim($data['value']) : '-' }}</p>
                                    @elseif ($data['type'] === 'checkbox')
                                        @if (is_array($data['value']))
                                            {{-- 値が配列の場合、カンマ区切りで表示 --}}
                                            <span>{{ !empty($data['value']) ? implode(', ', $data['value']) : '-' }}</span>
                                        @else
                                            {{-- 値が配列でない場合（単一のチェックボックスなど）、はい/いいえで表示 --}}
                                            <span>{{ $data['value'] ? 'はい' : 'いいえ' }}</span>
                                        @endif
                                    @elseif (($data['type'] === 'select' || $data['type'] === 'radio') && isset($data['options']) && is_array($data['options']))
                                        <span>{{ $data['options'][$data['value']] ?? $data['value'] ?: '-' }}</span>
                                    @elseif ($data['type'] === 'image_select' && isset($data['options']) && is_array($data['options']))
                                        @php
                                            // 値が配列でなくても、処理を共通化するために配列に変換する
                                            $selectedValues = is_array($data['value']) ? array_filter($data['value']) : ( $data['value'] ? [$data['value']] : [] );
                                        @endphp

                                        @if(!empty($selectedValues))
                                            {{-- 複数の選択肢をきれいに表示するために flex と wrap を使う --}}
                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                                {{-- 選択された値をループで処理 --}}
                                                @foreach($selectedValues as $selectedValue)
                                                    @if(isset($data['options'][$selectedValue]))
                                                        @php
                                                            $imageUrl = $data['options'][$selectedValue]; // 画像のURL
                                                        @endphp
                                                        <div class="flex flex-col items-center w-12">
                                                            <a href="{{ $imageUrl }}" class="image-preview-trigger flex-shrink-0 mb-1" title="プレビュー: {{ $selectedValue }}">
                                                                <img src="{{ $imageUrl }}" alt="{{ $selectedValue }}" class="w-12 h-12 object-cover rounded-md border dark:border-gray-600 hover:opacity-80 transition-opacity">
                                                            </a>
                                                            <span class="text-xs text-gray-500 text-center w-full block">{{ $selectedValue }}</span>
                                                        </div>
                                                    @else
                                                        {{-- optionsにキーが存在しない場合（データ不整合など） --}}
                                                        <span class="text-sm text-gray-500">{{ $selectedValue }} (画像なし)</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span>-</span>
                                        @endif
                                    @elseif ($data['type'] === 'color')
                                        @if($data['value'])
                                        <span class="inline-flex items-center">
                                            <span style="background-color: {{ $data['value'] }}; width: 20px; height: 20px; display: inline-block; border: 1px solid #ccc; margin-right: 8px; border-radius: 0.25rem;"></span>
                                            {{ $data['value'] }}
                                        </span>
                                        @else
                                            -
                                        @endif
                                    @elseif ($data['type'] === 'url')
                                        @if($data['value'])
                                            <a href="{{ $data['value'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 underline">
                                                {{ $data['value'] }}
                                            </a>
                                        @else
                                            <span>-</span>
                                        @endif
                                    @else
                                        @if(is_array($data['value']))
                                            {{-- 値が配列の場合、カンマ区切りで安全に表示 --}}
                                            <span>{{ implode(', ', $data['value']) }}</span>
                                        @else
                                            {{-- 配列でない場合はそのまま表示 --}}
                                            <span>{{ $data['value'] ?: '-' }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            {{-- このメッセージはファイルフィールドも空の場合にのみ表示されるよう、下の@ifで制御 --}}
                        @endforelse

                        @foreach ($fileFields as $fileField)
                            @php
                                // ★★★ ここから追加 ★★★
                                // データ構造を正規化して、単一・複数の両方に対応する
                                $filesToDisplay = [];
                                if (!empty($fileField['value'])) {
                                    // タイプが'file'の場合、値はファイル情報そのものなので、配列でラップする
                                    if ($fileField['type'] === 'file' && isset($fileField['value']['path'])) {
                                        $filesToDisplay = [$fileField['value']];
                                    }
                                    // タイプが'file_multiple'の場合、値はファイルの配列なのでそのまま使う
                                    elseif ($fileField['type'] === 'file_multiple' && is_array($fileField['value'])) {
                                        $filesToDisplay = $fileField['value'];
                                    }
                                }
                                // ★★★ ここまで追加 ★★★
                            @endphp
                            <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-start py-3 @if(!$loop->last) border-b border-gray-200 dark:border-gray-700 @endif">
                                <strong class="font-semibold text-gray-700 dark:text-gray-300 pt-1">{{ $fileField['label'] }}:</strong>
                                <div>
                                    {{-- ★ ifの条件を新しい変数に変更 --}}
                                    @if(!empty($filesToDisplay))
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-x-3 gap-y-4 mt-1">
                                            {{-- ★ foreachの対象を新しい変数に変更 --}}
                                            @foreach($filesToDisplay as $fileInfo)
                                                {{-- カード表示のロジック (この中身は変更なし) --}}
                                                @if(is_array($fileInfo) && isset($fileInfo['path']) && isset($fileInfo['original_name']))
                                                    @if(isset($fileInfo['mime_type']) && Str::startsWith($fileInfo['mime_type'], 'image/'))
                                                        <div class="flex flex-col items-center">
                                                            {{-- 画像プレビューカード --}}
                                                            <a href="{{ Storage::url($fileInfo['path']) }}"
                                                            class="block relative group rounded-md overflow-hidden border dark:border-gray-600 aspect-square hover:shadow-lg transition-shadow image-preview-trigger"
                                                            title="プレビュー: {{ $fileInfo['original_name'] }} @if(isset($fileInfo['size'])) ({{ \Illuminate\Support\Number::fileSize($fileInfo['size']) }}) @endif">
                                                                <img src="{{ Storage::url($fileInfo['path']) }}" alt="{{ $fileInfo['original_name'] }}" class="w-20 h-20 object-cover">
                                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-opacity">
                                                                    <i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                                                </div>
                                                            </a>
                                                            {{-- ファイル名とサイズ --}}
                                                            <div class="mt-2 text-center w-full px-1">
                                                                <a href="{{ Storage::url($fileInfo['path']) }}" download="{{ $fileInfo['original_name'] }}"
                                                                class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 font-medium break-all leading-tight line-clamp-2 underline"
                                                                title="{{ $fileInfo['original_name'] }}">
                                                                    {{ $fileInfo['original_name'] }}
                                                                </a>
                                                                @if(isset($fileInfo['size']))
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">({{ \Illuminate\Support\Number::fileSize($fileInfo['size']) }})</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- 画像以外のファイルカード --}}
                                                        <div class="flex flex-col items-center">
                                                            <div class="block relative group rounded-md overflow-hidden border dark:border-gray-600 aspect-square w-full cursor-default"
                                                                title="{{ $fileInfo['original_name'] }} @if(isset($fileInfo['size'])) ({{ \Illuminate\Support\Number::fileSize($fileInfo['size']) }}) @endif">
                                                                <div class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 p-2 text-center">
                                                                    <i class="fas fa-file-alt text-4xl text-gray-400 dark:text-gray-500"></i>
                                                                </div>
                                                            </div>
                                                            <div class="mt-2 text-center w-full px-1">
                                                                <a href="{{ Storage::url($fileInfo['path']) }}" download="{{ $fileInfo['original_name'] }}"
                                                                class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 font-medium break-all leading-tight line-clamp-2 underline"
                                                                title="{{ $fileInfo['original_name'] }}">
                                                                    {{ $fileInfo['original_name'] }}
                                                                </a>
                                                                @if(isset($fileInfo['size']))
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">({{ \Illuminate\Support\Number::fileSize($fileInfo['size']) }})</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-900 dark:text-gray-100">-</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(empty($displayData) && empty($fileFields))
                            <p class="text-gray-500 dark:text-gray-400 py-3">案件依頼フィールドの入力はありませんでした。</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 管理情報カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 dark:border-gray-600 pb-2 mb-4">管理情報</h3>
                    <div class="space-y-0">
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-center py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">現在のステータス:</strong>
                            {{-- IDは不要になります --}}
                            <span class="text-gray-900 dark:text-gray-100">{{ $statusOptions[$submission->status] ?? $submission->status }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-center py-3 border-b border-gray-200 dark:border-gray-700">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">最終対応者:</strong>
                            {{-- IDは不要になります --}}
                            <span class="text-gray-900 dark:text-gray-100 break-all">{{ $submission->processedBy->name ?? '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[theme(spacing.40)_1fr] gap-x-2 items-center py-3">
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">最終対応日時:</strong>
                            {{-- IDは不要になります --}}
                            <span class="text-gray-900 dark:text-gray-100">{{ $submission->processed_at ? $submission->processed_at->format('Y/m/d H:i') : '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 右側: ステータス更新フォーム --}}
        <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 border-b border-gray-300 dark:border-gray-600 pb-2">ステータス更新</h3>
                @can('update', $submission)
                @if($submission->status === 'processed')
                    {{-- 案件化済みの場合、操作不可のメッセージを表示 --}}
                    <div class="bg-blue-50 dark:bg-gray-700/50 border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded-r-lg" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    この依頼は案件化済みのため、ステータスは変更できません。
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- フォームのidはJavaScriptで参照しなくなるため、必須ではなくなります --}}
                    <form action="{{ route('admin.external-submissions.updateStatus', $submission) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="status" value="新しいステータス" :required="true"/>
                                <select name="status" id="status" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('status') border-red-500 @enderror" required>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', $submission->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                            {{-- 必要であれば、管理者メモなどの入力フィールドをここに追加できます --}}
                            {{--
                            <div>
                                <x-input-label for="manager_notes" value="管理者備考（任意）" />
                                <x-textarea-input id="manager_notes" name="manager_notes" class="mt-1 block w-full" rows="3">{{ old('manager_notes', $submission->manager_notes ?? '') }}</x-textarea-input>
                                <x-input-error :messages="$errors->get('manager_notes')" class="mt-2" />
                            </div>
                            --}}
                            <x-primary-button type="submit" class="w-full justify-center">
                                <i class="fas fa-sync-alt mr-2"></i>ステータスを更新
                            </x-primary-button>
                        </div>
                    </form>
                    @endif
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">この依頼のステータスを更新する権限がありません。</p>
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- 画像プレビューモーダル --}}
<div id="imagePreviewModal" class="fixed z-50 top-0 left-0 w-full h-full bg-black bg-opacity-75 hidden flex items-center justify-center p-4">
    <div class=" dark:bg-gray-900 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-auto relative">
        <button onclick="document.getElementById('imagePreviewModal').classList.add('hidden')" class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none z-10 bg-white/50 dark:bg-black/50 rounded-full p-1">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <div class="p-4">
            <img id="previewImage" src="" alt="画像プレビュー" class="w-full h-auto object-contain" style="max-height: calc(90vh - 4rem);">
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ステータス更新フォームの非同期処理は削除されました。
        // JavaScriptによるDOM更新やalert表示も不要になります。

        // 画像プレビューモーダルの処理 (これは変更なし、そのまま残します)
        const imageLinks = document.querySelectorAll('.image-preview-trigger');
        const previewImageElement = document.getElementById('previewImage');
        const imagePreviewModal = document.getElementById('imagePreviewModal');

        if (imagePreviewModal && previewImageElement) {
            imageLinks.forEach(link => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    previewImageElement.src = this.href;
                    imagePreviewModal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden'; // 背景スクロール禁止
                });
            });

            // モーダルを閉じる処理 (×ボタン、背景クリック、Escキー)
            const closeModal = () => {
                imagePreviewModal.classList.add('hidden');
                previewImageElement.src = ''; // 画像のsrcをクリア
                document.body.style.overflow = ''; // 背景スクロール許可
            };

            const closeButton = imagePreviewModal.querySelector('button'); // モーダル内の閉じるボタンを特定
            if(closeButton) { // ボタンが存在する場合のみイベントリスナーを追加
                closeButton.addEventListener('click', closeModal);
            }


            imagePreviewModal.addEventListener('click', function(event) {
                if (event.target === imagePreviewModal) { // 背景クリック時
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === "Escape" && !imagePreviewModal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        }
    });
</script>
@endpush

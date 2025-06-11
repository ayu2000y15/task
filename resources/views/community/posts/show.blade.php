@extends('layouts.app')

@section('title', '投稿詳細 - ' . Str::limit($post->title, 50))

@section('breadcrumbs')
    <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('community.posts.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">社内掲示板</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200 truncate">{{ Str::limit($post->title, 40) }}</span>
@endsection

@push('scripts')
    <link href="{{ asset('css/tinymce-content.css') }}" rel="stylesheet">
    {{-- 絵文字ピッカーのライブラリ --}}
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    {{-- TinyMCEのCDN --}}
    <script src="https://cdn.tiny.cloud/1/m3870xzvadd7jh67mc2gi5s50oen09a7yebhko8uvquwfy0x/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /**
             * メンション機能付きTinyMCEエディタを初期化する共通関数
             * @param {string} selector - TinyMCEを適用する要素のCSSセレクタ
             * @param {number} height - エディタの高さ
             */
            function initializeMentionEditor(selector, height) {
                tinymce.init({
                    selector: selector,
                    height: height,
                    plugins: 'autolink charmap emoticons link lists searchreplace wordcount help preview',
                    toolbar: 'undo redo | bold italic | bullist numlist | emoticons | help | preview',
                    menubar: false,
                    statusbar: false,
                    language: 'ja',
                    content_css: '../css/tinymce-content.css', // カスタムCSSを指定
                    entity_encoding: 'raw', // HTMLエスケープを防ぐ
                    relative_urls: false,
                    remove_script_host: false,
                    convert_urls: false,
                    document_base_url: "{{ url('/') }}/",
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
                            items.forEach((item) => {
                                const div = document.createElement('div');
                                div.className = 'mention-suggestion-item';
                                div.innerHTML = `<i class="fas fa-user fa-fw mr-2"></i>${item.text}`;
                                div.dataset.mentionId = item.id;
                                div.addEventListener('click', () => { insertMention(item); hideSuggestions(); });
                                suggestionContainer.appendChild(div);
                            });

                            const iframe = editor.getContainer().querySelector('iframe');
                            const iframeRect = iframe.getBoundingClientRect();
                            const markerId = 'tiny-mention-marker-' + editor.id;
                            editor.execCommand('mceInsertContent', false, `<span id="${markerId}"></span>`);
                            const markerEl = editor.dom.get(markerId);
                            const cursorRect = markerEl ? markerEl.getBoundingClientRect() : null;
                            if (markerEl) editor.dom.remove(markerEl);

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
                            if (!range.startContainer || !range.startContainer.textContent) return;
                            const textBeforeCursor = range.startContainer.textContent.substring(0, range.startOffset);
                            const mentionMatch = textBeforeCursor.match(/@([\p{L}\p{N}_-]*)$/u);
                            if (mentionMatch) {
                                fetchUsers(mentionMatch[1] || '');
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
            }

            // --- 新規コメント欄のエディタを初期化 ---
            initializeMentionEditor('textarea#comment_body_editor', 200);

            // --- 各機能のイベントリスナーをセットアップ ---
            const postReactionsContainer = document.getElementById('reactions-container');
            const commentsContainer = document.querySelector('.comment-list-container');
            const mainCommentForm = document.getElementById('main-comment-form-container');

            // 投稿へのリアクションを処理する関数
            function togglePostReaction(emoji) {
                const postId = postReactionsContainer.dataset.postId;
                const token = document.head.querySelector('meta[name="csrf-token"]').content;
                fetch(`/community/posts/${postId}/reactions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ emoji: emoji })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && postReactionsContainer) {
                            postReactionsContainer.innerHTML = data.html;
                        }
                    });
            }

            // コメントへのリアクションを処理する関数
            function toggleCommentReaction(commentId, emoji) {
                const token = document.head.querySelector('meta[name="csrf-token"]').content;
                fetch(`/community/comments/${commentId}/reactions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ emoji: emoji })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const commentReactionsContainer = document.querySelector(`.comment-reactions-container[data-comment-id='${commentId}']`);
                            if (commentReactionsContainer) {
                                commentReactionsContainer.innerHTML = data.html;
                            }
                        }
                    });
            }

            // 【投稿】のリアクションイベント
            if (postReactionsContainer) {
                postReactionsContainer.addEventListener('click', (e) => {
                    const reactionBadge = e.target.closest('.reaction-badge');
                    if (reactionBadge) togglePostReaction(reactionBadge.dataset.emoji);

                    const addReactionBtn = e.target.closest('#add-reaction-btn');
                    if (addReactionBtn) {
                        e.stopPropagation();
                        const picker = postReactionsContainer.querySelector('emoji-picker');
                        if (picker) picker.style.display = picker.style.display === 'block' ? 'none' : 'block';
                    }
                });
                postReactionsContainer.addEventListener('emoji-click', e => {
                    togglePostReaction(e.detail.emoji.unicode);
                    const picker = postReactionsContainer.querySelector('emoji-picker');
                    if (picker) picker.style.display = 'none';
                });
            }

            // 【コメント】の全てのクリック/送信イベントを一元管理
            if (commentsContainer) {
                // クリックイベントの委譲
                commentsContainer.addEventListener('click', function (e) {
                    const editBtn = e.target.closest('.edit-comment-btn');
                    const cancelBtn = e.target.closest('.cancel-edit-btn');
                    const deleteBtn = e.target.closest('.delete-comment-btn');
                    const replyBtn = e.target.closest('.reply-to-comment-btn');
                    const reactionBadge = e.target.closest('.comment-reaction-badge');
                    const addReactionBtn = e.target.closest('.add-comment-reaction-btn');

                    if (editBtn) {
                        e.preventDefault();
                        const commentContainer = editBtn.closest('.comment-container');
                        const commentId = commentContainer.id.split('-')[1];
                        const editorId = `comment-editor-${commentId}`;
                        commentContainer.querySelector('.comment-display-area').style.display = 'none';
                        commentContainer.querySelector('.comment-edit-form').style.display = 'block';
                        initializeMentionEditor(`#${editorId}`, 150);
                    }
                    else if (cancelBtn) {
                        e.preventDefault();
                        const commentContainer = cancelBtn.closest('.comment-container');
                        const commentId = commentContainer.id.split('-')[1];
                        const editorId = `comment-editor-${commentId}`;
                        const editorInstance = tinymce.get(editorId);
                        if (editorInstance) { editorInstance.remove(); }
                        commentContainer.querySelector('.comment-display-area').style.display = 'block';
                        commentContainer.querySelector('.comment-edit-form').style.display = 'none';
                    }
                    else if (deleteBtn) {
                        e.preventDefault();
                        if (confirm('本当にこのコメントを削除しますか？')) {
                            const commentId = deleteBtn.dataset.commentId;
                            const token = document.head.querySelector('meta[name="csrf-token"]').content;
                            fetch(`/community/comments/${commentId}`, {
                                method: 'DELETE',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                            }).then(response => response.json()).then(data => {
                                if (data.success) { document.getElementById(`comment-${commentId}`).remove(); }
                                else { alert(data.message || 'コメントの削除に失敗しました。'); }
                            }).catch(error => console.error('Error:', error));
                        }
                    }
                    else if (replyBtn) {
                        e.preventDefault();
                        const commentContainer = replyBtn.closest('.comment-container');
                        const parentId = commentContainer.id.split('-')[1];
                        const authorName = commentContainer.querySelector('.font-semibold').textContent;
                        document.getElementById('comment_parent_id').value = parentId;
                        document.getElementById('replying-to-user').textContent = authorName;
                        document.getElementById('replying-to-indicator').style.display = 'block';
                        mainCommentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const mainCommentEditor = tinymce.get('comment_body_editor');
                        if (mainCommentEditor) mainCommentEditor.focus();
                    }
                    else if (reactionBadge) {
                        const commentId = reactionBadge.closest('.comment-reactions-container').dataset.commentId;
                        toggleCommentReaction(commentId, reactionBadge.dataset.emoji);
                    }
                    else if (addReactionBtn) {
                        e.stopPropagation();
                        const picker = addReactionBtn.parentElement.querySelector('emoji-picker');
                        if (picker) picker.style.display = picker.style.display === 'block' ? 'none' : 'block';
                    }
                });

                // コメントリアクションの絵文字ピッカー処理
                commentsContainer.addEventListener('emoji-click', e => {
                    if (e.target.matches('emoji-picker')) {
                        const commentId = e.target.closest('.comment-reactions-container').dataset.commentId;
                        toggleCommentReaction(commentId, e.detail.emoji.unicode);
                        e.target.style.display = 'none';
                    }
                });

                // コメント編集フォームの保存処理
                commentsContainer.addEventListener('submit', function (e) {
                    if (e.target.matches('.comment-edit-form')) {
                        e.preventDefault();
                        const form = e.target;
                        const commentContainer = form.closest('.comment-container');
                        const commentId = form.dataset.commentId;
                        const editorId = `comment-editor-${commentId}`;
                        const editorInstance = tinymce.get(editorId);
                        const newBody = editorInstance ? editorInstance.getContent() : form.querySelector('textarea').value;
                        const token = document.head.querySelector('meta[name="csrf-token"]').content;

                        fetch(`/community/comments/${commentId}`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify({ body: newBody })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const displayArea = commentContainer.querySelector('.comment-body-content');
                                    displayArea.innerHTML = data.formatted_body;
                                    if (editorInstance) { editorInstance.remove(); }
                                    commentContainer.querySelector('.comment-display-area').style.display = 'block';
                                    form.style.display = 'none';
                                } else {
                                    alert(data.message || 'コメントの更新に失敗しました。');
                                }
                            })
                            .catch(error => { console.error('Error:', error); alert('エラーが発生しました。'); });
                    }
                });
            }

            // 返信キャンセルの処理
            const cancelReplyBtn = document.getElementById('cancel-reply-btn');
            if (cancelReplyBtn) {
                cancelReplyBtn.addEventListener('click', function () {
                    document.getElementById('comment_parent_id').value = '';
                    document.getElementById('replying-to-indicator').style.display = 'none';
                });
            }

            // ピッカーの外側をクリックしたら隠す共通処理
            document.addEventListener('click', (e) => {
                document.querySelectorAll('emoji-picker').forEach(picker => {
                    if (picker.style.display === 'block' && !picker.parentElement.contains(e.target)) {
                        picker.style.display = 'none';
                    }
                });
            });
        });
    </script>
@endpush


@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="mention-suggestions-container"
            class="absolute z-50 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg overflow-hidden"
            style="display: none;">
            {{-- メンション候補はここにJavaScriptによって動的に挿入されます --}}
        </div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
            <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
            <a href="{{ route('community.posts.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">社内掲示板</a>
            <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
            <span class="text-gray-700 dark:text-gray-200 truncate">{{ Str::limit($post->title, 40) }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg ">
            {{-- 投稿ヘッダー --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row justify-between md:items-start gap-4">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $post->title }}
                        </h1>
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-2">
                            <span class="font-semibold">{{ $post->user->name ?? '不明なユーザー' }}</span>
                            <span class="mx-2">&middot;</span>
                            <span>{{ $post->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                    </div>
                    <div class="hidden md:flex flex-1 mx-4 space-x-4">
                        <div class="flex-1">
                            <div class="border-l border-gray-300 dark:border-gray-600 pl-4 h-full">
                                <h4
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    閲覧範囲</h4>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($post->role)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                                            <i
                                                class="fas fa-users mr-1.5"></i><strong>ロール:</strong>&nbsp;{{ $post->role->display_name ?? $post->role->name }}
                                        </span>
                                    @endif
                                    @if ($post->readableUsers->isNotEmpty())
                                        @foreach($post->readableUsers as $user)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                <i class="fas fa-user mr-1.5"></i>{{ $user->name }}
                                            </span>
                                        @endforeach
                                    @endif
                                    @if (!$post->role && $post->readableUsers->isEmpty())
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                            <i class="fas fa-globe-asia mr-1.5"></i>全公開
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($post->tags->isNotEmpty())
                            <div class="flex-1">
                                <div class="border-l border-gray-300 dark:border-gray-600 pl-4 h-full">
                                    <h4
                                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                        タグ</h4>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @foreach($post->tags as $tag)
                                            <a href="{{ route('community.posts.index', ['tag' => $tag->name]) }}"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800 transition">
                                                <i class="fas fa-tag mr-1.5"></i>{{ $tag->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="flex-shrink-0 flex items-center space-x-2 w-full md:w-auto">
                        @can('update', $post)
                            <x-secondary-button as="a" href="{{ route('community.posts.edit', $post) }}"
                                class="py-1 px-3 text-xs w-1/2 md:w-auto justify-center">
                                <i class="fas fa-edit mr-1"></i>編集
                            </x-secondary-button>
                        @endcan
                        @can('delete', $post)
                            <form action="{{ route('community.posts.destroy', $post) }}" method="POST"
                                class="inline-block w-1/2 md:w-auto"
                                onsubmit="return confirm('本当に投稿「{{ $post->title }}」を削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit" class="py-1 px-3 text-xs w-full justify-center">
                                    <i class="fas fa-trash mr-1"></i>削除
                                </x-danger-button>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="block md:hidden mt-3 border-t border-gray-200 dark:border-gray-600 pt-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">閲覧範囲
                    </h4>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($post->role)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                                <i
                                    class="fas fa-users mr-1.5"></i><strong>ロール:</strong>&nbsp;{{ $post->role->display_name ?? $post->role->name }}
                            </span>
                        @endif
                        @if ($post->readableUsers->isNotEmpty())
                            @foreach($post->readableUsers as $user)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    <i class="fas fa-user mr-1.5"></i>{{ $user->name }}
                                </span>
                            @endforeach
                        @endif
                        @if (!$post->role && $post->readableUsers->isEmpty())
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                <i class="fas fa-globe-asia mr-1.5"></i>全公開
                            </span>
                        @endif
                    </div>
                </div>
                @if($post->tags->isNotEmpty())
                    <div class="block md:hidden mt-3 border-t border-gray-200 dark:border-gray-600 pt-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">タグ</h4>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($post->tags as $tag)
                                <a href="{{ route('community.posts.index', ['tag' => $tag->name]) }}"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800 transition">
                                    <i class="fas fa-tag mr-1.5"></i>{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="p-6 sm:p-8">
                <div class="tinymce-content prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200">
                    {!! $post->formatted_body !!}
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    {{-- リアクション表示エリア (左側) --}}
                    <div id="reactions-container" data-post-id="{{ $post->id }}">
                        @include('community.posts.partials._reactions', ['reactions' => $post->reactions])
                    </div>

                    {{-- 閲覧済みユーザー表示エリア (右側) --}}
                    @if($readByUsers->isNotEmpty())
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-eye fa-fw mr-2"></i>
                            <span class="font-semibold cursor-default"
                                title="既読者: {{ $readByUsers->pluck('user.name')->join(', ') }}">
                                既読: {{ $readByUsers->count() }}人
                            </span>
                        </div>
                    @endif
                </div>
            </div>


        </div>
        <div class="mt-8 max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">コメント</h2>
            @auth
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 px-6 pt-4" id="replying-to-indicator"
                        style="display:none;">
                        <span id="replying-to-user"></span> さんへ返信中...
                        <button type="button" id="cancel-reply-btn" class="ml-2 text-blue-500 hover:underline">[キャンセル]</button>
                    </div>
                    <form action="{{ route('community.posts.comments.store', $post) }}" method="POST" class="p-6">
                        @csrf
                        <input type="hidden" name="parent_id" id="comment_parent_id" value="">
                        <div>
                            <x-input-label for="comment_body_editor" value="コメントを投稿する" class="sr-only" />
                            <textarea id="comment_body_editor" name="body" class="hidden">{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                        </div>
                        <div class="mt-4 flex justify-end">
                            <x-primary-button type="submit">
                                <i class="fas fa-paper-plane mr-2"></i>コメントする
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            @endauth
            <div class="space-y-4 comment-list-container">
                @forelse ($sortedComments as $comment)
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 comment-container"
                        id="comment-{{ $comment->id }}">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-sm text-blue-500 dark:text-blue-400 ">#{{ $comment->id }}</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100 ml-2">
                                    {{ $comment->user->name ?? '不明なユーザー' }}
                                </span>

                                <span
                                    class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button class="reply-to-comment-btn text-sm text-gray-500 hover:text-blue-500" title="返信する"><i
                                        class="fas fa-reply"></i></button>
                                @can('update', $comment)
                                    <button class="edit-comment-btn text-gray-400 hover:text-blue-500" title="編集"><i
                                            class="fas fa-pencil-alt"></i></button>
                                @endcan
                                @can('delete', $comment)
                                    <button class="delete-comment-btn text-gray-400 hover:text-red-500"
                                        data-comment-id="{{ $comment->id }}" title="削除"><i class="fas fa-trash-alt"></i></button>
                                @endcan
                            </div>
                        </div>

                        {{-- 返信先表示 --}}
                        @if($comment->parent_id)
                            <div class="text-sm text-blue-500 dark:text-blue-400 mb-2">
                                <a href="#comment-{{ $comment->parent_id }}" class="hover:underline">>> #{{ $comment->parent_id }}
                                    への返信</a>
                            </div>
                        @endif

                        {{-- コメント本文表示エリア --}}
                        <div class="comment-display-area">
                            <div
                                class="text-gray-700 dark:text-gray-300 leading-relaxed prose dark:prose-invert max-w-none comment-body-content">
                                {!! $comment->formatted_body !!}
                            </div>
                        </div>

                        {{-- 編集フォームエリア --}}
                        <form class="comment-edit-form mt-2" style="display: none;" data-comment-id="{{ $comment->id }}">
                            <textarea name="body" id="comment-editor-{{ $comment->id }}" class="w-full"
                                rows="3">{{ $comment->body }}</textarea>
                            <div class="flex justify-end space-x-2 mt-2">
                                <x-secondary-button type="button" class="cancel-edit-btn">キャンセル</x-secondary-button>
                                <x-primary-button type="submit">保存する</x-primary-button>
                            </div>
                        </form>

                        {{-- コメントへのリアクション表示エリア --}}
                        <div class="comment-reactions-container pt-2 mt-2 border-t border-gray-200 dark:border-gray-700"
                            data-comment-id="{{ $comment->id }}">
                            @include('community.posts.partials._comment_reactions', ['comment' => $comment])
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-5 text-center">
                        <p class="text-gray-500 dark:text-gray-400">まだコメントはありません。</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
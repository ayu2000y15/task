<div class="flex items-start @if($comment->parent_id) ml-8 md:ml-12 mt-4 @endif">
    <div class="flex-grow">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 comment-container"
            id="comment-{{ $comment->id }}">
            <div class="flex items-center justify-between">
                <div>
                    <span
                        class="font-semibold text-gray-900 dark:text-gray-100">{{ $comment->user->name ?? '不明なユーザー' }}</span>
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

            <div class="comment-display-area">
                <div
                    class="mt-2 text-gray-700 dark:text-gray-300 leading-relaxed prose dark:prose-invert max-w-none comment-body-content">
                    {!! $comment->formatted_body !!}
                </div>
            </div>

            <form class="comment-edit-form mt-2" style="display: none;" data-comment-id="{{ $comment->id }}">
                <textarea name="body" id="comment-editor-{{ $comment->id }}" class="w-full"
                    rows="3">{{ $comment->body }}</textarea>
                <div class="flex justify-end space-x-2 mt-2">
                    <x-secondary-button type="button" class="cancel-edit-btn">キャンセル</x-secondary-button>
                    <x-primary-button type="submit">保存する</x-primary-button>
                </div>
            </form>

            <div class="comment-reactions-container" data-comment-id="{{ $comment->id }}">
                @include('community.posts.partials._comment_reactions', ['comment' => $comment])
            </div>
        </div>
    </div>
</div>

{{-- このコメントへの返信を再帰的に表示 --}}
@if ($comment->replies->isNotEmpty())
    <div class="pl-4 border-l-2 border-gray-200 dark:border-gray-700">
        @foreach($comment->replies as $reply)
            @include('community.posts.partials._comment', ['comment' => $reply])
        @endforeach
    </div>
@endif
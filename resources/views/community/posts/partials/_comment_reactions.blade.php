<div class="flex flex-wrap items-center gap-2 mt-2">
    @php
        $groupedReactions = $comment->reactions->groupBy('emoji')->map(function ($items) {
            $userNames = $items->map(fn($i) => $i->user->name ?? '不明')->join(', ');
            return [
                'count' => $items->count(),
                'user_list_string' => $userNames . ' さんがいいねしました',
                'reacted_by_current_user' => $items->contains('user_id', auth()->id()),
            ];
        });
    @endphp

    @foreach($groupedReactions as $emoji => $details)
        <button
            class="comment-reaction-badge px-2 py-0.5 rounded-full flex items-center text-xs transition {{ $details['reacted_by_current_user'] ? 'bg-blue-200 dark:bg-blue-800 border border-blue-400' : 'bg-gray-200 dark:bg-gray-700 border border-transparent' }}"
            data-emoji="{{ $emoji }}" title="{{ $details['user_list_string'] }}">
            <span class="text-sm mr-1">{{ $emoji }}</span>
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $details['count'] }}</span>
        </button>
    @endforeach

    @if(!$comment->reactions->contains('user_id', auth()->id()))
        <div class="relative comment-add-reaction-container">
            {{-- ▼▼▼【変更】ボタンのアイコンを投稿欄と統一 ▼▼▼ --}}
            <button
                class="add-comment-reaction-btn px-2 py-1 rounded-full flex items-center space-x-1 text-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                title="リアクションを追加">
                <i class="fas fa-smile text-gray-500 dark:text-gray-400"></i>
                <i class="fas fa-plus text-xs text-gray-400 dark:text-gray-300"></i>
            </button>
            <emoji-picker class="absolute top-full mb-2 z-50" style="display: none;"></emoji-picker>
        </div>
    @endif
</div>
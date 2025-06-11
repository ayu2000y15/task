<div class="flex flex-wrap items-center gap-2">
    @php
        $groupedReactions = $reactions->groupBy('emoji')->map(function ($items) {
            $userNames = $items->map(fn($i) => $i->user->name ?? '不明')->join(', ');
            $userListString = $userNames . ' さんがいいねしました';
            return [
                'count' => $items->count(),
                'user_list_string' => $userListString,
                'reacted_by_current_user' => $items->contains('user_id', auth()->id()),
            ];
        });
    @endphp

    @foreach($groupedReactions as $emoji => $details)
        <button class="reaction-badge px-2 py-1 rounded-full flex items-center text-sm transition
                                                            {{ $details['reacted_by_current_user'] ? 'bg-blue-200 dark:bg-blue-800 border border-blue-400' : 'bg-gray-200 dark:bg-gray-700 border border-transparent' }}
                                                            hover:bg-gray-300 dark:hover:bg-gray-600"
            data-emoji="{{ $emoji }}" title="{{ $details['user_list_string'] }}">
            <span class="text-base mr-1">{{ $emoji }}</span>
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $details['count'] }}</span>
        </button>
    @endforeach

    {{-- ▼▼▼【ここから変更】ログイン中のユーザーがまだリアクションしていない場合のみ、追加ボタンを表示する ▼▼▼ --}}
    @if(!$reactions->contains('user_id', auth()->id()))
        <div class="relative" id="add-reaction-container">
            <button id="add-reaction-btn"
                class="reaction-badge-add px-2 py-1 rounded-full flex items-center space-x-1 text-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                title="リアクションを追加">
                <i class="fas fa-smile text-gray-500 dark:text-gray-400"></i>
                <i class="fas fa-plus text-xs text-gray-400 dark:text-gray-300"></i>
            </button>
            <emoji-picker class="absolute top-full mt-2 z-50" style="display: none;"></emoji-picker>
        </div>
    @endif
    {{-- ▲▲▲【ここまで】▲▲▲ --}}
</div>
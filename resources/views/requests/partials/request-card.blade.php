<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
    {{-- ヘッダー --}}
    <div class="p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        {{-- タイトルとボタンの行 --}}
        <div class="flex justify-between items-start mb-2">
            <h3 class="font-semibold text-gray-800 dark:text-gray-200 truncate pr-4">{{ $request->title }}</h3>
            {{-- 編集・削除ボタンのコンテナ --}}
            <div class="flex-shrink-0 flex items-center space-x-3">
                @can('update', $request)
                    <a href="{{ route('requests.edit', $request) }}"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="編集">
                        <i class="fas fa-edit"></i>
                    </a>
                @endcan
                @can('delete', $request)
                    <form action="{{ route('requests.destroy', $request) }}" method="POST"
                        onsubmit="return confirm('この依頼を本当に削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="削除">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- ▼▼▼【修正】依頼者・担当者などの情報行をグリッドレイアウトに変更 ▼▼▼ --}}
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center" title="依頼者">
                <i class="fas fa-user-edit w-4 text-center mr-1.5"></i>
                <span>{{ $request->requester->name }}</span>
            </div>
            <div class="flex items-center" title="担当者">
                <i class="fas fa-users w-4 text-center mr-1.5"></i>
                <span>{{ $request->assignees->pluck('name')->join(', ') }}</span>
            </div>
            @if($request->project)
                <div class="flex items-center" title="関連案件">
                    <i class="fas fa-folder-open w-4 text-center mr-1.5"></i>
                    <a href="{{ route('projects.show', $request->project) }}"
                        class="hover:underline truncate">{{ $request->project->title }}</a>
                </div>
            @endif
            @if($request->category)
                <div class="flex items-center" title="カテゴリ">
                    <i class="fas fa-tag w-4 text-center mr-1.5"></i>
                    <span>{{ $request->category->name }}</span>
                </div>
            @endif
            <div class="flex items-center" title="依頼日">
                <i class="fas fa-calendar-alt w-4 text-center mr-1.5"></i>
                <span>{{ $request->created_at->format('Y/m/d') }}</span>
            </div>
        </div>
        @if($request->notes)
            <p class="mt-2 pt-2 text-sm text-gray-600 dark:text-gray-300 border-t border-gray-200 dark:border-gray-600">
                {!! nl2br(e($request->notes)) !!}
            </p>
        @endif
    </div>

    {{-- チェックリスト --}}
    <div class="p-4">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700 sortable-list"
            id="request-item-list-{{ $request->id }}">
            @foreach($request->items as $item)
                {{-- ▼▼▼【修正】リスト項目をflex-col（縦積み）にし、mdスクリーン以上でflex-row（横並び）に変更 ▼▼▼ --}}
                <li class="py-3 flex flex-col md:flex-row md:items-center justify-between gap-x-4 gap-y-3 group"
                    data-id="{{ $item->id }}">
                    {{-- 左側：ハンドル、チェックボックス、タスク名 --}}
                    <div class="flex-grow flex items-center gap-x-3 min-w-0">
                        <div class="flex-shrink-0 cursor-move drag-handle text-gray-400 hover:text-gray-600" title="並び替え">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="flex-shrink-0 w-4 h-4 flex items-center justify-center">
                            @if($request->assignees->contains(Auth::user()))
                                <input type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 request-item-checkbox"
                                    data-item-id="{{ $item->id }}" {{ $item->is_completed ? 'checked' : '' }}>
                            @else
                                @if($item->is_completed)
                                    <i class="fas fa-check-circle text-green-500" title="完了済み"></i>
                                @else
                                    <i class="far fa-circle text-gray-400" title="未完了"></i>
                                @endif
                            @endif
                        </div>
                        <div class="min-w-0">
                            <label
                                class="text-sm text-gray-700 dark:text-gray-200 {{ $item->is_completed ? 'line-through text-gray-500' : '' }}">
                                {{ $item->content }}
                            </label>
                            <span id="status-{{$item->id}}"
                                class="ml-2 text-xs text-gray-400 dark:text-gray-500 {{ !$item->is_completed ? 'hidden' : '' }}">
                                @if($item->is_completed && $item->completedBy)
                                    - {{ $item->completedBy->name }}が完了 ({{ $item->completed_at->format('n/j H:i') }})
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- ▼▼▼【修正】右側：日付ピッカーエリアのレイアウトをレスポンシブ化 ▼▼▼ --}}
                    <div
                        class="w-full md:w-auto flex-shrink-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-x-4 gap-y-2 pl-8 md:pl-0">
                        <div class="flex-grow flex items-center gap-x-2" title="開始日時">
                            <i class="fas fa-play-circle w-4 text-center text-gray-400"></i>
                            {{-- 固定幅(w-44)を削除し、親要素の幅に追従するように変更 --}}
                            <input type="datetime-local"
                                class="start-at-input w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                value="{{ optional($item->start_at)->format('Y-m-d\TH:i') }}"
                                data-item-id="{{ $item->id }}">
                        </div>
                        <div class="flex-grow flex items-center gap-x-2" title="終了日時">
                            <i class="fas fa-flag-checkered w-4 text-center text-gray-400"></i>
                            {{-- 固定幅(w-44)を削除し、親要素の幅に追従するように変更 --}}
                            <input type="datetime-local"
                                class="end-at-input w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                value="{{ optional($item->end_at)->format('Y-m-d\TH:i') }}" data-item-id="{{ $item->id }}">
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if($request->items->isNotEmpty())
            <div class="flex justify-start pt-4">
                <button type="button"
                    class="save-request-item-order-btn inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150"
                    data-target-list="#request-item-list-{{ $request->id }}"
                    data-url="{{ route('requests.items.updateOrder', $request) }}">
                    <i class="fas fa-save mr-2"></i>並び順を保存
                </button>
                <div class="ml-4 text-sm flex items-center"></div>
            </div>
        @endif
    </div>
</div>
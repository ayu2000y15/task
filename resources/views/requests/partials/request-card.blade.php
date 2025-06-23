<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
    {{-- ▼▼▼【ここから修正】ヘッダーのデザインを改善 ▼▼▼ --}}
    <div class="p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        {{-- タイトルとボタンの行 --}}
        <div class="flex justify-between items-center mb-2">
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
        {{-- 依頼者・担当者などの情報行 --}}
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
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
                    {{-- 案件詳細ページへのリンク（routeが 'projects.show' の場合） --}}
                    <a href="{{ route('projects.show', $request->project) }}"
                        class="hover:underline">{{ $request->project->title }}</a>
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
    {{-- ▲▲▲【修正ここまで】▲▲▲ --}}

    <ul class="divide-y divide-gray-200 dark:divide-gray-700 p-4">
        @foreach($request->items as $item)
            <li class="py-2 flex items-center justify-between gap-x-4 group">

                {{-- 左側：チェックボックスとタスク名 --}}
                <div class="flex-grow flex items-center gap-x-3 min-w-0">
                    <input type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 request-item-checkbox flex-shrink-0"
                        data-item-id="{{ $item->id }}" {{ $item->is_completed ? 'checked' : '' }}>

                    <div class="min-w-0">
                        <label for="item-{{$item->id}}"
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

                {{-- 右側：日付ピッカー --}}
                <div class="flex-shrink-0 flex items-center gap-x-4">
                    <div class="flex items-center gap-x-1" title="このタスクを計画する日付を設定します">
                        <i class="far fa-calendar-check w-4 text-center text-gray-400"></i> <input type="datetime-local"
                            class="due-date-input w-44 border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            value="{{ optional($item->my_day_date)->format('Y-m-d\TH:i') }}" data-item-id="{{ $item->id }}">
                    </div>
                    <div class="flex items-center gap-x-1" title="このタスクの終了予定日時を設定します">
                        <i class="far fa-calendar-times w-4 text-center text-gray-400"></i>
                        <input type="datetime-local"
                            class="due-date-input w-44 border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            value="{{ optional($item->due_date)->format('Y-m-d\TH:i') }}" data-item-id="{{ $item->id }}">
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</div>
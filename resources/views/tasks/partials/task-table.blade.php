{{-- resources/views/tasks/partials/task-table.blade.php --}}
<div id="assignee-data-container" data-assignee-options='{{ json_encode($assigneeOptions ?? []) }}'>
    {{-- ★ Bladeファイル全体を div で囲む（IDは後でJavaScriptから利用） --}}
    <div id="task-table-body">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="{{ $tableId ?? 'default-task-table-fallback' }}">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    {{-- 時間記録 (ソート対象外) --}}
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">
                        時間記録
                    </th>

                    {{-- ▼▼▼【ここから変更】▼▼▼ --}}
                    {{-- 工程名 --}}
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[250px] sm:min-w-[300px]">
                        @php
                            // 現在のフィルター条件を維持しつつ、ソート条件を切り替えるリンクを生成
                            $linkParamsName = array_merge(request()->query(), ['sort_by' => 'name', 'sort_order' => ($sortBy == 'name' && $sortOrder == 'asc') ? 'desc' : 'asc']);
                        @endphp
                        <a href="{{ route('tasks.index', $linkParamsName) }}" class="sortable-link">
                            @if(Request::is('projects/*'))
                                工程名
                            @else
                                案件 / 工程名
                            @endif
                            {{-- ソートアイコンの表示 --}}
                            @if ($sortBy == 'name')
                                <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @else
                                <i class="fas fa-sort text-gray-400 ml-1"></i>
                            @endif
                        </a>
                    </th>

                    {{-- キャラクター --}}
                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !(isset($character) && $character))
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            @php
                                $linkParamsCharacter = array_merge(request()->query(), ['sort_by' => 'character_name', 'sort_order' => ($sortBy == 'character_name' && $sortOrder == 'asc') ? 'desc' : 'asc']);
                            @endphp
                            <a href="{{ route('tasks.index', $linkParamsCharacter) }}" class="sortable-link">
                                キャラクター
                                @if ($sortBy == 'character_name')
                                    <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400 ml-1"></i>
                                @endif
                            </a>
                        </th>
                    @endif

                    {{-- 担当者 (ソート対象外のまま) --}}
                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="min-width:120px;">担当者</th>
                    @endif

                    {{-- 開始日時 --}}
                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                           @php
                                $linkParamsStart = array_merge(request()->query(), ['sort_by' => 'start_date', 'sort_order' => ($sortBy == 'start_date' && $sortOrder == 'asc') ? 'desc' : 'asc']);
                           @endphp
                            <a href="{{ route('tasks.index', $linkParamsStart) }}" class="sortable-link">
                                開始日時
                                @if ($sortBy == 'start_date')
                                    <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400 ml-1"></i>
                                @endif
                            </a>
                        </th>
                    @endif

                    @if($isMilestoneView ?? false)
                         <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
                    @endif

                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">工数</th>
                    @endif

                     @if($isFolderView ?? false)
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">親工程</th>
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ファイル数</th>
                    @endif

                    {{-- ステータス --}}
                     {{-- @if(!($isFolderView ?? false))
                     <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-36 min-w-[140px]">
                        @php
                             $linkParamsStatus = array_merge(request()->query(), ['sort_by' => 'status', 'sort_order' => ($sortBy == 'status' && $sortOrder == 'asc') ? 'desc' : 'asc']);
                        @endphp
                         <a href="{{ route('tasks.index', $linkParamsStatus) }}" class="sortable-link">
                             ステータス
                             @if ($sortBy == 'status')
                                 <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ml-1"></i>
                             @else
                                 <i class="fas fa-sort text-gray-400 ml-1"></i>
                             @endif
                         </a>
                     </th>
                     @endif --}}
                    {{-- ▲▲▲【ここまで変更】▲▲▲ --}}

                    {{-- 操作 (ソート対象外) --}}
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($tasksToList as $task)
                    @php
                        $rowClass = '';
                        $now = \Carbon\Carbon::now();
                        $in24Hours = \Carbon\Carbon::now()->addHours(24);
                        $isCompleted = in_array($task->status, ['completed', 'cancelled']);

                        // Define conditions for styling
                        $isPast = $task->end_date && $task->end_date->isPast();
                        $isDueSoon = $task->end_date && $task->end_date->isBetween($now, $in24Hours);

                        // Determine the row class
                        if (!($isFolderView ?? false) && $isPast && !$isCompleted) {
                            $rowClass = 'bg-red-50 dark:bg-red-900/50';
                        } elseif (!($isFolderView ?? false) && !($isMilestoneView ?? false) && $isDueSoon && !$isCompleted) {
                            $rowClass = 'bg-yellow-50 dark:bg-yellow-700/50';
                        }

                        $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
                        $level = $task->level ?? 0;
                        $hasChildren = $task->children->isNotEmpty();
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $rowClass }} {{ $hoverClass }} @if($task->parent_id) child-row child-of-{{ $task->parent_id }} @endif"
                        data-task-id="{{ $task->id }}"
                        data-project-id="{{ $task->project_id }}"
                        @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
                        data-progress="{{ $task->progress ?? 0 }}">

                        @if(($isFolderView ?? false) && $task->is_folder)
                            @can('fileView', $task)
                            {{-- フォルダ表示 --}}
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                                <div class="flex items-start" style="padding-left: {{ $level * 1.5 }}rem;">
                                     <div class="task-toggle-container mr-1" style="width: 1.2em; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 0.125rem;">
                                        @if($hasChildren)
                                            <a href="javascript:void(0);" class="task-toggle-trigger text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-task-id="{{ $task->id }}" aria-expanded="true" title="子要素を展開/折りたたむ">
                                                <i class="fas fa-chevron-down toggle-icon fa-fw"></i>
                                            </a>
                                        @endif
                                    </div>
                                    <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                                        <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 whitespace-normal break-words inline-block">
                                            {{ $task->name }}
                                        </a>
                                        @if (!empty($task->description))
                                            <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                                        @endif
                                        @if($task->files->count() > 0)
                                            @can('fileView', $task)
                                                <button type="button" class="ml-2 text-xs text-blue-500 hover:underline toggle-folder-files"
                                                        data-target="folder-files-{{ $tableId ?? 'default' }}-{{ $task->id }}">
                                                    ファイル表示 ({{ $task->files->count() }}) <i class="fas fa-chevron-down fa-xs"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->parent->name ?? '-' }}</td>
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top"><i class="fas fa-file mr-1"></i> {{ $task->files->count() }}</td>

                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium align-top">
                                <div class="flex items-center space-x-1">
                                    @can('update', $task)
                                    <x-icon-button
                                        :href="route('projects.tasks.edit', [$task->project, $task])"
                                        icon="fas fa-edit"
                                        title="編集"
                                        color="blue" />
                                    @endcan
                                    @can('delete', $task)
                                        @if($task->files->isEmpty())
                                        <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="inline-block" onsubmit="return confirm('本当に削除しますか？フォルダ内のすべての工程も削除されます。');"> @csrf @method('DELETE')
                                            <x-icon-button
                                                icon="fas fa-trash"
                                                title="削除"
                                                color="red"
                                                type="submit" />
                                        </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                            @endcan
                        @elseif(!($isFolderView ?? false)) {{-- 通常の工程または重要納期の場合 --}}
                            <td class="px-4 py-3 align-top">
                                @if(!$task->is_folder && !$task->is_milestone)
                                    {{-- 【追加】担当者がいる場合のみ、以下のロジックを実行 --}}
                                    @if($task->assignees->isNotEmpty())
                                        @php
                                            // ログイン中のユーザーがこのタスクの担当者かチェック
                                            $isAssigned = $task->assignees->contains('id', Auth::id());
                                            // ログイン中のユーザーが共有アカウントかチェック
                                            $isSharedAccount = Auth::check() && Auth::user()->status === \App\Models\User::STATUS_SHARED;
                                        @endphp
                                        @if($isAssigned || $isSharedAccount)
                                            {{-- 操作可能なタイマーコントロール --}}
                                            <div class="timer-controls"
                                            data-task-id="{{ $task->id }}"
                                            data-task-status="{{ $task->status }}"
                                            data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}"
                                            data-assignees='{{ json_encode($task->assignees->map->only(['id', 'name'])->values()) }}'>
                                                {{-- JavaScriptがこの中身を生成します --}}
                                            </div>
                                        @else
                                            {{-- 表示専用のタイマー状況コンテナ --}}
                                            <div class="timer-display-only"
                                                data-task-id="{{ $task->id }}"
                                                data-task-status="{{ $task->status }}"
                                                data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}">
                                                {{-- JavaScriptがこの中身を生成します --}}
                                            </div>
                                        @endif
                                    @else
                                        {{-- 【追加】担当者が一人もいない場合 --}}
                                        <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                @else
                                    {{-- フォルダやマイルストーンにはタイマー不要 --}}
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                                <div class="flex items-center gap-x-3">
                                    {{-- @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center self-start mt-0.5">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem; min-width: 30px; text-align: center;">進行中</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-in-progress form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                                   data-action="set-in-progress"
                                                   title="進行中にする"
                                                   @if($task->status == 'in_progress') checked @endif>
                                        </div>
                                    @endif --}}

                                    <div class="flex items-start flex-grow min-w-0" style="padding-left: {{ $level * 1.5 }}rem;">
                                        <div class="task-toggle-container mr-1" style="width: 1.2em; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 0.125rem;">
                                             @if($hasChildren)
                                                <a href="javascript:void(0);" class="task-toggle-trigger text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-task-id="{{ $task->id }}" aria-expanded="true" title="子工程を展開/折りたたむ">
                                                    <i class="fas fa-chevron-down toggle-icon fa-fw"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                                            @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                            @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                            @else
                                                @switch($task->status)
                                                    @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                                    @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                                    @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="一時停止中"></i> @break
                                                    @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                                    @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                                                @endswitch
                                            @endif
                                        </span>
                                        <div class="min-w-0">
                                            @if(!Request::is('projects/*'))
                                            <a href="{{ route('projects.show', $task->project) }}" class="text-xs font-semibold truncate hover:underline block" style="color: {{ $task->project->color ?? '#6c757d' }};" title="案件: {{ $task->project->title }}">
                                                {{ $task->project->title }}
                                            </a>
                                            @endif

                                            <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 whitespace-normal break-words inline-block font-medium text-lg">
                                                {{ $task->name }}
                                                @if(!($isMilestoneView ?? false) && !($isFolderView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
                                            </a>
                                            @cannot('fileView', $task)
                                                @if(!($isMilestoneView ?? false) && !($isFolderView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
                                            @endcannot
                                            @if (!($isFolderView ?? false) && !($isMilestoneView ?? false) && $task->parent && !$task->parent->is_folder)
                                                <span class="text-xs text-gray-500 dark:text-gray-400 block mt-0.5" title="Parent Task: {{ $task->parent->name }}">
                                                    <i class="fas fa-level-up-alt fa-rotate-90 fa-xs mr-1 text-gray-400 dark:text-gray-500"></i>{{ \Illuminate\Support\Str::limit($task->parent->name, 30) }}
                                                </span>
                                            @endif
                                            @if (!empty($task->description))
                                                <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                                            @endif

                                            {{-- ▼▼▼【変更】sm以下の画面サイズでキャラクター名を表示 ▼▼▼ --}}
                                            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !(isset($character) && $character))
                                                <p class="sm:hidden text-xs text-gray-500 dark:text-gray-400 truncate mt-1" title="キャラクター: {{ $task->character->name ?? '未設定' }}">
                                                    <i class="fas fa-dragon fa-fw mr-1 text-gray-400"></i> {{ $task->character->name ?? 'キャラクター未設定' }}
                                                </p>
                                            @endif
                                            {{-- ▲▲▲【変更】ここまで ▲▲▲ --}}

                                            @if($task->end_date && !$task->is_milestone)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <i class="far fa-clock mr-1"></i>
                                                <span
                                                    @if($isPast && !$isCompleted)
                                                        class="text-red-500 font-semibold" title="期限切れ"
                                                    @elseif($isDueSoon && !$isCompleted)
                                                        class="text-yellow-500 font-semibold" title="期限1日前"
                                                    @endif
                                                >
                                                    {{ $task->end_date->format('n/j H:i') }}
                                                </span>
                                                <span class="text-gray-400 dark:text-gray-500">
                                                    ({{ $task->end_date->diffForHumans() }})
                                                </span>
                                            </p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center self-start mt-0.5">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">完了</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-completed form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600"
                                                   data-action="set-completed"
                                                   title="完了にする"
                                                   @if($task->status == 'completed') checked @endif>
                                        </div>
                                    @endif --}}
                                </div>
                            </td>
                            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !(isset($character) && $character))
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->character->name ?? '-' }}</td>
                            @endif

                            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 align-top editable-cell-assignees cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/50"
                                data-current-assignees='@json($task->assignees->pluck("id"))'
                                title="クリックして担当者を編集">
                                <div class="assignee-badge-container flex flex-wrap gap-1">
                                    @include('tasks.partials.assignee-badges', ['assignees' => $task->assignees])
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j H:i') }}</td>
                            @endif

                            @if($isMilestoneView ?? false)
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j') }}</td>
                            @endif

                            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                                {{ $task->formatted_duration ?? '-' }}
                            </td>
                            @endif

                            {{-- @if(!($isFolderView ?? false))
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                                @if(!$task->is_milestone && !$task->is_folder)
                                <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}">
                                    <option value="not_started" @if($task->status == 'not_started') selected @endif>未着手</option>
                                    <option value="in_progress" @if($task->status == 'in_progress') selected @endif>進行中</option>
                                    <option value="completed" @if($task->status == 'completed') selected @endif>完了</option>
                                    <option value="on_hold" @if($task->status == 'on_hold') selected @endif>一時停止中</option>
                                    <option value="cancelled" @if($task->status == 'cancelled') selected @endif>キャンセル</option>
                                </select>
                                @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            @endif --}}

                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium align-top">
                                <div class="flex items-center space-x-1">
                                    @can('update', $task)
                                    <x-icon-button
                                        :href="route('projects.tasks.edit', [$task->project, $task])"
                                        icon="fas fa-edit"
                                        title="編集"
                                        color="blue" />
                                    @endcan
                                    @can('delete', $task)
                                        <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="inline-block" onsubmit="return confirm('本当に削除しますか？');"> @csrf @method('DELETE')
                                            <x-icon-button
                                                icon="fas fa-trash"
                                                title="削除"
                                                color="red"
                                                type="submit" />
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        @endif
                    </tr>
                    @if(($isFolderView ?? false) && $task->is_folder && $task->files->count() > 0)
                         @can('fileView', $task)
                            <tr id="folder-files-{{ $tableId ?? 'default' }}-{{ $task->id }}" class="hidden">
                                <td colspan="5" class="p-0">
                                    <div class="pl-[calc(theme(spacing.4)_+_theme(spacing.12))] pr-4 py-3 bg-gray-50 dark:bg-gray-700/50">
                                        <h6 class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2"><i class="fas fa-paperclip mr-1"></i> 添付ファイル</h6>
                                        @include('tasks.partials.file-list-tailwind', ['files' => $task->files, 'project' => $task->project, 'task' => $task])
                                    </div>
                                </td>
                            </tr>
                        @endcan
                    @endif
                @empty
                    @php
                         $colspan = 7;
                         if ($isFolderView ?? false) {
                             $colspan = 5;
                         } elseif ($isMilestoneView ?? false) {
                             $colspan = 6;
                         }
                         if(isset($character) && $character) $colspan--;
                    @endphp
                    <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">表示する{{ ($isFolderView ?? false) ? 'フォルダ' : (($isMilestoneView ?? false) ? '重要納期' : '工程') }}がありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{-- スクリプトは変更の必要なし --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleTriggers = document.querySelectorAll('.task-toggle-trigger');

    toggleTriggers.forEach(trigger => {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            const taskId = this.dataset.taskId;
            const icon = this.querySelector('.toggle-icon');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
                this.setAttribute('aria-expanded', 'false');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
                this.setAttribute('aria-expanded', 'true');
            }
            toggleChildRows(taskId, !isExpanded);
        });
    });

    function toggleChildRows(parentId, show) {
        const childRows = document.querySelectorAll('tr.child-row.child-of-' + parentId);
        childRows.forEach(row => {
            row.style.display = show ? '' : 'none';

            const currentTaskId = row.dataset.taskId;
            const nestedToggleTrigger = document.querySelector('.task-toggle-trigger[data-task-id="' + currentTaskId + '"]');

            if (!show) {
                if (nestedToggleTrigger && nestedToggleTrigger.getAttribute('aria-expanded') === 'true') {
                    const nestedIcon = nestedToggleTrigger.querySelector('.toggle-icon');
                    if (nestedIcon) {
                        nestedIcon.classList.remove('fa-chevron-down');
                        nestedIcon.classList.add('fa-chevron-right');
                    }
                    nestedToggleTrigger.setAttribute('aria-expanded', 'false');
                    toggleChildRows(currentTaskId, false);
                }
            } else {
                if (nestedToggleTrigger && nestedToggleTrigger.getAttribute('aria-expanded') === 'true') {
                    toggleChildRows(currentTaskId, true);
                }
            }
        });
    }
});
</script>

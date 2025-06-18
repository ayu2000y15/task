{{-- resources/views/projects/partials/projects-task-table.blade.php --}}

<div id="task-list-container-{{ $tableId }}">
    <div class="mb-4">
        @php
            $hideCompletedParams = request()->query();

            if (isset($isProjectTaskView) && $isProjectTaskView) {
                $hideCompletedParams['context'] = 'project';
            } elseif (isset($character)) {
                $hideCompletedParams['context'] = 'character';
                $hideCompletedParams['character_id'] = $character->id;
            }

            $isHidingCompleted = $hideCompleted ?? false;

            $baseClass = 'inline-flex items-center px-4 py-2 mx-2 my-2 border rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150';
            $activeClass = 'bg-blue-600 border-transparent text-white hover:bg-blue-700 focus:ring-blue-500';
            $inactiveClass = 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-indigo-500';

            if ($isHidingCompleted) {
                unset($hideCompletedParams['hide_completed']);
                $buttonText = '完了を表示';
                $buttonIcon = 'fa-eye';
                $buttonClass = $activeClass;
            } else {
                $hideCompletedParams['hide_completed'] = 1;
                $buttonText = '完了を非表示';
                $buttonIcon = 'fa-eye-slash';
                $buttonClass = $inactiveClass;
            }
        @endphp
        <a href="{{ request()->url() }}?{{ http_build_query($hideCompletedParams) }}" class="{{ $baseClass }} {{ $buttonClass }}" id="toggle-completed-tasks-btn-{{ $tableId }}" data-container-id="task-list-container-{{ $tableId }}">
            <i class="fas {{ $buttonIcon }} mr-2"></i>{{ $buttonText }}
        </a>
        <div
            class="p-2 mx-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
            <i class="fas fa-info-circle mr-1"></i>
            工数の1日は8時間として計算しています。
        </div>
    </div>

    <div id="assignee-data-container-{{ $tableId }}" data-assignee-options='{{ json_encode($assigneeOptions ?? []) }}'>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="{{ $tableId }}">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    {{-- ▼▼▼【追加】時間記録用の列ヘッダー ▼▼▼ --}}
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">
                        時間記録
                    </th>
                    {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[250px] sm:min-w-[300px]">
                        工程名
                    </th>
                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="min-width:120px;">担当者</th>
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">開始日時</th>
                    @endif
                    @if($isMilestoneView ?? false)
                         <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
                    @endif
                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">工数</th>
                    @endif
                     @if($isFolderView ?? false)
                        {{-- ▼▼▼【変更】時間記録列の追加に伴い、フォルダ表示の空セルを1つ削除 ▼▼▼ --}}
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">親工程</th>
                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ファイル数</th>
                    @endif
                    @if(!($isFolderView ?? false))
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-36 min-w-[140px]">ステータス</th>
                    @endif
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

                        $isPast = $task->end_date && $task->end_date->isPast();
                        $isDueSoon = $task->end_date && $task->end_date->isBetween($now, $in24Hours);

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
                            {{-- ▼▼▼【追加】フォルダ表示用の空セル ▼▼▼ --}}
                            <td class="px-4 py-3 align-top"></td>
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
                            {{-- ▼▼▼【追加】時間記録用のセルとボタン表示ロジック ▼▼▼ --}}
                            <td class="px-4 py-3 align-top">
                                @if(!$task->is_folder && !$task->is_milestone)
                                    @if($task->assignees->isNotEmpty())
                                        @php
                                            $isAssigned = $task->assignees->contains('id', Auth::id());
                                            $isSharedAccount = Auth::check() && Auth::user()->status === \App\Models\User::STATUS_SHARED;
                                        @endphp
                                        {{-- ▼▼▼【ここから変更】▼▼▼ --}}
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
                                        {{-- ▲▲▲【変更ここまで】▲▲▲ --}}
                                    @else
                                        {{-- 担当者が一人もいない場合 --}}
                                        <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                @else
                                    {{-- フォルダやマイルストーンにはタイマー不要 --}}
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}
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
                                            <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 whitespace-normal break-words inline-block font-medium text-lg">
                                                {{ $task->name }}
                                                @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
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
                            @if(!($isFolderView ?? false))
                            <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                                @if(!$task->is_milestone && !$task->is_folder)
                                <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}">
                                    <option value="not_started" @if($task->status == 'not_started') selected @endif>未着手</option>
                                    <option value="in_progress" @if($task->status == 'in_progress') selected @endif>進行中</option>
                                    <option value="completed" @if($task->status == 'completed') selected @endif>完了</option>
                                    <option value="on_hold" @if($task->status == 'on_hold') selected @endif>一時停止中</option>
                                    <option value="cancelled" @if($task->status == 'cancelled') selected @endif>キャンセル</option>
                                </select>
                                @endif
                            </td>
                            @endif
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
                         // ▼▼▼【変更】colspanを7に更新 ▼▼▼
                         $colspan = 7;
                         if ($isFolderView ?? false) {
                             $colspan = 5;
                         } elseif ($isMilestoneView ?? false) {
                             $colspan = 6;
                         }
                    @endphp
                    <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">表示する工程がありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

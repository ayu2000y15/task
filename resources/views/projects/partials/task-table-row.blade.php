{{-- resources/views/projects/partials/task-table-row.blade.php --}}
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

<tr id="task-row-{{ $task->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $rowClass }} {{ $hoverClass }} @if($task->parent_id) child-row child-of-{{ $task->parent_id }} @endif"
    data-task-id="{{ $task->id }}"
    data-project-id="{{ $task->project_id }}"
    @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
    data-progress="{{ $task->progress ?? 0 }}">

    {{-- フォルダ以外の表示 (通常の工程または予定) --}}
    <td class="px-4 py-3 align-top">
        @if(!$task->is_milestone)
            @if($task->assignees->isNotEmpty())
                @php
                    $isAssigned = $task->assignees->contains('id', Auth::id());
                    $isSharedAccount = Auth::check() && Auth::user()->status === \App\Models\User::STATUS_SHARED;
                @endphp
                @if($isAssigned || $isSharedAccount)
                    <div class="timer-controls"
                    data-task-id="{{ $task->id }}"
                    data-task-status="{{ $task->status }}"
                    data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}"
                    data-assignees='{{ json_encode($task->assignees->map->only(['id', 'name'])->values()) }}'>
                        {{-- JavaScriptがこの中身を生成します --}}
                    </div>
                @else
                    <div class="timer-display-only"
                        data-task-id="{{ $task->id }}"
                        data-task-status="{{ $task->status }}"
                        data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}">
                        {{-- JavaScriptがこの中身を生成します --}}
                    </div>
                @endif
            @else
                <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
            @endif
        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
        @endif
    </td>
    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
        <div class="flex items-center gap-x-3">
            <div class="flex items-start flex-grow min-w-0" style="padding-left: {{ $level * 1.5 }}rem;">
                <div class="task-toggle-container mr-1" style="width: 1.2em; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 0.125rem;">
                        @if($hasChildren)
                        <a href="javascript:void(0);" class="task-toggle-trigger text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-task-id="{{ $task->id }}" aria-expanded="true" title="子工程を展開/折りたたむ">
                            <i class="fas fa-chevron-down toggle-icon fa-fw"></i>
                        </a>
                    @endif
                </div>
                <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                    @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="予定"></i>
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
                    </a>
                    @if ($task->parent && !$task->parent->is_folder)
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
</tr>
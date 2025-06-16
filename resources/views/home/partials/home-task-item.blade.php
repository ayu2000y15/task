<li class="flex items-start group">
    <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0 w-5 text-center">
        @switch($task->status)
            @case('completed')
            <i class="fas fa-check-circle text-green-500" title="完了"></i>
                @break
            @case('in_progress')
                <i class="fas fa-play-circle text-blue-500" title="進行中"></i>
                @break
            @case('on_hold')
                <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i>
                @break
            @default
                <i class="far fa-circle text-gray-400" title="未着手"></i>
        @endswitch
    </span>
    <div class="min-w-0">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            <a href="{{ route('projects.show', $task->project) }}" class="font-semibold hover:underline"
                style="color: {{ $task->project->color ?? '#6c757d' }};">{{ $task->project->title }}</a>

            <p>
                <i class="fas fa-dragon fa-fw mr-0.5 text-gray-400"></i>
                {{ optional($task->character)->name ?? 'キャラクター未設定' }}
            </p>
            <p>
                <i class="far fa-clock fa-fw mr-0.5 text-gray-400"></i>
                {{ optional($task->end_date)->format('n/j H:i') ?? '期限なし' }}
            </p>
        </div>
        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
            class="text-base font-medium text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">
            {{ $task->name }}
        </a>

    </div>
</li>
<li class="flex items-start group">
    <span class="task-status-icon-wrapper mr-2 mt-2 flex-shrink-0 w-5 text-center">
        @switch($task->status)
            @case('completed')
            <i class="fas fa-check-circle text-green-500" title="完了"></i>
                @break
            @case('in_progress')
                <i class="fas fa-play-circle text-blue-500" title="進行中"></i>
                @break
            @case('on_hold')
                <i class="fas fa-pause-circle text-yellow-500" title="一時停止中"></i>
                @break
            @default
                <i class="far fa-circle text-gray-400" title="未着手"></i>
        @endswitch
    </span>
    <div class="min-w-0 mt-2 flex-grow">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            <a href="{{ route('projects.show', $task->project) }}" class="font-semibold hover:underline"
                style="color: {{ $task->project->color ?? '#6c757d' }};">{{ $task->project->title }}</a>

            {{-- ▼▼▼【ここから変更】タスク名と緊急バッジを横並びにする ▼▼▼ --}}
            <div class="flex items-center gap-x-2">
                <p>
                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
                        class="text-xl font-medium text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                        {{ $task->name }}
                    </a>
                </p>
                @if($task->end_date && $task->end_date->isToday() && $task->status !== 'completed')
                    <span class="px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full flex-shrink-0">本日締切</span>
                @endif
            </div>
            {{-- ▲▲▲【変更ここまで】▲▲▲ --}}

            <p>
                <i class="fas fa-dragon fa-fw mr-0.5 text-gray-400"></i>
                {{ optional($task->character)->name ?? 'キャラクター未設定' }}
            </p>
            @if($task->end_date && $task->status !== 'completed')
                @php
                    $now = \Carbon\Carbon::now();
                    $isPast = $task->end_date->isPast();
                @endphp
                <p class="text-xs mt-1 {{ $isPast ? 'text-red-500 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                    <i class="far fa-clock fa-fw mr-1"></i>
                    期限: {{ $task->end_date->format('n/j H:i') }} ({{ $task->end_date->diffForHumans() }})
                </p>
            @else
                <p>
                    <i class="far fa-clock fa-fw mr-1"></i>
                    期限: {{ $task->end_date->format('n/j H:i') }}
                </p>
            @endif
        </div>
    </div>
</li>
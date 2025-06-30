{{-- resources/views/home/partials/home-task-item.blade.php --}}

<li class="flex items-start group"
    data-task-id="{{ $task->id }}"
    data-duration="{{ $task->duration ?? 0 }}"
    data-total-work-seconds="{{ $task->total_work_seconds }}">

    {{-- カラム1: ステータスアイコン --}}
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

    {{-- カラム2: タスク情報 --}}
    <div class="min-w-0 mt-2 flex-grow">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            <a href="{{ route('projects.show', $task->project) }}" class="font-semibold hover:underline"
                style="color: {{ $task->project->color ?? '#6c757d' }};">{{ $task->project->title }}</a>

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

            <p class="mt-0.5">
                <i class="fas fa-dragon fa-fw mr-0.5 text-gray-400"></i>
                {{ optional($task->character)->name ?? 'キャラクター未設定' }}
            </p>
            <p class="mt-0.5">
                <i class="fas fa-stopwatch fa-fw mr-0.5 text-gray-400"></i>
                予定工数：{{ $task->formatted_duration ?? '設定なし' }}
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
                <p class="mt-0.5">
                    <i class="far fa-clock fa-fw mr-1"></i>
                    期限: {{ $task->end_date->format('n/j H:i') }}
                </p>
            @endif
        </div>
    </div>

    {{-- カラム3: 実績時間とタイマー --}}
    <div class="mt-2 ml-4 flex-shrink-0 flex flex-col items-end space-y-2">
        {{-- 実績時間表示 --}}
        <div class="text-sm font-mono text-right min-w-[80px]">
             @if(!$task->is_milestone && !$task->is_folder)
                <div class="task-actual-time-display" data-task-id="{{ $task->id }}">
                    @if (!($task->status === 'in_progress' && !$task->is_paused) && $task->total_work_seconds > 0)
                        @php
                            $remainingSeconds = ($task->duration ?? 0) * 60 - $task->total_work_seconds;
                            $isOver = $remainingSeconds < 0;
                            $absSeconds = abs($remainingSeconds);
                            $hours = floor($absSeconds / 3600);
                            $minutes = floor(($absSeconds % 3600) / 60);
                            $seconds = $absSeconds % 60;
                            $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                        @endphp
                        <span class="{{ $isOver ? 'text-red-500 font-bold' : '' }}">
                            {{ ($isOver ? '-' : '') . $formattedTime }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- タイマーコントロール --}}
        <div>
             @if(!$task->is_folder && !$task->is_milestone)
                @php
                    // このタスクに対して操作可能か（共有アカウント or 自分が担当者）
                    $canInteract = (Auth::check() && Auth::user()->status === \App\Models\User::STATUS_SHARED) || $task->assignees->contains('id', Auth::id());
                @endphp

                {{-- ①「自分のセクション」かつ「操作可能なユーザー」である場合のみ、操作ボタンコンテナを表示 --}}
                @if($isCurrentUserSection && $canInteract)
                    <div class="timer-controls"
                        data-task-id="{{ $task->id }}"
                        data-task-status="{{ $task->status }}"
                        data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}"
                        data-assignees='{{ json_encode($task->assignees->map->only(['id', 'name'])->values()) }}'
                        data-view-mode="compact">
                        {{-- JSが状況に応じた操作ボタン（開始/停止など）を生成します --}}
                    </div>
                {{-- ② それ以外（他人のセクションなど）の場合は、必ずラベル表示コンテナを表示 --}}
                @else
                    <div class="timer-display-only"
                        data-task-id="{{ $task->id }}"
                        data-task-status="{{ $task->status }}"
                        data-is-paused="{{ $task->is_paused ? 'true' : 'false' }}">
                         {{-- JSが状況に応じたラベル（作業中など）を生成します --}}
                    </div>
                @endif
            @else
                <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
            @endif
        </div>
    </div>
</li>
@php
    $appTimezone = config('app.timezone');
    $now = \Carbon\Carbon::now($appTimezone);
    $endDate = $task->end_date->copy()->setTimezone($appTimezone);
    $isCompleted = in_array($task->status, ['completed', 'cancelled']);
    $isPast = !$isCompleted && $endDate->isPast();
    $isDueSoon = !$isCompleted && !$isPast && $endDate->lte($now->copy()->addHours(24));
@endphp
<div class="flex items-center justify-between">
    <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold truncate" style="color: {{ $task->project->color ?? '#6c757d' }};"
            title="案件: {{ $task->project->title }}">
            <a href="{{ route('projects.show', $task->project) }}" class="hover:underline">
                {{ $task->project->title }}
            </a>
        </p>
        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
            class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 truncate block">
            {{ $task->name }}
        </a>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
            <i class="fas fa-dragon fa-fw mr-1 text-gray-400"></i> {{ $task->character->name ?? 'キャラクター未設定' }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate"
            title="担当: {{ $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : '-' }}">
            <i class="fas fa-users fa-fw mr-1 text-gray-400"></i>担当:
            {{ $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : '-' }}
        </p>
        <p
            class="text-xs mt-1 {{ $isPast ? 'text-red-500 font-semibold' : ($isDueSoon ? 'text-yellow-500 font-semibold' : 'text-gray-500 dark:text-gray-400') }}">
            <i class="far fa-clock mr-1"></i>
            {{ $task->end_date->format('n/j H:i') }}
            <span class="text-gray-400 dark:text-gray-500">({{ $task->end_date->diffForHumans() }})</span>
        </p>
    </div>
</div>
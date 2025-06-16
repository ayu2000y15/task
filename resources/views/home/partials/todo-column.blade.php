<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <div
        class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-{{$columnData['color']}}-100 dark:bg-{{$columnData['color']}}-800/50 rounded-t-lg">
        <h6 class="font-semibold text-{{$columnData['color']}}-800 dark:text-{{$columnData['color']}}-200">
            <i class="{{ $columnData['icon'] }} mr-2"></i>{{ $columnData['label'] }}
        </h6>
        <span
            class="px-2 py-0.5 text-xs font-semibold bg-{{$columnData['color']}}-200 text-{{$columnData['color']}}-800 dark:bg-{{$columnData['color']}}-600 dark:text-{{$columnData['color']}}-100 rounded-full">{{ $tasksInStatus->count() }}</span>
    </div>
    <ul class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
        @forelse($tasksInStatus as $task)
            <li class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-start border-l-4"
                style="border-left-color: {{ $task->project->color }};">
                <div class="flex-grow min-w-0">
                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
                        class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 whitespace-normal break-words inline-block">
                        {{ $task->name }}
                    </a>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <a href="{{ route('projects.show', $task->project) }}" class="font-semibold"
                            style="color: {{ $task->project->color }}">{{ $task->project->title }}</a>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        担当: {{ $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : '-' }}
                    </p>
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">工程がありません</li>
        @endforelse
    </ul>
</div>
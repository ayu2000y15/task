@forelse($assignees as $assignee)
    <span
        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
        {{ $assignee->name }}
    </span>
@empty
    <span class="text-gray-400">-</span>
@endforelse
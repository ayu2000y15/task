@props([
    'label',
    'sortKey',
    'currentSort',
    'currentDirection'
])
@php
    $direction = ($currentSort === $sortKey && $currentDirection === 'asc') ? 'desc' : 'asc';
    $iconClass = 'fa-sort';
    if ($currentSort === $sortKey) {
        $iconClass = $currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    }
@endphp

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $sortKey, 'direction' => $direction]) }}" class="flex items-center group">
        <span>{{ $label }}</span>
        <i class="fas {{ $iconClass }} ml-2 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200"></i>
    </a>
</th>
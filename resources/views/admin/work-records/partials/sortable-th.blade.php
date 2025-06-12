@props([
    'label',
    'sortKey',
    'currentSort',
    'currentDirection',
])

@php
    $isCurrentSort = ($currentSort === $sortKey);
    $nextDirection = ($isCurrentSort && $currentDirection === 'asc') ? 'desc' : 'asc';
@endphp

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $sortKey, 'direction' => $nextDirection]) }}" class="flex items-center">
        <span>{{ $label }}</span>
        <span class="ml-2">
            @if($isCurrentSort)
                @if($currentDirection === 'asc')
                    <i class="fas fa-sort-up"></i>
                @else
                    <i class="fas fa-sort-down"></i>
                @endif
            @else
                <i class="fas fa-sort text-gray-300 dark:text-gray-500"></i>
            @endif
        </span>
    </a>
</th>
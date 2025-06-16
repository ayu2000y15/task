@props([
    'title',
    'requests',
    'isEmptyMessage',
    'collapsible' => false,
])

<div @if($collapsible) x-data="{ open: false }" @endif>
    <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4">
        @if($collapsible)
            <button @click="open = !open" class="flex items-center space-x-2 text-left w-full">
                <span>{{ $title }} ({{ $requests->count() }})</span>
                <i class="fas fa-sm" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
            </button>
        @else
            <span>{{ $title }} ({{ $requests->count() }})</span>
        @endif
    </h2>
    <div @if($collapsible) x-show="open" x-collapse @endif class="space-y-4">
        @forelse($requests as $request)
            @include('requests.partials.request-card', ['request' => $request])
        @empty
            <p class="text-gray-500 dark:text-gray-400">{{ $isEmptyMessage }}</p>
        @endforelse
    </div>
</div>
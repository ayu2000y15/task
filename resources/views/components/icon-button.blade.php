@props([
    'as' => 'button',
    'href' => null,
    'icon',
    'title' => null,
    'color' => 'gray',
    'size' => 'sm',
    'action' => null,
    'method' => null,
    'confirm' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center p-1 rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800';
    $colorClasses = match ($color) {
        'blue' => 'text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-gray-700 focus:ring-blue-500',
        'red' => 'text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-gray-700 focus:ring-red-500',
        'yellow' => 'text-yellow-600 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-gray-700 focus:ring-yellow-500',
        'green' => 'text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-gray-700 focus:ring-green-500',
        default => 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:ring-indigo-500',
    };
    $sizeClasses = match ($size) {
        'xs' => 'text-xs',
        default => 'text-sm',
    };
    $iconSizeClass = match ($size) {
        'xs' => 'fa-xs',
        default => 'fa-sm',
    };
    if ($as === 'button' && $href) {
        $as = 'a';
    }
@endphp

@if ($as === 'a')
    <a href="{{ $href }}" title="{{ $title }}" {{ $attributes->merge(['class' => "$baseClasses $colorClasses $sizeClasses"]) }}>
        <i class="{{ $icon }} {{ $iconSizeClass }}"></i>
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $action ? 'submit' : 'button' }}"
        @if ($action) formaction="{{ $action }}" @endif
        @if ($method && strtoupper($method) !== 'POST') formmethod="POST" @endif
        title="{{ $title }}"
        @if($confirm) onclick="return confirm('{{ $confirm }}');" @endif
        {{ $attributes->merge(['class' => "$baseClasses $colorClasses $sizeClasses"]) }}
    >
        @if ($method && strtoupper($method) !== 'POST' && strtoupper($method) !== 'GET')
            @csrf
            @method(strtoupper($method))
        @elseif (strtoupper($method) === 'POST' && $action)
             @csrf
        @endif
        <i class="{{ $icon }} {{ $iconSizeClass }}"></i>
        {{ $slot }}
    </button>
@endif
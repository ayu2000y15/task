@props([
    'href' => null, // href属性をpropsとして定義
    'as' => null    // as属性をpropsとして定義 (a または button を想定)
])
@php
    // as="a" と href が指定されている場合はアンカータグとして扱う
    // それ以外の場合はボタンとして扱う
    $tag = ($as === 'a' && $href) ? 'a' : 'button';

    $defaultAttributes = [
        'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150'
    ];

    if ($tag === 'button') {
        $defaultAttributes['type'] = 'button';
    }
@endphp

<{{ $tag }} {{ $attributes->merge($defaultAttributes) }} @if($tag === 'a' && $href) href="{{ $href }}" @endif>
    {{ $slot }}
</{{ $tag }}>
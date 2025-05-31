@props([
    'as' => 'button', // デフォルトは 'button'
    'href' => null,    // リンク先のURL
    'type' => 'submit' // buttonの場合のデフォルトtype属性
])
@php
    // 'as' => 'a' かつ href が指定されていればアンカータグとして扱う
    // それ以外の場合はボタンとして扱う
    $tag = ($as === 'a' && $href !== null) ? 'a' : 'button';

    $defaultAttributes = [
        'class' => 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-sky-500 dark:hover:bg-sky-600 dark:focus:bg-sky-600 dark:active:bg-sky-700 dark:focus:ring-sky-400 transition ease-in-out duration-150'
    ];

    if ($tag === 'button') {
        $defaultAttributes['type'] = $type; // propsで渡されたtypeを使用、なければ'submit'
    }
@endphp

<{{ $tag }}
        @if($tag === 'a')
            href="{{ $href }}"
        @endif
    {{ $attributes->merge($defaultAttributes) }}
>
    {{ $slot }}
</{{ $tag }}>
@props([
    'disabled' => false,
    'type' => 'text',
    'name' => '',
    'value' => '', // valueプロパティを追加し、old()ヘルパーで値を保持できるようにする
    'hasError' => false, // デフォルトは false とし、呼び出し側でエラー状態を渡す
])


@php
    $errorClasses = 'border-red-300 dark:border-red-600 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 dark:focus:ring-red-500 dark:focus:border-red-500';
    $defaultClasses = 'border-gray-300 dark:border-gray-600 dark:focus:border-indigo-600 focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-600';

    $classes = 'form-input block w-full mt-1 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400';
    $classes .= ($hasError ? ' ' . $errorClasses : ' ' . $defaultClasses);
@endphp

<input type="{{ $type }}"
       name="{{ $name }}"
       value="{{ $value }}" {{-- value属性を出力 --}}
       {{ $disabled ? 'disabled' : '' }}
       {!! $attributes->merge(['class' => $classes]) !!}
>
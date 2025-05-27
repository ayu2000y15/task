@props([
    'disabled' => false,
    'type' => 'text',
    'name' => '', // name属性をpropsで受け取れるように追加
    'hasError' => $name ? $errors->has($name) : false, // name属性があればエラーをチェック
])

<input type="{{ $type }}"
       {{ $disabled ? 'disabled' : '' }}
       name="{{ $name }}" {{-- name属性を出力 --}}
       {!! $attributes->merge([
           'class' => 'form-input block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400'
           . ($hasError ? ' border-red-500 dark:border-red-600' : '')
       ]) !!}
>
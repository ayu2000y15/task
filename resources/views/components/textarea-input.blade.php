@props([
    'disabled' => false,
    'name' => '', // name属性をpropsで受け取れるように追加
    // ★ この行を修正
    'hasError' => (!empty($name) && isset($errors) && $errors->has($name)),
])

<textarea
    {{ $disabled ? 'disabled' : '' }}
    @if(!empty($name)) name="{{ $name }}" @endif {{-- name属性が空でなければ出力 --}}
    {!! $attributes->merge([
        'class' => 'form-textarea block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400'
        . ($hasError ? ' border-red-500 dark:border-red-600' : '')
    ]) !!}
>{{ $slot }}</textarea>
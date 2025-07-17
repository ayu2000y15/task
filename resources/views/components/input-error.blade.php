@props(['messages'])

@php
    // バリデータがネストした配列でメッセージを返す場合があるため、
    // Arr::flatten() を使って、どのような形式でも安全な文字列の配列に変換します。
    $flattenedMessages = Illuminate\Support\Arr::flatten((array) $messages);
@endphp

@if (!empty($flattenedMessages))
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 dark:text-red-400 space-y-1']) }}>
        @foreach ($flattenedMessages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
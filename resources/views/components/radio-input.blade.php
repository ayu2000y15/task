@props([
    'disabled' => false,
    'name',
    'id', // ラジオボタンはID必須
    'value',
    'label' => null,
    'checked' => false,
])

<label for="{{ $id }}" class="inline-flex items-center">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="radio"
        value="{{ $value }}"
        {{ $checked ? 'checked' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => 'form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed'
        ]) !!}
    >
    @if ($label)
        <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">{!! $label !!}</span>
    @endif
</label>
@props([
    'disabled' => false,
    'required' => false,
    'name',
    'id' => $name,
    'options' => [], // 連想配列またはコレクションを想定: ['value' => 'label', ...]
    'selected' => null,
    'label' => null,
    'emptyOptionText' => null, // 例: "選択してください"
    'hasError' => $errors->has($name),
])

<div>
    @if ($label)
        <x-input-label :for="$id" :value="$label" :required="$required" />
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {!! $attributes->merge([
            'class' => 'form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400'
            . ($hasError ? ' border-red-500 dark:border-red-600' : '')
        ]) !!}
    >
        @if ($emptyOptionText)
            <option value="">{{ $emptyOptionText }}</option>
        @endif
        @foreach ($options as $value => $display)
            <option value="{{ $value }}" {{ ($selected !== null && (string) $value === (string) $selected) ? 'selected' : '' }}>
                {{ $display }}
            </option>
        @endforeach
    </select>

    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
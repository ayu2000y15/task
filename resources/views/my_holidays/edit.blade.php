@extends('layouts.app')
@section('title', '休日の編集')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">休日の編集</h1>
            <x-secondary-button as="a" href="{{ route('my-holidays.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-lg mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <form action="{{ route('my-holidays.update', $userHoliday) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" value="休日の名称" :required="true" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $userHoliday->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="date" value="日付" :required="true" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $userHoliday->date->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">種類</label>
                        <div class="mt-2 flex space-x-4">
                            @php
                                $periodTypes = ['full' => '全休', 'am' => '午前休', 'pm' => '午後休'];
                            @endphp
                            @foreach($periodTypes as $value => $label)
                                <label class="inline-flex items-center">
                                    <input type="radio" class="form-radio text-indigo-600" name="period_type"
                                        value="{{ $value }}" {{ old('period_type', $userHoliday->period_type) == $value ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <x-secondary-button as="a" href="{{ route('my-holidays.index') }}">キャンセル</x-secondary-button>
                        <x-primary-button type="submit">更新する</x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
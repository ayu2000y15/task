@extends('layouts.app')
@section('title', '交通費の編集')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">交通費の編集</h1>
            <x-secondary-button as="a" href="{{ route('transportation-expenses.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <form action="{{ route('transportation-expenses.update', $expense) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-6">
                    <div>
                        <x-input-label for="date" value="利用日" :required="true" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $expense->date->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="project_id" value="関連案件" />
                        <select id="project_id" name="project_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">その他（案件に紐付けない）</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $expense->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">案件に紐付けると、その案件のコストとして自動登録されます。</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="departure" value="出発地" />
                            <x-text-input id="departure" name="departure" type="text" class="mt-1 block w-full"
                                :value="old('departure', $expense->departure)" placeholder="例: 自宅" />
                            <x-input-error :messages="$errors->get('departure')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="destination" value="到着地" :required="true" />
                            <x-text-input id="destination" name="destination" type="text" class="mt-1 block w-full"
                                :value="old('destination', $expense->destination)" required placeholder="例: 〇〇スタジオ" />
                            <x-input-error :messages="$errors->get('destination')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="amount" value="金額（円）" :required="true" />
                        <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full"
                            :value="old('amount', $expense->amount)" required min="0" />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="備考" />
                        <textarea id="notes" name="notes" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('notes', $expense->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <x-secondary-button as="a"
                            href="{{ route('transportation-expenses.index') }}">キャンセル</x-secondary-button>
                        <x-primary-button type="submit">更新する</x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
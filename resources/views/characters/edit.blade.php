@extends('layouts.app')

@section('title', 'キャラクター編集 - ' . $character->name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">キャラクター編集: {{ $character->name }}</h1>
            <x-secondary-button onclick="location.href='{{ route('projects.show', $project) }}'">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>「{{ $project->title }}」に戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('characters.update', $character) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="キャラクター名" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $character->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        {{-- ★ 性別フィールド追加 --}}
                        <div>
                            <x-input-label for="gender" value="性別" />
                            <select id="gender" name="gender"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="" @if(old('gender', $character->gender) == '') selected @endif>選択しない</option>
                                <option value="male" @if(old('gender', $character->gender) == 'male') selected @endif>男性
                                </option>
                                <option value="female" @if(old('gender', $character->gender) == 'female') selected @endif>女性
                                </option>
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="備考" />
                            <x-textarea-input id="description" name="description" class="mt-1 block w-full"
                                rows="4">{{ old('description', $character->description) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <x-primary-button>
                            <i class="fas fa-save mr-2"></i>更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
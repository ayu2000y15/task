@extends('layouts.app')

@section('title', '衣装案件編集 - ' . $project->title)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="project-form-page">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">衣装案件編集: {{ $project->title }}</h1>
            <div>
                <a href="{{ route('projects.show', $project) }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                    <i class="fas fa-arrow-left mr-2"></i> 詳細に戻る
                </a>
                @can('delete', $project)
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline-block"
                        onsubmit="return confirm('本当に削除しますか？衣装案件内のすべての工程も削除されます。');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:border-red-800 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <i class="fas fa-trash mr-2"></i> 削除
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.update', $project) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">衣装案件名
                                <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('title') border-red-500 @enderror"
                                value="{{ old('title', $project->title) }}" required>
                            @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="series_title"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">作品名</label>
                            <input type="text" name="series_title" id="series_title"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('series_title') border-red-500 @enderror"
                                value="{{ old('series_title', $project->series_title) }}">
                            @error('series_title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="client_name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">依頼主名</label>
                            <input type="text" name="client_name" id="client_name"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('client_name') border-red-500 @enderror"
                                value="{{ old('client_name', $project->client_name) }}">
                            @error('client_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="color"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">カラー</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="color" name="color" id="color"
                                    class="form-input h-10 w-12 rounded-l-md border-gray-300 p-1 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    value="{{ old('color', $project->color) }}">
                                <input type="text" id="colorHex" name="color_hex_display"
                                    class="form-input block w-full rounded-r-md border-gray-300 border-l-0 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    value="{{ old('color', $project->color) }}" readonly>
                            </div>
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始日
                                <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" id="start_date"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('start_date') border-red-500 @enderror"
                                value="{{ old('start_date', $project->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了日
                                <span class="text-red-500">*</span></label>
                            <input type="date" name="end_date" id="end_date"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('end_date') border-red-500 @enderror"
                                value="{{ old('end_date', $project->end_date->format('Y-m-d')) }}" required>
                            @error('end_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">説明</label>
                            <textarea name="description" id="description" rows="4"
                                class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('description') border-red-500 @enderror">{{ old('description', $project->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex items-center mt-2">
                                <input type="checkbox" name="is_favorite" id="is_favorite" value="1" {{ old('is_favorite', $project->is_favorite) ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500">
                                <label for="is_favorite"
                                    class="ml-2 block text-sm text-gray-900 dark:text-gray-300">お気に入りに追加</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <a href="{{ route('projects.show', $project) }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            キャンセル
                        </a>
                        <x-primary-button>
                            <i class="fas fa-save mr-2"></i> 更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
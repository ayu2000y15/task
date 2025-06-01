@extends('layouts.app')

@section('title', 'ユーザー役割編集')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">ユーザー役割編集</h1>
            <x-secondary-button onclick="location.href='{{ route('admin.users.index') }}'">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>一覧へ戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $user->name }} <span class="text-sm text-gray-500 dark:text-gray-400">({{ $user->email }})</span>
                </h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label value="役割" class="mb-2" />
                        <div class="space-y-2">
                            @foreach($roles as $role)
                                <label for="role_{{ $role->id }}" class="flex items-center">
                                    <input id="role_{{ $role->id }}" name="roles[]" type="checkbox" value="{{ $role->id }}" {{ $user->roles->contains($role->id) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">{{ $role->display_name }}</span>
                                </label>
                            @endforeach
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
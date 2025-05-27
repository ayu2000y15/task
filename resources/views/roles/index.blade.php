@extends('layouts.app')

@section('title', '権限設定')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">権限設定</h1>

    <div class="space-y-4">
        @foreach ($roles as $role)
            <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div @click="open = !open" class="px-6 py-4 cursor-pointer flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $role->display_name }}</h2>
                    <i class="fas fa-chevron-down text-gray-500 dark:text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </div>
                <div x-show="open" x-collapse style="display: none;">
                    <div class="p-6">
                        <form action="{{ route('roles.update', $role) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="space-y-6">
                                @foreach ($permissions as $groupName => $permissionGroup)
                                    <fieldset>
                                        <legend class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2 border-b border-gray-200 dark:border-gray-700 pb-1">{{ ucfirst($groupName) }}</legend>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                            @foreach ($permissionGroup as $permission)
                                                <label for="perm_{{ $role->id }}_{{ $permission->id }}" class="flex items-center space-x-2">
                                                    <input id="perm_{{ $role->id }}_{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" type="checkbox"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"
                                                        {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $permission->display_name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endforeach
                            </div>

                            <div class="mt-6 flex justify-end">
                                <x-primary-button>
                                    <i class="fas fa-save mr-2"></i>「{{ $role->display_name }}」の権限を保存
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', '権限設定')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">権限設定</h1>
        <x-primary-button onclick="location.href='{{ route('admin.roles.create') }}'">
            <i class="fas fa-plus mr-2"></i>
            <span>ロール追加</span>
        </x-primary-button>
    </div>

    <div class="space-y-4">
        @foreach ($roles as $role)
            <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div @click="open = !open" class="px-6 py-4 cursor-pointer flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $role->display_name }}
                        <span class="text-sm text-gray-500 dark:text-gray-400">({{ $role->users_count }}人)</span>
                    </h2>
                    <i class="fas fa-chevron-down text-gray-500 dark:text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </div>
                <div x-show="open" x-collapse style="display: none;">
                    <div class="p-6">
                        @can('delete', $role)
                        <div class="flex justify-end mb-4">
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('本当に「{{ $role->display_name }}」を削除しますか？この操作は元に戻せません。');">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit">
                                    <i class="fas fa-trash mr-2"></i>削除
                                </x-danger-button>
                            </form>
                        </div>
                        @endcan

                        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="space-y-6">
                                @foreach ($permissions as $groupName => $permissionGroup)
                                    <fieldset x-data="{
                                        masterChecked: false,
                                        permissionIdsInGroup: {{ json_encode($permissionGroup->pluck('id')->toArray()) }},
                                        rolePermissionIds: {{ json_encode($role->permissions->pluck('id')->toArray()) }},
                                        checkboxesInGroup: [],
                                        init() {
                                            this.checkboxesInGroup = Array.from(this.$el.querySelectorAll('input[type=\'checkbox\'].permission-checkbox-{{ $groupName }}_{{ $role->id }}'));
                                            this.updateMasterCheckboxState();
                                        },
                                        toggleAllPermissions() {
                                            this.checkboxesInGroup.forEach(checkbox => {
                                                checkbox.checked = this.masterChecked;
                                            });
                                        },
                                        updateMasterCheckboxState() {
                                            if (this.checkboxesInGroup.length === 0) {
                                                this.masterChecked = false;
                                                return;
                                            }
                                            const allChecked = this.checkboxesInGroup.every(checkbox => checkbox.checked);
                                            this.masterChecked = allChecked;
                                        }
                                    }">
                                        <legend class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2 border-b border-gray-200 dark:border-gray-700 pb-1 flex justify-between items-center">
                                            {{-- グループ名とチェックボックスの間にスペースを入れるため、spanにmr-4を追加 --}}
                                            <span class="mr-4">{{ ucfirst($groupName) }}</span>
                                            <label class="flex items-center space-x-1 text-xs cursor-pointer">
                                                <input type="checkbox"
                                                       title="すべて選択/解除"
                                                       x-model="masterChecked"
                                                       @change="toggleAllPermissions()"
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                                <span>全て</span>
                                            </label>
                                        </legend>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                            @foreach ($permissionGroup as $permission)
                                                <label for="perm_{{ $role->id }}_{{ $permission->id }}" class="flex items-center space-x-2">
                                                    <input id="perm_{{ $role->id }}_{{ $permission->id }}"
                                                           name="permissions[]"
                                                           value="{{ $permission->id }}"
                                                           type="checkbox"
                                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 permission-checkbox-{{ $groupName }}_{{ $role->id }}"
                                                           :checked="rolePermissionIds.includes({{ $permission->id }})"
                                                           @change="updateMasterCheckboxState()">
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
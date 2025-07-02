<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('tasks.viewAny');
    }

    public function viewAnyFileFolders(User $user): bool
    {
        return $user->hasPermissionTo('tasks.file-view');
    }

    // 新しいメソッド: フォルダ作成（ファイルアップロード機能利用）の可否
    public function canCreateFoldersForFileUpload(User $user): bool
    {
        // フォルダ作成はファイルアップロード権限で制御
        return $user->hasPermissionTo('tasks.file-upload');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.update');
    }

    /**
     * 工程の重要な項目を更新する権限があるか決定する
     */
    public function updateCriticalFields(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.update-critical-fields');
    }

    public function fileViewAny(User $user): bool
    {
        return $user->hasPermissionTo('tasks.file-view');
    }

    public function fileView(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.file-view');
    }

    public function fileDownload(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.file-download');
    }

    // 特定のタスクへのファイルアップロード権限
    public function fileUpload(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.file-upload');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.delete'); // または 'tasks.delete'
    }
}

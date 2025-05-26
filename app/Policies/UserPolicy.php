<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $currentUser): bool
    {
        return $currentUser->hasPermissionTo('users.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $currentUser, User $user): bool
    {
        // ユーザー自身、または users.view 権限を持つユーザーは表示可能
        if ($currentUser->id === $user->id) {
            return true;
        }
        return $currentUser->hasPermissionTo('users.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $currentUser): bool
    {
        return $currentUser->hasPermissionTo('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $currentUser, User $user): bool
    {
        // ユーザー自身（ロール変更を除く主要情報）、または users.update 権限を持つユーザーは更新可能
        // ロール変更など、より強力な権限が必要な場合は、コントローラー側でさらにチェックするか、
        // 別途 'manageRoles'のような権限をここでチェックすることも考えられます。
        // 今回は users.update で統一します。
        if ($currentUser->id === $user->id && !$currentUser->hasPermissionTo('users.update')) {
            // 自分自身のプロフィール更新は許可するが、権限がない場合はロール変更などはさせない想定。
            // ここではUserControllerのupdateが主にロール更新なので、users.update権限を基本とする。
            // プロフィール更新はProfileControllerが担当。
        }
        return $currentUser->hasPermissionTo('users.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $currentUser, User $user): bool
    {
        // 自分自身は削除できないようにする例
        if ($currentUser->id === $user->id) {
            return false;
        }
        return $currentUser->hasPermissionTo('users.delete');
    }
}

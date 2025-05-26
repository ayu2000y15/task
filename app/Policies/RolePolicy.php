<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * 役割・権限設定画面の表示権限
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.viewAny');
    }

    /**
     * Determine whether the user can update the model.
     * 役割に紐づく権限の更新権限
     * (他の役割関連の操作、例: Role作成・削除が将来的に追加される場合もこの権限でカバーするか、
     * 別途 roles.create, roles.delete などのパーミッションを定義することも可能です)
     */
    public function update(User $user, Role $role): bool
    {
        // $role パラメータはポリシーメソッドの標準的なシグネチャに合わせるために含めていますが、
        // 現在のパーミッション 'roles.update' は特定のロールインスタンスに依存しない一般的な権限です。
        return $user->hasPermissionTo('roles.update');
    }

    // 必要に応じて、将来的に view, create, delete などのメソッドも追加できます。
    // public function view(User $user, Role $role): bool
    // {
    //     return $user->hasPermissionTo('roles.view');
    // }

    // public function create(User $user): bool
    // {
    //     return $user->hasPermissionTo('roles.create');
    // }

    // public function delete(User $user, Role $role): bool
    // {
    //     return $user->hasPermissionTo('roles.delete');
    // }
}

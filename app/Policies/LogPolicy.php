<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activity logs.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // 'view_activity_log' 権限を持つユーザーのみログ閲覧を許可する
        // (この権限は後ほど RolePermissionController で特定の役割に割り当てる想定)
        return $user->hasPermissionTo('log.viewAny');
    }

    // 必要に応じて、特定のログエントリの表示権限なども定義可能ですが、
    // 今回は一覧表示の権限のみとします。
    // public function view(User $user, Activity $activity)
    // {
    //     return $user->hasPermissionTo('log.view');
    // }
}

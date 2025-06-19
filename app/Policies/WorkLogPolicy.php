<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // 管理者権限を持つユーザーのみが全作業ログを閲覧できる（例）
        return $user->hasPermissionTo('work-logs.viewAny');
    }

    /**
     * 自身の作業実績一覧を閲覧できるかを判定する
     *
     * @param \App\Models\User $user
     * @return boolean
     */
    public function viewOwn(User $user): bool
    {
        // 'work-logs.view-own'の権限を持つユーザーに許可
        return $user->hasPermissionTo('work-logs.viewOwn');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WorkLog  $workLog
     * @return bool
     */
    public function view(User $user, WorkLog $workLog): bool
    {
        // 自分の作業ログ、または管理権限があれば閲覧できる
        return $user->id === $workLog->user_id || $user->hasPermissionTo('work-logs.viewAny');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // 誰でも自身の作業ログは作成可能
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WorkLog  $workLog
     * @return bool
     */
    public function update(User $user, WorkLog $workLog): bool
    {
        // 自分の作業ログのみ更新（一時停止、終了など）できる
        return $user->id === $workLog->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WorkLog  $workLog
     * @return bool
     */
    public function delete(User $user, WorkLog $workLog): bool
    {
        // 自分の作業ログのみ削除できる
        return $user->id === $workLog->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WorkLog  $workLog
     * @return bool
     */
    public function restore(User $user, WorkLog $workLog): bool
    {
        return $user->id === $workLog->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WorkLog  $workLog
     * @return bool
     */
    public function forceDelete(User $user, WorkLog $workLog): bool
    {
        return $user->id === $workLog->user_id;
    }
}

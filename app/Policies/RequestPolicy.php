<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Request $request): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * ユーザーが指定された依頼を更新できるか判断
     */
    public function update(User $user, Request $request): bool
    {
        // 依頼者(requester)と現在ログインしているユーザーが一致する場合のみ許可
        return $user->id === $request->requester_id;
    }

    /**
     * ユーザーが指定された依頼を削除できるか判断
     */
    public function delete(User $user, Request $request): bool
    {
        // 依頼者(requester)と現在ログインしているユーザーが一致する場合のみ許可
        return $user->id === $request->requester_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Request $request): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Request $request): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestItemPolicy
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
    public function view(User $user, RequestItem $requestItem): bool
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
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RequestItem  $requestItem
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, RequestItem $requestItem)
    {
        // 依頼の担当者であれば更新を許可する
        return $requestItem->request->assignees()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RequestItem $requestItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RequestItem $requestItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RequestItem $requestItem): bool
    {
        return false;
    }
}

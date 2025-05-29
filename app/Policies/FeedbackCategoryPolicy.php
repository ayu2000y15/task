<?php

namespace App\Policies;

use App\Models\FeedbackCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FeedbackCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     * 管理者のみがカテゴリ一覧を閲覧できる
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('feedback-category.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     * (通常はviewAnyでカバーされるが、個別の権限が必要な場合に)
     */
    public function view(User $user, FeedbackCategory $feedbackCategory): bool
    {
        return $user->hasPermissionTo('feedback-category.viewAny');
    }

    /**
     * Determine whether the user can create models.
     * 管理者のみがカテゴリを作成できる
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('feedback-category.update');
    }

    /**
     * Determine whether the user can update the model.
     * 管理者のみがカテゴリを更新できる
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('feedback-category.update');
    }

    /**
     * Determine whether the user can delete the model.
     * 管理者のみがカテゴリを削除できる
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('feedback-category.delete');
    }

    /**
     * Determine whether the user can reorder models.
     * 管理者のみがカテゴリを並び替えできる
     */
    public function reorder(User $user): bool
    {
        return $user->hasPermissionTo('feedback-category.update');
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, FeedbackCategory $feedbackCategory): bool
    // {
    //     return $user->hasRole('Admin');
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, FeedbackCategory $feedbackCategory): bool
    // {
    //     return $user->hasRole('Admin');
    // }
}

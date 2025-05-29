<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FeedbackPolicy
{
    /**
     * Determine whether the user can view any models.
     * 管理者のみがフィードバック一覧を閲覧できる
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('feedback.viewAny'); // 例: 'Admin' ロールを持つユーザー
    }

    /**
     * Determine whether the user can view the model.
     * 管理者のみが特定のフィードバックを閲覧できる (今回は主に一覧での操作のため、viewAnyでカバー)
     */
    public function view(User $user, Feedback $feedback): bool
    {
        return $user->hasPermissionTo('feedback.viewAny');
    }

    /**
     * Determine whether the user can create models.
     * 認証済みユーザーなら誰でもフィードバックを作成できる
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('feedback.create'); // ログインしていればOK
    }

    /**
     * Determine whether the user can update the model.
     * 管理者のみがフィードバックを更新できる (ステータス、メモ、担当者など)
     */
    public function update(User $user, Feedback $feedback): bool
    {
        return $user->hasPermissionTo('feedback.update');
    }

    /**
     * Determine whether the user can delete the model.
     * (今回は削除機能は実装していませんが、将来的に必要であれば)
     * 管理者のみがフィードバックを削除できる
     */
    public function delete(User $user, Feedback $feedback): bool
    {
        return $user->hasPermissionTo('feedback.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, Feedback $feedback): bool
    // {
    //     return $user->hasRole('Admin');
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, Feedback $feedback): bool
    // {
    //     return $user->hasRole('Admin');
    // }
}

<?php

namespace App\Policies;

use App\Models\ExternalProjectSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExternalProjectSubmissionPolicy
{
    /**
     * Determine whether the user can view any models.
     * 特定の権限を持つユーザーが一覧を閲覧できる
     */
    public function viewAny(User $user): bool
    {
        // 例: 'external-submission.viewAny' という権限を持つユーザー
        return $user->hasPermissionTo('external-submission.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     * 特定の権限を持つユーザーが詳細を閲覧できる
     */
    public function view(User $user, ExternalProjectSubmission $externalProjectSubmission): bool
    {
        // viewAnyと同じ権限で良いか、個別の権限にするか検討
        return $user->hasPermissionTo('external-submission.viewAny');
    }

    /**
     * Determine whether the user can create models.
     * (外部フォームからの送信なので、認証済みユーザーによる作成は通常なし)
     */
    public function create(User $user): bool
    {
        return false; // 通常、管理画面からは作成しない想定
    }

    /**
     * Determine whether the user can update the model.
     * 特定の権限を持つユーザーがステータスなどを更新できる
     */
    public function update(User $user, ExternalProjectSubmission $externalProjectSubmission): bool
    {
        // 例: 'external-submission.update' という権限を持つユーザー
        return $user->hasPermissionTo('external-submission.update');
    }

    /**
     * Determine whether the user can delete the model.
     * (今回は削除機能は実装していませんが、必要であれば)
     */
    public function delete(User $user, ExternalProjectSubmission $externalProjectSubmission): bool
    {
        // 例: 'external-submission.delete' という権限を持つユーザー
        return $user->hasPermissionTo('external-submission.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, ExternalProjectSubmission $externalProjectSubmission): bool
    // {
    //     return $user->hasPermissionTo('external-submission.delete');
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, ExternalProjectSubmission $externalProjectSubmission): bool
    // {
    //     return $user->hasPermissionTo('external-submission.delete');
    // }
}

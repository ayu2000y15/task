<?php

namespace App\Policies;

use App\Models\BoardPost;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoardPostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('boards.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BoardPost $post): bool
    {
        // 管理権限があれば常に閲覧可能
        if ($user->hasPermissionTo('boards.manage')) {
            return true;
        }

        // 投稿者本人は常に閲覧可能
        if ($user->id === $post->user_id) {
            return true;
        }

        // 閲覧範囲が指定されていない（ロールもユーザーも未指定）場合は全公開
        if (is_null($post->role_id) && $post->readableUsers()->doesntExist()) {
            return true;
        }

        // ユーザーが投稿の閲覧範囲ロールに属しているかチェック
        if ($post->role_id && $user->roles->contains('id', $post->role_id)) {
            return true;
        }

        // ユーザーが閲覧許可ユーザーとして個別に指定されているかチェック
        if ($post->readableUsers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('boards.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BoardPost $boardPost): bool
    {
        // 管理権限があれば更新可能
        if ($user->hasPermissionTo('boards.update')) {
            return true;
        }
        // 投稿者本人のみ更新可能
        return $user->id === $boardPost->user_id && $user->hasPermissionTo('boards.create');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BoardPost $boardPost): bool
    {
        // 管理権限があれば削除可能
        if ($user->hasPermissionTo('boards.delete')) {
            return true;
        }
        // 投稿者本人のみ削除可能
        return $user->id === $boardPost->user_id;
    }
}

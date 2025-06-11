<?php

namespace App\Policies;

use App\Models\BoardComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoardCommentPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BoardComment $comment): bool
    {
        // ユーザーがコメントの投稿者である場合のみ許可
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, BoardComment $comment): bool
    {
        // ユーザーがコメントの投稿者である場合のみ許可
        return $user->id === $comment->user_id;
    }
}

<?php

namespace App\Policies;

use App\Models\ShiftChangeRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShiftChangeRequestPolicy
{
    /**
     * 承認者などがシフト変更申請の一覧を閲覧できるか決定する。
     */
    public function viewAny(User $user): bool
    {
        // 'approve' 権限を持つユーザー（承認者）に一覧の閲覧を許可
        return $this->approve($user);
    }

    /**
     * ユーザーが特定のシフト変更申請を閲覧できるか決定する。
     */
    public function view(User $user, ShiftChangeRequest $shiftChangeRequest): bool
    {
        // 申請者本人、または承認権限を持つユーザーは閲覧可能
        return $user->id === $shiftChangeRequest->user_id || $this->approve($user);
    }

    /**
     * ユーザーがシフト変更申請を作成できるか決定する。
     */
    public function create(User $user): bool
    {
        // ログインしているユーザーであれば誰でも申請を作成可能
        return true;
    }

    /**
     * ユーザーが申請を削除（取り下げ）できるか決定する。
     */
    public function delete(User $user, ShiftChangeRequest $shiftChangeRequest): bool
    {
        // 申請者本人が、かつステータスが 'pending' (保留中) の場合にのみ削除（取り下げ）を許可
        return $user->id === $shiftChangeRequest->user_id && $shiftChangeRequest->status === 'pending';
    }

    /**
     * ユーザーがシフト変更申請を承認/否認できるか決定する。
     * このメソッドは'viewAny'や'view'からも呼び出される基幹的な権限です。
     */
    public function approve(User $user): bool
    {
        // 例：'shifts.manage'という権限を持つユーザーに承認/否認を許可
        // この権限は事前にRoles & Permissionsで設定しておく必要があります。
        return $user->hasPermissionTo('shifts.manage');
    }
}

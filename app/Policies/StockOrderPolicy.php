<?php

namespace App\Policies;

use App\Models\StockOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StockOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('stock_orders.viewAny') || $user->hasPermissionTo('stock_orders.view_own');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockOrder $stockOrder): bool
    {
        // 自分の申請、または管理者は閲覧可能など
        if ($user->hasPermissionTo('stock_orders.viewAny')) {
            return true;
        }
        return $user->id === $stockOrder->requested_by_user_id && $user->hasPermissionTo('stock_orders.view_own');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // 在庫が少ない場合に誰でも申請できるか、特定の権限を持つユーザーのみか
        return $user->hasPermissionTo('stock_orders.create');
    }

    /**
     * Determine whether the user can update the model.
     * 通常、申請自体の内容変更は制限されることが多い。ステータス更新は別メソッドで。
     */
    public function update(User $user, StockOrder $stockOrder): bool
    {
        // 例: 申請者本人かつステータスが 'pending' の場合のみ編集許可
        // if ($user->id === $stockOrder->requested_by_user_id && $stockOrder->status === 'pending') {
        //    return $user->hasPermissionTo('stock_orders.update_own');
        // }
        return $user->hasPermissionTo('stock_orders.update'); // 管理者用の一般更新権限
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockOrder $stockOrder): bool
    {
        // 例: 申請者本人かつステータスが 'pending' の場合のみ削除許可
        // if ($user->id === $stockOrder->requested_by_user_id && $stockOrder->status === 'pending') {
        //     return $user->hasPermissionTo('stock_orders.delete_own');
        // }
        return $user->hasPermissionTo('stock_orders.delete'); // 管理者用の一般削除権限
    }

    /**
     * Determine whether the user can update the status of the model.
     */
    public function updateStatus(User $user, StockOrder $stockOrder): bool
    {
        // 承認、却下、発注済、入荷済などのステータス変更権限
        return $user->hasPermissionTo('stock_orders.manage_status');
    }
}

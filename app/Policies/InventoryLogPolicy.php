<?php

namespace App\Policies;

use App\Models\InventoryLog;
use App\Models\User;

class InventoryLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // 在庫品目閲覧権限を持つユーザーはログも見れるようにするなど
        return $user->hasPermissionTo('inventory_items.viewAny');
        // もしくは専用の 'inventory_logs.viewAny' パーミッション
        // return $user->hasPermissionTo('inventory_logs.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryLog $inventoryLog): bool
    {
        return $user->hasPermissionTo('inventory_items.viewAny');
        // return $user->hasPermissionTo('inventory_logs.viewAny');
    }

    // InventoryLog は通常ユーザーが直接作成・更新・削除しないため、
    // create, update, delete メソッドは false を返すか、
    // 特定の管理者のみに許可する（あるいはメソッド自体を定義しない）。
    public function create(User $user): bool
    {
        return false; // 通常、ログはシステムが自動生成
    }

    public function update(User $user, InventoryLog $inventoryLog): bool
    {
        return false; // ログは改変不可
    }

    public function delete(User $user, InventoryLog $inventoryLog): bool
    {
        return $user->hasPermissionTo('inventory_logs.delete'); // 管理者のみログ削除を許可する場合など
    }
}

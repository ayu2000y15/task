<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InventoryItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory_items.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        // viewAny権限があれば個別の閲覧も許可する場合
        return $user->hasPermissionTo('inventory_items.viewAny');
        // もしくは、より詳細な 'inventory_items.view' パーミッションを定義
        // return $user->hasPermissionTo('inventory_items.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventory_items.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasPermissionTo('inventory_items.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        // 在庫が0の場合のみ削除可能、などのビジネスロジックをここに追加することもできる
        // if ($inventoryItem->quantity > 0) {
        //     return Response::deny('在庫が残っているため削除できません。');
        // }
        return $user->hasPermissionTo('inventory_items.delete');
    }

    /**
     * Determine whether the user can perform stock in (入荷処理).
     */
    public function stockIn(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasPermissionTo('inventory_items.manage_stock'); // 在庫操作権限
    }

    /**
     * Determine whether the user can adjust stock (在庫調整).
     */
    public function adjustStock(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasPermissionTo('inventory_items.manage_stock'); // 在庫操作権限
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, InventoryItem $inventoryItem): bool
    // {
    //     return $user->hasPermissionTo('inventory_items.update'); // 例
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, InventoryItem $inventoryItem): bool
    // {
    //     return $user->hasPermissionTo('inventory_items.delete'); // 例
    // }
}

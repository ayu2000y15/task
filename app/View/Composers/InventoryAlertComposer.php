<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;

class InventoryAlertComposer
{
    public function compose(View $view)
    {
        $hasInventoryAlerts = false;
        if (Auth::check() && Auth::user()->can('viewAny', InventoryItem::class)) { // 在庫閲覧権限がある場合のみ
            $hasInventoryAlerts = InventoryItem::where(function ($query) {
                $query->whereRaw('quantity <= 0 AND minimum_stock_level > 0'); // 在庫切れ
            })
                ->orWhere(function ($query) {
                    $query->whereRaw('quantity < minimum_stock_level AND minimum_stock_level > 0 AND quantity > 0'); // 在庫僅少
                })
                ->exists();
        }
        $view->with('hasInventoryAlertsGlobal', $hasInventoryAlerts);
    }
}

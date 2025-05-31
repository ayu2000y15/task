<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\StockOrder;
use Illuminate\Support\Facades\Auth;

class PendingStockOrdersComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $pendingStockOrdersCount = 0;
        if (Auth::check()) {
            // viewAny権限があるユーザーにのみ表示するなどの考慮も可能
            if (Auth::user()->can('viewAny', StockOrder::class)) {
                $pendingStockOrdersCount = StockOrder::where('status', 'pending')->count();
            }
        }
        $view->with('pendingStockOrdersCountGlobal', $pendingStockOrdersCount);
    }
}

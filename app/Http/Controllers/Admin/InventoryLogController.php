<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\User;

class InventoryLogController extends Controller
{
    /**
     * Display a listing of the inventory logs.
     */ public function index(Request $request)
    {
        $query = InventoryLog::with(['inventoryItem', 'user', 'material', 'stockOrder'])->latest();

        // フィルター機能
        if ($request->filled('inventory_item_name')) { // ビューの name="inventory_item_name" に対応
            $query->whereHas('inventoryItem', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('inventory_item_name') . '%');
            });
        }
        if ($request->filled('user_name')) { // ビューの name="user_name" に対応
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('user_name') . '%');
            });
        }
        if ($request->filled('change_type')) { // ビューの name="change_type" に対応
            $query->where('change_type', $request->input('change_type'));
        }
        if ($request->filled('date_from')) { // ビューの name="date_from" に対応
            $query->where('created_at', '>=', $request->input('date_from') . ' 00:00:00');
        }
        if ($request->filled('date_to')) { // ビューの name="date_to" に対応
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }
        if ($request->filled('related_stock_order_id')) { // ビューの name="related_stock_order_id" に対応
            $query->where('related_stock_order_id', $request->input('related_stock_order_id'));
        }

        $inventoryLogs = $query->orderBy('created_at', 'desc')->take(200)->paginate(50)->appends($request->except('page'));

        // フィルター用の変動種別の選択肢 (ビューで直接記述しているものをコントローラーで定義する例)
        $changeTypesForFilter = [
            'stocked' => '入荷 (手動)', // `InventoryController@stockIn` で使用
            'used' => '使用 (材料として)',
            'adjusted' => '在庫調整',
            'order_received' => '発注からの入荷', // `StockOrderController@updateStatus` で使用
            // 他の change_type があれば追加
        ];
        // 他のフィルター用選択肢も必要ならここで準備
        // $inventoryItemsForFilter = InventoryItem::orderBy('name')->pluck('name', 'id');
        // $usersForFilter = User::orderBy('name')->pluck('name', 'id');


        return view('admin.inventory_logs.index', compact('inventoryLogs', 'changeTypesForFilter'));
    }

    /**
     * Display the specified inventory log.
     * (通常、ログの詳細表示は一覧に含めるか、モーダルで表示することが多い)
     */
    // public function show(InventoryLog $inventoryLog)
    // {
    //     if (!Gate::allows('view', $inventoryLog)) {
    //         abort(403);
    //     }
    //     $inventoryLog->load(['inventoryItem', 'user', 'material', 'stockOrder']);
    //     return view('admin.inventory_logs.show', compact('inventoryLog'));
    // }

    // InventoryLog の作成、更新、削除メソッドは通常コントローラーには実装しません。
    // これらは他のコントローラー (InventoryController, MaterialController, StockOrderControllerなど) の
    // アクション内で InventoryLog::create() によって記録されます。
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockOrder;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StockOrderController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('viewAny', StockOrder::class)) {
            abort(403);
        }

        $query = StockOrder::with(['inventoryItem', 'requestedByUser', 'managedByUser'])->latest();

        // フィルター機能
        if ($request->filled('inventory_item_name')) { // ビューの name="inventory_item_name" に対応
            $query->whereHas('inventoryItem', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('inventory_item_name') . '%');
            });
        }
        if ($request->filled('requested_by_user_name')) { // ビューの name="requested_by_user_name" に対応
            $query->whereHas('requestedByUser', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('requested_by_user_name') . '%');
            });
        }
        if ($request->filled('status')) { // ビューの name="status" に対応
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date_from')) { // ビューの name="date_from" に対応
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) { // ビューの name="date_to" に対応
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        // ★ 関連発注IDでのフィルターは StockOrder 自身には不要なので削除（他のログ等で使う場合）

        $stockOrders = $query->paginate(20)->appends($request->except('page'));
        $statusOptions = StockOrder::STATUS_OPTIONS; // ビューのステータス選択肢用

        return view('admin.stock_orders.index', compact('stockOrders', 'statusOptions'));
    }

    public function create(Request $request)
    {
        if (!Gate::allows('create', StockOrder::class)) {
            abort(403);
        }
        $inventoryItemId = $request->input('inventory_item_id');
        $inventoryItem = null;
        if ($inventoryItemId) {
            $inventoryItem = InventoryItem::find($inventoryItemId);
        }
        $inventoryItems = InventoryItem::orderBy('name')->get(); // 申請対象の在庫品目リスト

        return view('admin.stock_orders.create', compact('inventoryItems', 'inventoryItem'));
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create', StockOrder::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity_requested' => 'required|numeric|min:0.01', // 0より大きい値
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        $stockOrder = new StockOrder($validated);
        $stockOrder->requested_by_user_id = Auth::id();
        $stockOrder->status = 'pending'; // 初期ステータス
        $stockOrder->save(); // LogsActivity が発火

        return redirect()->route('admin.stock-orders.index')->with('success', '在庫発注申請を作成しました。');
    }

    public function show(StockOrder $stockOrder)
    {
        if (!Gate::allows('view', $stockOrder)) {
            abort(403);
        }
        $stockOrder->load(['inventoryItem', 'requestedByUser', 'managedByUser']);
        $statusOptions = StockOrder::STATUS_OPTIONS;
        $users = User::orderBy('name')->get(); // 対応者選択用

        return view('admin.stock_orders.show', compact('stockOrder', 'statusOptions', 'users'));
    }

    public function edit(StockOrder $stockOrder)
    {
        // 通常、申請内容の「編集」は限定的。ステータス変更が主。
        // ここでは show にリダイレクトするか、管理者による備考編集などのUIを提供。
        if (!Gate::allows('update', $stockOrder)) {
            abort(403);
        }
        // return redirect()->route('admin.stock-orders.show', $stockOrder);
        // もし編集画面を作るなら
        $inventoryItems = InventoryItem::orderBy('name')->get();
        return view('admin.stock_orders.edit', compact('stockOrder', 'inventoryItems'));
    }

    public function update(Request $request, StockOrder $stockOrder)
    {
        // 申請内容自体の更新ロジック (限定的であるべき)
        if (!Gate::allows('update', $stockOrder)) {
            abort(403);
        }

        $validated = $request->validate([
            // 'quantity_requested' => 'sometimes|required|numeric|min:0.01', // 数量変更を許可する場合
            'expected_delivery_date' => 'sometimes|nullable|date|after_or_equal:today',
            'notes' => 'sometimes|nullable|string|max:1000', // 申請者による備考更新
            'manager_notes' => 'sometimes|nullable|string|max:1000', // 管理者による備考更新
        ]);

        // 申請内容の変更はステータスによって制限することが望ましい
        if ($stockOrder->status !== 'pending' && ($request->has('quantity_requested') || $request->has('notes'))) {
            return back()->with('error', '申請中以外の申請内容は変更できません。')->withInput();
        }

        $stockOrder->update($validated); // LogsActivity が発火

        return redirect()->route('admin.stock-orders.show', $stockOrder)->with('success', '発注申請情報を更新しました。');
    }


    public function destroy(StockOrder $stockOrder)
    {
        if (!Gate::allows('delete', $stockOrder)) {
            abort(403);
        }
        // 削除条件（例：申請中のみ削除可能など）
        if (!in_array($stockOrder->status, ['pending', 'rejected', 'cancelled'])) {
            return back()->with('error', 'このステータスの申請は削除できません。');
        }
        $stockOrder->delete(); // LogsActivity が発火
        return redirect()->route('admin.stock-orders.index')->with('success', '発注申請を削除しました。');
    }

    // ステータス更新用の専用メソッド
    public function updateStatus(Request $request, StockOrder $stockOrder)
    {
        if (!Gate::allows('updateStatus', $stockOrder)) { // StockOrderPolicy@updateStatus を使用
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(StockOrder::STATUS_OPTIONS))],
            'manager_notes' => 'nullable|string|max:1000', // 管理者メモも一緒に更新できるように
            'expected_delivery_date_on_status_change' => 'nullable|date|required_if:status,ordered|after_or_equal:today', // 発注時に納期を設定
        ]);

        $oldStatus = $stockOrder->status;
        $newStatus = $validated['status'];

        $stockOrder->status = $newStatus;
        $stockOrder->managed_by_user_id = Auth::id();
        $stockOrder->managed_at = now();
        if ($request->filled('manager_notes')) {
            $stockOrder->manager_notes = $validated['manager_notes'];
        }
        if ($newStatus === 'ordered' && $request->filled('expected_delivery_date_on_status_change')) {
            $stockOrder->expected_delivery_date = $validated['expected_delivery_date_on_status_change'];
        }


        // 「入荷済(received)」になったら、対応する在庫品目の在庫数を増やす
        if ($newStatus === 'received' && $oldStatus !== 'received') {
            $inventoryItem = $stockOrder->inventoryItem;
            if ($inventoryItem) {
                $inventoryItem->increment('quantity', $stockOrder->quantity_requested);
                $inventoryItem->last_stocked_at = now();
                $inventoryItem->save();

                // 在庫ログも記録
                activity()
                    ->performedOn($inventoryItem) // InventoryItem を対象
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'change_type' => 'stocked_from_order',
                        'quantity_change' => $stockOrder->quantity_requested,
                        'stock_order_id' => $stockOrder->id,
                        'new_quantity' => $inventoryItem->quantity
                    ])
                    ->log("発注申請 (ID: {$stockOrder->id}) により、{$inventoryItem->name} が {$stockOrder->quantity_requested}{$inventoryItem->unit} 入荷されました。");
            }
        }

        $stockOrder->save(); // StockOrder の LogsActivity が発火 (status, manager_notes などの変更)

        // ★ ステータス変更のログを別途手動で記録 (より詳細な情報と共に)
        activity()
            ->causedBy(Auth::user())
            ->performedOn($stockOrder)
            ->withProperties(['old_status' => $oldStatus, 'new_status' => $newStatus, 'manager_notes' => $stockOrder->manager_notes])
            ->log("発注申請 (ID: {$stockOrder->id}, 品目: {$stockOrder->inventoryItem->name}) のステータスが「" . StockOrder::STATUS_OPTIONS[$oldStatus] . "」から「" . StockOrder::STATUS_OPTIONS[$newStatus] . "」に変更されました。");


        return redirect()->route('admin.stock-orders.show', $stockOrder)->with('success', '申請ステータスを更新しました。');
    }
}

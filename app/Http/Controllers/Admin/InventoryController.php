<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth; // Authファサードを追加
use App\Models\InventoryLog;      // InventoryLogモデルを追加
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        if (!Gate::allows('viewAny', InventoryItem::class)) {
            abort(403);
        }
        $inventoryItems = InventoryItem::orderBy('name')->paginate(20);
        return view('admin.inventory.index', compact('inventoryItems'));
    }

    public function create()
    {
        if (!Gate::allows('create', InventoryItem::class)) {
            abort(403);
        }
        return view('admin.inventory.create');
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create', InventoryItem::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0', // ★ total_cost のバリデーション追加
            'minimum_stock_level' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'last_stocked_at' => 'nullable|date',
        ]);

        // total_cost が入力されていない場合は0をセット
        $validated['total_cost'] = $validated['total_cost'] ?? 0;

        $inventoryItem = InventoryItem::create($validated);

        // ★ 初期在庫登録に伴うInventoryLogの記録 (任意)
        if ($inventoryItem->quantity > 0) {
            InventoryLog::create([
                'inventory_item_id' => $inventoryItem->id,
                'user_id' => Auth::id(), // 操作者ID
                'change_type' => 'initial_stock', // 変動種別: 初期在庫
                'quantity_change' => $inventoryItem->quantity, // 変動量は初期在庫数
                'quantity_before_change' => 0, // 初期登録なので変動前は0
                'quantity_after_change' => $inventoryItem->quantity, // 変動後は初期在庫数
                'unit_price_at_change' => $inventoryItem->quantity > 0 ? ($inventoryItem->total_cost / $inventoryItem->quantity) : 0, // 初期平均単価
                'total_price_at_change' => $inventoryItem->total_cost, // 初期総コスト
                'notes' => '新規在庫品目登録による初期在庫設定',
            ]);
        }


        return redirect()->route('admin.inventory.index')->with('success', '在庫品目を登録しました。');
    }

    public function show(InventoryItem $inventoryItem)
    {
        if (!Gate::allows('view', $inventoryItem)) {
            abort(403);
        }
        return redirect()->route('admin.inventory.edit', $inventoryItem);
    }

    public function edit(InventoryItem $inventoryItem)
    {
        if (!Gate::allows('update', $inventoryItem)) {
            abort(403);
        }
        return view('admin.inventory.edit', compact('inventoryItem'));
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        if (!Gate::allows('update', $inventoryItem)) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name,' . $inventoryItem->id,
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'minimum_stock_level' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'last_stocked_at' => 'nullable|date',
        ]);
        // 'quantity' はこのフォームでは直接更新しない
        $inventoryItem->update($validated);
        return redirect()->route('admin.inventory.edit', $inventoryItem)->with('success', '在庫品目を更新しました。');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        if (!Gate::allows('delete', $inventoryItem)) {
            abort(403);
        }
        // TODO: 関連する StockOrder がある場合の処理（削除を許可しない、または関連を解除するなど）
        $inventoryItem->delete();
        return redirect()->route('admin.inventory.index')->with('success', '在庫品目を削除しました。');
    }

    /**
     * 在庫の入荷処理
     */
    public function stockIn(Request $request, InventoryItem $inventoryItem)
    {
        if (!Gate::allows('stockIn', $inventoryItem)) {
            abort(403, 'この在庫品目の入荷処理を行う権限がありません。');
        }

        // 入荷数量と、入荷単価または入荷総額のどちらかを受け取る
        $validated = $request->validateWithBag('stockIn', [
            'quantity_in' => 'required|numeric|min:0.01', // name属性を quantity_in に変更する想定
            'unit_price_in' => 'nullable|numeric|min:0|required_without:total_price_in', // 単価
            'total_price_in' => 'nullable|numeric|min:0|required_without:unit_price_in', // 総額
            'notes' => 'nullable|string|max:1000',
        ]);

        $quantityIn = floatval($validated['quantity_in']);
        $unitPriceIn = null;
        $totalPriceIn = null;

        if (isset($validated['unit_price_in'])) {
            $unitPriceIn = floatval($validated['unit_price_in']);
            $totalPriceIn = $unitPriceIn * $quantityIn;
        } elseif (isset($validated['total_price_in'])) {
            $totalPriceIn = floatval($validated['total_price_in']);
            $unitPriceIn = $quantityIn > 0 ? $totalPriceIn / $quantityIn : 0;
        }

        if ($totalPriceIn === null) { // どちらも指定されなかった場合のフォールバック(バリデーションで防がれるはずだが念のため)
            return back()->withErrors(['unit_price_in' => '単価または総額のいずれかを入力してください。'], 'stockIn')->withInput();
        }

        DB::beginTransaction();
        try {
            $oldQuantity = $inventoryItem->quantity;
            $oldTotalCost = $inventoryItem->total_cost;

            // 新しい総コストと総数量を計算 (移動平均法)
            $newTotalCost = $oldTotalCost + $totalPriceIn;
            $newQuantity = $oldQuantity + $quantityIn;

            $inventoryItem->quantity = $newQuantity;
            $inventoryItem->total_cost = $newTotalCost;
            $inventoryItem->last_stocked_at = now();
            $inventoryItem->save();

            InventoryLog::create([
                'inventory_item_id' => $inventoryItem->id,
                'user_id' => Auth::id(),
                'change_type' => 'stocked_manual', // 手動入荷（発注申請経由の場合は別のchange_typeが良いかも）
                'quantity_change' => $quantityIn,
                'quantity_before_change' => $oldQuantity,
                'quantity_after_change' => $newQuantity,
                'unit_price_at_change' => $unitPriceIn, // 今回入荷したものの単価
                'total_price_at_change' => $totalPriceIn, // 今回入荷したものの総額
                'notes' => $validated['notes'] ?? "手動入荷処理",
            ]);

            DB::commit();

            activity()
                ->performedOn($inventoryItem)
                ->causedBy(Auth::user())
                ->withProperties([
                    'action' => 'stock_in_manual',
                    'quantity_added' => $quantityIn,
                    'unit_price_in' => $unitPriceIn,
                    'total_price_in' => $totalPriceIn,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'old_total_cost' => $oldTotalCost,
                    'new_total_cost' => $newTotalCost,
                    'notes' => $validated['notes']
                ])
                ->log("在庫品目「{$inventoryItem->name}」に {$quantityIn}{$inventoryItem->unit} (単価:{$unitPriceIn} / 総額:{$totalPriceIn}) 入荷されました。");

            return redirect()->route('admin.inventory.edit', $inventoryItem)->with('success', "{$quantityIn}{$inventoryItem->unit} の入荷処理が完了しました。");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Stock in failed: ' . $e->getMessage());
            return back()->with('error', '入荷処理中にエラーが発生しました。' . $e->getMessage())->withInput();
        }
    }

    /**
     * 在庫数の調整処理
     */
    public function adjustStock(Request $request, InventoryItem $inventoryItem)
    {
        if (!Gate::allows('adjustStock', $inventoryItem)) {
            abort(403, 'この在庫品目の在庫調整を行う権限がありません。');
        }

        $validated = $request->validateWithBag('adjustStock', [ // エラーバッグ名を指定
            'adjustment_type' => ['required', Rule::in(['new_total', 'change_amount'])],
            'adjustment_value' => 'required|numeric', // step="0.01" を考慮するなら numeric
            'notes' => 'required|string|max:1000',
        ]);

        $oldQuantity = $inventoryItem->quantity;
        $adjustmentType = $validated['adjustment_type'];
        $adjustmentValue = floatval($validated['adjustment_value']); // 小数点も扱えるように floatval を使用
        $newQuantity = 0;

        if ($adjustmentType === 'new_total') {
            $newQuantity = $adjustmentValue;
            if ($newQuantity < 0) {
                return back()->withErrors(['adjustment_value' => '調整後の総在庫数は0以上である必要があります。'], 'adjustStock')->withInput();
            }
        } elseif ($adjustmentType === 'change_amount') {
            $newQuantity = $oldQuantity + $adjustmentValue;
            if ($newQuantity < 0) {
                // 減らした結果マイナスになる場合はエラーとするか、0にするかなどの仕様を決める
                return back()->withErrors(['adjustment_value' => '在庫数がマイナスになるような増減はできません。現在の在庫数: ' . $oldQuantity], 'adjustStock')->withInput();
            }
        } else {
            // 万が一、未知のadjustment_typeが来た場合
            return back()->with('error', '不明な調整タイプです。')->withInput();
        }

        $quantityChange = $newQuantity - $oldQuantity;

        $inventoryItem->quantity = $newQuantity;
        $inventoryItem->save();

        InventoryLog::create([
            'inventory_item_id' => $inventoryItem->id,
            'user_id' => Auth::id(),
            'change_type' => 'adjusted',
            'quantity_change' => $quantityChange,
            'quantity_before_change' => $oldQuantity,
            'quantity_after_change' => $newQuantity,
            'notes' => $validated['notes'],
        ]);

        activity()
            ->performedOn($inventoryItem)
            ->causedBy(Auth::user())
            ->withProperties([
                'action' => 'stock_adjustment',
                'adjustment_type' => $adjustmentType,
                'adjustment_value_input' => $adjustmentValue,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'quantity_change' => $quantityChange,
                'reason' => $validated['notes']
            ])
            ->log("在庫品目「{$inventoryItem->name}」(ID:{$inventoryItem->id}) の在庫数が調整されました。方法: {$adjustmentType}, 入力値: {$adjustmentValue}, 結果: {$oldQuantity}{$inventoryItem->unit} → {$newQuantity}{$inventoryItem->unit}。理由: {$validated['notes']}");

        return redirect()->route('admin.inventory.edit', $inventoryItem)->with('success', '在庫数の調整が完了しました。');
    }
}

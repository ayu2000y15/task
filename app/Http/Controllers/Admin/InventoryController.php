<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Authファサードを追加
use App\Models\InventoryLog;      // InventoryLogモデルを追加
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        // 検索機能
        if ($request->filled('inventory_item_name')) {
            $query->where('name', 'like', '%' . $request->input('inventory_item_name') . '%');
        }
        if ($request->filled('product_number')) { // ★品番での絞り込み
            $query->where('product_number', 'like', '%' . $request->input('product_number') . '%');
        }
        if ($request->filled('color_number')) { // ★色番での絞り込み
            $query->where('color_number', 'like', '%' . $request->input('color_number') . '%');
        }
        if ($request->filled('supplier')) {
            $query->where('supplier', 'like', '%' . $request->input('supplier') . '%');
        }
        if ($request->filled('stock_status')) {
            $status = $request->input('stock_status');
            if ($status === 'out') {
                $query->where('quantity', '<=', 0);
            } elseif ($status === 'low') {
                $query->whereColumn('quantity', '<', 'minimum_stock_level')->where('quantity', '>', 0);
            } elseif ($status === 'ok') {
                $query->whereColumn('quantity', '>=', 'minimum_stock_level');
            }
        }

        $inventoryItems = $query->orderBy('name')->paginate(20)->appends($request->except('page'));

        return view('admin.inventory.index', compact('inventoryItems'));
    }

    public function create()
    {

        return view('admin.inventory.create');
    }

    // 一括登録フォーム表示
    public function bulkCreate()
    {
        return view('admin.inventory.bulk-create');
    }

    // 一括登録処理
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'base_name' => 'required|string|max:255',
            'product_number' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'minimum_stock_level' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'last_stocked_at' => 'nullable|date',
            'variants' => 'required|array|min:1|max:20', // 最大20件まで
            'variants.*.color_number' => 'nullable|string|max:255',
            'variants.*.quantity' => 'required|numeric|min:0',
            'variants.*.total_cost' => 'nullable|numeric|min:0',
        ], [
            'variants.required' => 'バリエーションを最低1つは入力してください。',
            'variants.min' => 'バリエーションを最低1つは入力してください。',
            'variants.max' => 'バリエーションは最大20件まで登録できます。',
            'variants.*.quantity.required' => '在庫数は必須です。',
            'variants.*.quantity.numeric' => '在庫数は数値で入力してください。',
            'variants.*.quantity.min' => '在庫数は0以上で入力してください。',
        ]);

        DB::beginTransaction();
        try {
            $createdItems = [];

            foreach ($validated['variants'] as $index => $variant) {
                // 品番と色番の組み合わせが既存のものと重複していないかチェック
                $existingItem = InventoryItem::where('name', $validated['base_name'])
                    ->where('product_number', $validated['product_number'])
                    ->where('color_number', $variant['color_number'])
                    ->first();

                if ($existingItem) {
                    throw new \Exception("品名「{$validated['base_name']}」、品番「{$validated['product_number']}」、色番「{$variant['color_number']}」の組み合わせは既に登録されています。");
                }

                // 在庫品目を作成
                $itemData = [
                    'name' => $validated['base_name'],
                    'product_number' => $validated['product_number'],
                    'color_number' => $variant['color_number'],
                    'description' => $validated['description'],
                    'unit' => $validated['unit'],
                    'quantity' => $variant['quantity'],
                    'total_cost' => $variant['total_cost'] ?? 0,
                    'minimum_stock_level' => $validated['minimum_stock_level'],
                    'supplier' => $validated['supplier'],
                    'last_stocked_at' => $validated['last_stocked_at'],
                ];

                $inventoryItem = InventoryItem::create($itemData);
                $createdItems[] = $inventoryItem;

                // 初期在庫登録に伴うInventoryLogの記録
                if ($inventoryItem->quantity > 0) {
                    InventoryLog::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => Auth::id(),
                        'change_type' => 'initial_stock',
                        'quantity_change' => $inventoryItem->quantity,
                        'quantity_before_change' => 0,
                        'quantity_after_change' => $inventoryItem->quantity,
                        'unit_price_at_change' => $inventoryItem->quantity > 0 ? ($inventoryItem->total_cost / $inventoryItem->quantity) : 0,
                        'total_price_at_change' => $inventoryItem->total_cost,
                        'notes' => '一括登録による初期在庫設定',
                    ]);
                }

                // アクティビティログ
                activity()
                    ->performedOn($inventoryItem)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'action' => 'bulk_create',
                        'batch_index' => $index + 1,
                        'total_items' => count($validated['variants'])
                    ])
                    ->log("在庫品目「{$inventoryItem->name}」（品番: {$inventoryItem->product_number}, 色番: {$inventoryItem->color_number}）が一括登録されました。");
            }

            DB::commit();

            return redirect()->route('admin.inventory.index')
                ->with('success', count($createdItems) . '件の在庫品目を一括登録しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk inventory creation failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e
            ]);
            return back()->with('error', '一括登録中にエラーが発生しました: ' . $e->getMessage())->withInput();
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_number' => 'nullable|string|max:255',
            'color_number' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'minimum_stock_level' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'last_stocked_at' => 'nullable|date',
        ]);

        $validated['total_cost'] = $validated['total_cost'] ?? 0;

        $inventoryItem = InventoryItem::create($validated);

        if ($inventoryItem->quantity > 0) {
            InventoryLog::create([
                'inventory_item_id' => $inventoryItem->id,
                'user_id' => Auth::id(),
                'change_type' => 'initial_stock',
                'quantity_change' => $inventoryItem->quantity,
                'quantity_before_change' => 0,
                'quantity_after_change' => $inventoryItem->quantity,
                'unit_price_at_change' => $inventoryItem->quantity > 0 ? ($inventoryItem->total_cost / $inventoryItem->quantity) : 0,
                'total_price_at_change' => $inventoryItem->total_cost,
                'notes' => '新規在庫品目登録による初期在庫設定',
            ]);
        }

        return redirect()->route('admin.inventory.index')->with('success', '在庫品目を登録しました。');
    }

    public function show(InventoryItem $inventoryItem)
    {

        return redirect()->route('admin.inventory.edit', $inventoryItem);
    }

    public function edit(InventoryItem $inventoryItem)
    {

        return view('admin.inventory.edit', compact('inventoryItem'));
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255,' . $inventoryItem->id,
            'product_number' => 'nullable|string|max:255',
            'color_number' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'total_cost' => 'nullable|numeric|min:0', // ★ total_cost をバリデーション
            'minimum_stock_level' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'last_stocked_at' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $oldTotalCost = $inventoryItem->total_cost;
            $newTotalCostFromRequest = $request->filled('total_cost') ? floatval($validated['total_cost']) : null;

            // total_cost 以外の品目情報をまず更新
            $inventoryItem->fill(array_filter($validated, function ($key) {
                return $key !== 'total_cost'; // total_cost は後で特別に処理
            }, ARRAY_FILTER_USE_KEY));

            $logProperties = [
                'action' => 'item_info_update',
                'old_values' => [],
                'new_values' => [],
            ];

            // 変更があったフィールドをログ用に収集 (total_cost以外)
            foreach ($inventoryItem->getDirty() as $attribute => $newValue) {
                $logProperties['old_values'][$attribute] = $inventoryItem->getOriginal($attribute);
                $logProperties['new_values'][$attribute] = $newValue;
            }

            // total_cost がリクエストに含まれており、かつ既存の値と異なる場合のみ更新・ログ記録
            if ($newTotalCostFromRequest !== null && (string)$newTotalCostFromRequest !== (string)$oldTotalCost) {
                $inventoryItem->total_cost = $newTotalCostFromRequest;

                // ログプロパティに総コストの変更も追加
                $logProperties['old_values']['total_cost'] = $oldTotalCost;
                $logProperties['new_values']['total_cost'] = $newTotalCostFromRequest;
                $logProperties['action'] = 'item_info_and_total_cost_manual_update'; // アクション名を変更

                // 在庫変動ログにも記録 (在庫数は変動しないが、コストが変わった記録として)
                InventoryLog::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'user_id' => Auth::id(),
                    'change_type' => 'cost_adjusted_manual', // 新しい種別
                    'quantity_change' => 0, // 在庫数変動なし
                    'quantity_before_change' => $inventoryItem->quantity,
                    'quantity_after_change' => $inventoryItem->quantity,
                    'unit_price_at_change' => $inventoryItem->quantity > 0 ? ($newTotalCostFromRequest / $inventoryItem->quantity) : null, // 新しい平均単価の目安
                    'total_price_at_change' => $newTotalCostFromRequest - $oldTotalCost, // コストの変動額
                    'notes' => '品目情報編集画面から在庫総コストを手動修正。旧: ' . $oldTotalCost . ' 新: ' . $newTotalCostFromRequest,
                ]);
            } elseif ($newTotalCostFromRequest === null && $request->has('total_cost')) {
                // 明示的に空で送信された場合（例えば値を0にしたい場合など）
                // このケースを許可するかは要件による。ここでは0に設定する例。
                if ($oldTotalCost != 0) { // 既に0でなければ変更とみなす
                    $inventoryItem->total_cost = 0;
                    $logProperties['old_values']['total_cost'] = $oldTotalCost;
                    $logProperties['new_values']['total_cost'] = 0;
                    $logProperties['action'] = 'item_info_and_total_cost_manual_update';
                    InventoryLog::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => Auth::id(),
                        'change_type' => 'cost_adjusted_manual', // 新しい種別
                        'quantity_change' => 0, // 在庫数変動なし
                        'quantity_before_change' => $inventoryItem->quantity,
                        'quantity_after_change' => $inventoryItem->quantity,
                        'unit_price_at_change' => $inventoryItem->quantity > 0 ? ($newTotalCostFromRequest / $inventoryItem->quantity) : null, // 新しい平均単価の目安
                        'total_price_at_change' => $newTotalCostFromRequest - $oldTotalCost, // コストの変動額
                        'notes' => '品目情報編集画面から在庫総コストを手動修正。旧: ' . $oldTotalCost . ' 新: ' . $newTotalCostFromRequest,
                    ]);
                }
            }
            // total_cost がリクエストに含まれていない場合は、既存の値を維持する (fillで上書きされない)

            $inventoryItem->save(); // 全ての変更を保存

            // Spatie Activity Log (変更があった場合のみ)
            if (!empty($logProperties['old_values']) || !empty($logProperties['new_values'])) {
                // action を元にログメッセージを生成
                $logMessage = "在庫品目「{$inventoryItem->name}」の情報が更新されました。";
                if ($logProperties['action'] === 'item_info_and_total_cost_manual_update') {
                    $logMessage = "在庫品目「{$inventoryItem->name}」の情報及び総コストが手動で更新されました。";
                }

                activity()
                    ->performedOn($inventoryItem)
                    ->causedBy(Auth::user())
                    ->withProperties($logProperties)
                    ->log($logMessage);
            }


            DB::commit();
            return redirect()->route('admin.inventory.index', $inventoryItem)->with('success', '在庫品目を更新しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InventoryItem update failed: ' . $e->getMessage(), [
                'inventory_item_id' => $inventoryItem->id,
                'request_data' => $request->all(),
                'exception' => $e
            ]);
            return back()->with('error', '在庫品目の更新中にエラーが発生しました。詳細はサーバーログを確認してください。')->withInput();
        }
    }

    public function destroy(InventoryItem $inventoryItem)
    {

        // TODO: 関連する StockOrder がある場合の処理（削除を許可しない、または関連を解除するなど）
        $inventoryItem->delete();
        return redirect()->route('admin.inventory.index')->with('success', '在庫品目を削除しました。');
    }

    /**
     * 在庫の入荷処理
     */
    public function stockIn(Request $request, InventoryItem $inventoryItem)
    {

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

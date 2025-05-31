<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Material;
use App\Models\Character;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMaterials', $project);

        $isManualInput = $request->input('inventory_item_id') === 'manual_input' || empty($request->input('inventory_item_id'));

        $rules = [
            'inventory_item_id' => ['nullable', Rule::requiredIf(!$isManualInput), 'exists:inventory_items,id'],
            'name' => ['required', 'string', 'max:255'], // JS側で在庫品名 or 手入力名がここにセットされる
            'unit' => ['required', 'string', 'max:50'],  // JS側で在庫品単位 or 手入力単位がここにセットされる
            'price' => 'required|numeric|min:0',
            // unit_price_at_creation は必須だが、手入力の場合は price/qty で計算するので nullable にして後処理も可
            'unit_price_at_creation' => 'required|numeric|min:0',
            'quantity_needed' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'status' => ['required', Rule::in(['未購入', '購入済'])],
        ];

        // 手入力の場合、inventory_item_id の exists は不要
        if ($isManualInput) {
            unset($rules['inventory_item_id']); // inventory_item_id はバリデーション対象外、または 'nullable' のみ
        }


        $validated = $request->validate($rules);

        $statusToSet = $validated['status'];
        $quantityNeeded = floatval($validated['quantity_needed']);
        $inventoryItemId = $isManualInput ? null : $validated['inventory_item_id'];

        if ($statusToSet === '購入済' && $inventoryItemId) {
            $inventoryItem = InventoryItem::find($inventoryItemId);
            if ($inventoryItem && $inventoryItem->quantity < $quantityNeeded) {
                return response()->json([
                    'success' => false,
                    'message' => "在庫不足です。「{$inventoryItem->name}」の現在の在庫は {$inventoryItem->quantity}{$inventoryItem->unit} です。",
                    'errors' => ['quantity_needed' => ["在庫が不足しています。必要量: {$quantityNeeded}{$inventoryItem->unit} に対して、現在の在庫は {$inventoryItem->quantity}{$inventoryItem->unit} です。"]],
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $materialData = $validated;
            $materialData['character_id'] = $character->id;
            $materialData['inventory_item_id'] = $inventoryItemId; // 手入力なら null

            // unit_price_at_creation の再計算 (手入力で合計価格が入力された場合など)
            if ($isManualInput && isset($validated['price']) && $quantityNeeded > 0) {
                $materialData['unit_price_at_creation'] = round(floatval($validated['price']) / $quantityNeeded, 2);
            } elseif ($inventoryItemId) {
                // 在庫品の場合はJS側で設定された値 (data-avg_price) を使う
                // $materialData['unit_price_at_creation'] は $validated['unit_price_at_creation'] のまま
            } else {
                $materialData['unit_price_at_creation'] = 0; //念のため
            }


            $material = Material::create($materialData);

            if ($material->status === '購入済' && $material->inventory_item_id) { // 在庫品かつ購入済の場合のみ在庫処理
                $inventoryItem = InventoryItem::find($material->inventory_item_id);
                if ($inventoryItem) {
                    $oldInventoryQty = $inventoryItem->quantity;
                    // unit_price_at_creation は材料作成時の単価、なければ現在の平均単価
                    $unitPriceForCostDecrease = $material->unit_price_at_creation ?: $inventoryItem->average_unit_price;
                    $costToDecrease = $unitPriceForCostDecrease * $quantityNeeded;

                    $inventoryItem->decrement('quantity', $quantityNeeded);
                    $inventoryItem->decrement('total_cost', $costToDecrease);

                    InventoryLog::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => Auth::id(),
                        'change_type' => 'used_for_material',
                        'quantity_change' => -$quantityNeeded,
                        'quantity_before_change' => $oldInventoryQty,
                        'quantity_after_change' => $inventoryItem->quantity,
                        'related_material_id' => $material->id,
                        'unit_price_at_change' => $unitPriceForCostDecrease,
                        'total_price_at_change' => -$costToDecrease,
                        'notes' => "案件「{$project->title}」のキャラクター「{$character->name}」の材料「{$material->name}」として使用",
                    ]);
                }
            }

            if ($material->status === '購入済' && $material->price > 0) {
                $character->costs()->create([
                    'item_description' => $material->name,
                    'amount' => $material->price,
                    'type' => '材料費',
                    'cost_date' => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '材料を追加しました。',
                'material' => $material->load('inventoryItem'),
                'costs_updated' => ($material->status === '購入済' && $material->price > 0)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Material store failed: ' . $e->getMessage(), ['request_data' => $request->all(), 'exception' => $e]);
            return response()->json(['success' => false, 'message' => '材料の追加中にエラーが発生しました。詳細はサーバーログを確認してください。エラー: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project);
        if ($material->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        // ステータスのみの更新か(チェックボックスからのJSONリクエスト)、全項目更新か(フォームからのリクエスト)を判定
        // チェックボックスからの更新は Content-Type: application/json で status のみ含む
        $isStatusUpdateRequest = $request->isJson() && $request->has('status') && count($request->all()) === 1;

        // inventory_item_id が 'manual_input' または空の場合、手入力とみなす
        // この値はフォーム送信時にのみ関連し、ステータス更新リクエストでは通常送信されない
        $isManualInput = $request->input('inventory_item_id') === 'manual_input' || empty($request->input('inventory_item_id'));

        if ($isStatusUpdateRequest) {
            // ステータスのみの更新の場合
            $validated = $request->validate([
                'status' => ['required', Rule::in(['未購入', '購入済'])],
            ]);
        } else {
            // 全項目更新の場合 (フォームからの送信)
            $rules = [
                'inventory_item_id' => ['nullable', Rule::requiredIf(!$isManualInput), 'exists:inventory_items,id'],
                'name' => ['required', 'string', 'max:255'],
                'unit' => ['required', 'string', 'max:50'],
                'price' => 'required|numeric|min:0',
                'unit_price_at_creation' => 'required|numeric|min:0',
                'quantity_needed' => 'required|numeric|min:0.01',
                'notes' => 'nullable|string|max:1000',
                'status' => ['required', Rule::in(['未購入', '購入済'])],
            ];
            if ($isManualInput) { // 手入力の場合、inventory_item_id のバリデーションルールを調整
                unset($rules['inventory_item_id']);
            }
            $validated = $request->validate($rules);
        }

        // 更新後の各種値を取得 (ステータス更新のみの場合は既存値を維持)
        $newStatus = $validated['status'] ?? $material->status; // $validatedにstatusがあればそれを使用、なければ既存のstatus
        // ステータス更新時は inventory_item_id, quantity_needed はリクエストに含まれないため、既存の値を使用
        $newInventoryItemId = $isStatusUpdateRequest ? $material->inventory_item_id : ($isManualInput ? null : $validated['inventory_item_id']);
        $newQuantityNeeded = $isStatusUpdateRequest ? $material->quantity_needed : floatval($validated['quantity_needed']);


        // 在庫チェック (全項目更新時、またはステータスが「購入済」に変更され、かつ在庫品目がある場合)
        // ただし、ステータスのみの更新で「購入済」になる場合は、数量や品目は変わらない前提
        if ($newStatus === '購入済' && $newInventoryItemId) {
            $inventoryItem = InventoryItem::find($newInventoryItemId);
            if ($inventoryItem) {
                $effectiveAvailableStock = $inventoryItem->quantity;
                // もし更新対象の材料が元々「購入済」で同じ品目だった場合、その分の在庫は引当済みなので、計算上戻す
                if ($material->status === '購入済' && $material->inventory_item_id == $inventoryItem->id && !$isStatusUpdateRequest) {
                    // ただし、ステータスのみの更新では数量は変わらないので、この考慮は不要
                    $effectiveAvailableStock += floatval($material->quantity_needed);
                }

                if ($newQuantityNeeded > $effectiveAvailableStock) {
                    return response()->json([
                        'success' => false,
                        'message' => "在庫不足です。「{$inventoryItem->name}」の有効在庫は {$effectiveAvailableStock}{$inventoryItem->unit} です。",
                        'errors' => ['quantity_needed' => ["在庫が不足しています。必要量: {$newQuantityNeeded}{$inventoryItem->unit} に対して、有効在庫は {$effectiveAvailableStock}{$inventoryItem->unit} です。"]],
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $oldStatus = $material->status;
            $oldPrice = $material->price;
            $oldQuantityNeeded = $material->quantity_needed;
            $oldInventoryItemId = $material->inventory_item_id;
            $oldName = $material->name; // コスト更新時の検索用

            // 1. 既存の在庫引当を戻す処理 (ステータス変更や品目・数量変更時)
            // このロジックは、ステータスが「購入済」から変わる場合、または「購入済」のままでも品目・数量が変わる場合に適用
            if ($oldStatus === '購入済' && $oldInventoryItemId) {
                $oldInventoryItem = InventoryItem::find($oldInventoryItemId);
                if ($oldInventoryItem) {
                    $needsReturn = false;
                    if ($isStatusUpdateRequest) { // ステータスのみの更新
                        if ($newStatus === '未購入') $needsReturn = true;
                    } else { // 全項目更新
                        if (
                            $newStatus === '未購入' || // 「未購入」になる
                            ($newInventoryItemId != $oldInventoryItemId) || // 品目が変わる
                            ($newInventoryItemId == $oldInventoryItemId && floatval($newQuantityNeeded) != floatval($oldQuantityNeeded)) // 同じ品目で数量が変わる
                        ) {
                            $needsReturn = true;
                        }
                    }

                    if ($needsReturn) {
                        $qtyToReturn = floatval($oldQuantityNeeded);
                        $unitPriceForCostReturn = $material->unit_price_at_creation ?: $oldInventoryItem->average_unit_price;
                        $costToReturn = $unitPriceForCostReturn * $qtyToReturn;

                        $oldInventoryItem->increment('quantity', $qtyToReturn);
                        $oldInventoryItem->increment('total_cost', $costToReturn);

                        InventoryLog::create([
                            'inventory_item_id' => $oldInventoryItem->id,
                            'user_id' => Auth::id(),
                            'change_type' => 'returned_from_material_update',
                            'quantity_change' => $qtyToReturn,
                            'quantity_before_change' => $oldInventoryItem->quantity - $qtyToReturn, // increment前の値
                            'quantity_after_change' => $oldInventoryItem->quantity,
                            'related_material_id' => $material->id,
                            'unit_price_at_change' => $unitPriceForCostReturn,
                            'total_price_at_change' => $costToReturn,
                            'notes' => "材料「{$oldName}」の更新により在庫に戻されました。",
                        ]);
                    }
                }
            }

            // マテリアル情報本体の更新データ準備
            $updateData = $validated; // ステータスのみ更新なら $validated = ['status' => ...]
            if (!$isStatusUpdateRequest) { // 全項目更新の場合のみ、他のフィールドも設定
                $updateData['inventory_item_id'] = $newInventoryItemId; // 手入力ならnull
                // 手入力で合計価格が入力された場合、単価を再計算
                if ($isManualInput && isset($validated['price']) && $newQuantityNeeded > 0) {
                    $updateData['unit_price_at_creation'] = round(floatval($validated['price']) / $newQuantityNeeded, 2);
                } elseif ($newInventoryItemId) {
                    // 在庫品選択時はJSから送信されたunit_price_at_creation (data-avg_price由来) を使用
                    // $updateData['unit_price_at_creation'] は $validated['unit_price_at_creation'] のまま
                } else {
                    // 在庫品でなく、手入力でもない異常系だが念のため
                    $updateData['unit_price_at_creation'] = 0;
                }

                // inventory_item_id が変更された、または新規設定された場合は、nameとunitを在庫品目のものに更新
                // inventory_item_id がクリアされた場合（在庫品目との紐付け解除）、name と unit はフォームから送信されたもの（手入力）が使われる
                if (isset($validated['inventory_item_id']) && $validated['inventory_item_id'] != $oldInventoryItemId) {
                    $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
                    if ($inventoryItem) {
                        $updateData['name'] = $inventoryItem->name;
                        $updateData['unit'] = $inventoryItem->unit;
                    }
                } elseif (!isset($validated['inventory_item_id']) && $oldInventoryItemId) {
                    // inventory_item_id がクリアされた（手入力に切り替わった）場合
                    // name, unit は $validated['name'], $validated['unit'] (フォームからの手入力値) が使われる
                }
            }


            $material->update($updateData);
            $material->refresh(); // DBからの最新情報をモデルに反映

            // 2. 新しい状態に基づいて在庫を引く処理 (ステータス変更や品目・数量変更時)
            // このロジックは、ステータスが「購入済」になる場合に適用
            if ($material->status === '購入済' && $material->inventory_item_id) { // 在庫品かつ新ステータスが「購入済」
                $currentInventoryItem = InventoryItem::find($material->inventory_item_id);
                if ($currentInventoryItem) {
                    $needsDecrement = false;
                    if ($isStatusUpdateRequest) { // ステータスのみの更新
                        if ($oldStatus === '未購入') $needsDecrement = true; // 未購入から購入済になった
                    } else { // 全項目更新
                        if (($oldStatus === '未購入') || // 未購入から購入済になった
                            ($oldInventoryItemId != $material->inventory_item_id) || // 品目が変わった (かつ新ステータス購入済)
                            ($oldInventoryItemId == $material->inventory_item_id && floatval($oldQuantityNeeded) != floatval($material->quantity_needed)) // 同じ品目で数量が変わった (かつ新ステータス購入済)
                        ) {
                            $needsDecrement = true;
                        }
                    }

                    if ($needsDecrement) {
                        $qtyToDecrement = floatval($material->quantity_needed);
                        $currentInventoryQty = $currentInventoryItem->quantity; // decrement前の数量
                        $unitPriceForCostDecreaseNew = $material->unit_price_at_creation ?: $currentInventoryItem->average_unit_price;
                        $costToDecreaseNew = $unitPriceForCostDecreaseNew * $qtyToDecrement;

                        // 在庫が足りるか最終確認 (戻し処理後なので、ここでの在庫は最新のはず)
                        if ($currentInventoryItem->quantity < $qtyToDecrement) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "在庫不足です。「{$currentInventoryItem->name}」の現在の在庫は {$currentInventoryItem->quantity}{$currentInventoryItem->unit} です。",
                                'errors' => ['quantity_needed' => ["在庫不足です。必要量: {$qtyToDecrement}{$currentInventoryItem->unit}、現在庫: {$currentInventoryItem->quantity}{$currentInventoryItem->unit}"]],
                            ], 422);
                        }

                        $currentInventoryItem->decrement('quantity', $qtyToDecrement);
                        $currentInventoryItem->decrement('total_cost', $costToDecreaseNew);

                        InventoryLog::create([
                            'inventory_item_id' => $currentInventoryItem->id,
                            'user_id' => Auth::id(),
                            'change_type' => 'used_for_material_update',
                            'quantity_change' => -$qtyToDecrement,
                            'quantity_before_change' => $currentInventoryQty,
                            'quantity_after_change' => $currentInventoryItem->quantity,
                            'related_material_id' => $material->id,
                            'unit_price_at_change' => $unitPriceForCostDecreaseNew,
                            'total_price_at_change' => -$costToDecreaseNew,
                            'notes' => "材料「{$material->name}」として使用 (更新処理)",
                        ]);
                    }
                }
            }

            // コストテーブルの更新ロジック
            // このロジックはステータスのみの更新でも、全項目更新でも正しく動作する
            $costsUpdatedFlag = false;
            $newCostItemDescription = $material->name; // 更新後の名前
            $newCostAmount = $material->price; // 更新後の価格

            if ($oldStatus !== '購入済' && $material->status === '購入済' && $newCostAmount > 0) {
                // 未購入 -> 購入済: コスト追加
                $character->costs()->create([
                    'item_description' => $newCostItemDescription,
                    'amount' => $newCostAmount,
                    'type' => '材料費',
                    'cost_date' => now(),
                ]);
                $costsUpdatedFlag = true;
            } elseif ($oldStatus === '購入済' && $material->status === '未購入') {
                // 購入済 -> 未購入: コスト削除 (更新前の名前と価格で検索)
                $costToDelete = $character->costs()->where('type', '材料費')->where('item_description', $oldName)->where('amount', $oldPrice)->first();
                if ($costToDelete) $costToDelete->delete();
                $costsUpdatedFlag = true;
            } elseif ($material->status === '購入済' && ($oldPrice != $newCostAmount || $oldName != $newCostItemDescription)) {
                // 購入済のまま名前や価格が変更された場合: 既存コストを削除して新規追加
                $costToDelete = $character->costs()->where('type', '材料費')->where('item_description', $oldName)->where('amount', $oldPrice)->first();
                if ($costToDelete) $costToDelete->delete();
                if ($newCostAmount > 0) {
                    $character->costs()->create([
                        'item_description' => $newCostItemDescription,
                        'amount' => $newCostAmount,
                        'type' => '材料費',
                        'cost_date' => now(),
                    ]);
                }
                $costsUpdatedFlag = true;
            }


            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '材料情報を更新しました。',
                'material' => $material->load('inventoryItem'), // 更新されたmaterialインスタンスを返す
                'costs_updated' => $costsUpdatedFlag
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Material update failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'material_id' => $material->id,
                'exception' => $e
            ]);
            // JSON文字列のようなエラーメッセージを避けるため、具体的なエラー内容はログに記録し、
            // フロントエンドには汎用的なメッセージを返す
            return response()->json([
                'success' => false,
                'message' => '材料の更新中に予期せぬエラーが発生しました。詳細はサーバーログを確認してください。',
            ], 500);
        }
    }

    public function destroy(Project $project, Character $character, Material $material) // 変更なしでOK
    {
        $this->authorize('manageMaterials', $project);
        if ($material->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $wasPurchased = $material->status === '購入済';
            $inventoryItemId = $material->inventory_item_id;
            $quantityNeeded = $material->quantity_needed;
            $materialNameForCost = $material->name;
            $materialPriceForCost = $material->price;
            $unitPriceAtCreation = $material->unit_price_at_creation;

            if ($wasPurchased && $inventoryItemId) { // 在庫品かつ購入済だった場合のみ在庫を戻す
                $inventoryItem = InventoryItem::find($inventoryItemId);
                if ($inventoryItem) {
                    $oldInventoryQty = $inventoryItem->quantity;
                    $unitPriceForCostReturn = $unitPriceAtCreation ?: $inventoryItem->average_unit_price;
                    $costToReturn = $unitPriceForCostReturn * floatval($quantityNeeded);

                    $inventoryItem->increment('quantity', floatval($quantityNeeded));
                    $inventoryItem->increment('total_cost', $costToReturn);

                    InventoryLog::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => Auth::id(),
                        'change_type' => 'returned_on_material_delete',
                        'quantity_change' => floatval($quantityNeeded),
                        'quantity_before_change' => $oldInventoryQty,
                        'quantity_after_change' => $inventoryItem->quantity,
                        'related_material_id' => $material->id,
                        'unit_price_at_change' => $unitPriceForCostReturn,
                        'total_price_at_change' => $costToReturn,
                        'notes' => "材料「{$materialNameForCost}」削除により在庫に戻されました。",
                    ]);
                }
            }

            if ($wasPurchased && $materialPriceForCost > 0) {
                $costToDelete = $character->costs()
                    ->where('type', '材料費')
                    ->where('item_description', $materialNameForCost)
                    ->where('amount', $materialPriceForCost)
                    ->first();
                if ($costToDelete) {
                    $costToDelete->delete();
                }
            }

            $material->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '材料を削除しました。',
                'costs_updated' => $wasPurchased && $materialPriceForCost > 0
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Material delete failed: ' . $e->getMessage(), ['material_id' => $material->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => '材料の削除中にエラーが発生しました。'], 500);
        }
    }
}

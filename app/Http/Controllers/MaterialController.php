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
use Illuminate\Support\Facades\Validator; // 明示的に use
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMaterials', $project);

        $validated = $request->validate([
            'inventory_item_id' => 'required_if:use_inventory_item,1|nullable|exists:inventory_items,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'unit_price_at_creation' => 'required|numeric|min:0',
            'quantity_needed' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'status' => ['nullable', Rule::in(['未購入', '購入済'])],
        ]);

        $statusToSet = $request->input('status', '未購入');
        $quantityNeeded = floatval($validated['quantity_needed']);

        // ★ 在庫チェック (ステータスが「購入済」で在庫品目が選択されている場合)
        if ($statusToSet === '購入済' && !empty($validated['inventory_item_id'])) {
            $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
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
            $materialData['status'] = $statusToSet;

            if (!empty($validated['inventory_item_id'])) {
                $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
                if ($inventoryItem) {
                    $materialData['name'] = $inventoryItem->name;
                    $materialData['unit'] = $inventoryItem->unit;
                }
            }

            $material = Material::create($materialData);

            if ($material->status === '購入済' && $material->inventory_item_id) {
                $inventoryItem = InventoryItem::find($material->inventory_item_id); // 再度取得するか、上で取得したものを利用
                if ($inventoryItem) {
                    $oldInventoryQty = $inventoryItem->quantity;
                    $costToDecrease = ($material->unit_price_at_creation ?: $inventoryItem->average_unit_price) * $quantityNeeded;

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
                        'unit_price_at_change' => $material->unit_price_at_creation ?: $inventoryItem->average_unit_price,
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
            Log::error('Material store failed: ' . $e->getMessage(), ['request' => $request->all(), 'exception' => $e]);
            return response()->json(['success' => false, 'message' => '材料の追加中にエラーが発生しました。詳細はサーバーログを確認してください。'], 500);
        }
    }

    public function update(Request $request, Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project);
        if ($material->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        $isStatusUpdateRequest = $request->has('status') && count($request->all()) === 1;

        if ($isStatusUpdateRequest) {
            $validated = $request->validate(['status' => ['required', Rule::in(['未購入', '購入済'])]]);
        } else {
            $validated = $request->validate([
                'inventory_item_id' => 'required_if:use_inventory_item,1|nullable|exists:inventory_items,id',
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required|numeric|min:0',
                'unit_price_at_creation' => 'required|numeric|min:0',
                'quantity_needed' => 'required|numeric|min:0.01',
                'notes' => 'nullable|string|max:1000',
                'status' => ['sometimes', 'required', Rule::in(['未購入', '購入済'])],
            ]);
        }

        $newStatus = $validated['status'] ?? $material->status; // 更新リクエストになければ既存のステータス
        $newInventoryItemId = $isStatusUpdateRequest ? $material->inventory_item_id : ($validated['inventory_item_id'] ?? null);
        $newQuantityNeeded = $isStatusUpdateRequest ? $material->quantity_needed : floatval($validated['quantity_needed']);

        // ★ 在庫チェック (ステータスが「購入済」で在庫品目が選択されている場合)
        if ($newStatus === '購入済' && !empty($newInventoryItemId)) {
            $inventoryItem = InventoryItem::find($newInventoryItemId);
            if ($inventoryItem) {
                $effectiveAvailableStock = $inventoryItem->quantity;
                // もし更新対象の材料が元々「購入済」で、かつ同じ在庫品目だった場合、
                // 更新ロジックで一旦在庫を戻すので、その分を有効在庫に加算してチェックする
                if ($material->status === '購入済' && $material->inventory_item_id == $inventoryItem->id) {
                    $effectiveAvailableStock += floatval($material->quantity_needed);
                }

                if ($newQuantityNeeded > $effectiveAvailableStock) {
                    return response()->json([
                        'success' => false,
                        'message' => "在庫不足です。「{$inventoryItem->name}」の有効在庫は {$effectiveAvailableStock}{$inventoryItem->unit} (この材料の既存引当分を含む場合) です。",
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
            $oldName = $material->name;

            $updateData = $validated;

            if (!$isStatusUpdateRequest && isset($validated['inventory_item_id']) && $validated['inventory_item_id'] != $oldInventoryItemId) {
                $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
                if ($inventoryItem) {
                    $updateData['name'] = $inventoryItem->name;
                    $updateData['unit'] = $inventoryItem->unit;
                }
            } elseif (!$isStatusUpdateRequest && !isset($validated['inventory_item_id']) && $oldInventoryItemId) {
                // inventory_item_id がクリアされた場合、フォームから送信された name, unit を使う
            }


            // 1. 在庫とコストを戻す必要があるか判断
            if ($oldStatus === '購入済' && $oldInventoryItemId) {
                if (
                    $newStatus === '未購入' ||
                    ($newInventoryItemId != $oldInventoryItemId) ||
                    ($newInventoryItemId == $oldInventoryItemId && floatval($newQuantityNeeded) != floatval($oldQuantityNeeded))
                ) {
                    $oldInventoryItem = InventoryItem::find($oldInventoryItemId);
                    if ($oldInventoryItem) {
                        $qtyToReturn = floatval($oldQuantityNeeded);
                        $costToReturn = ($material->unit_price_at_creation ?: $oldInventoryItem->average_unit_price) * $qtyToReturn;

                        $oldInventoryItem->increment('quantity', $qtyToReturn);
                        $oldInventoryItem->increment('total_cost', $costToReturn);

                        InventoryLog::create([
                            'inventory_item_id' => $oldInventoryItem->id,
                            'user_id' => Auth::id(),
                            'change_type' => 'returned_from_material',
                            'quantity_change' => $qtyToReturn,
                            'quantity_before_change' => $oldInventoryItem->quantity - $qtyToReturn,
                            'quantity_after_change' => $oldInventoryItem->quantity,
                            'related_material_id' => $material->id,
                            'unit_price_at_change' => $material->unit_price_at_creation ?: $oldInventoryItem->average_unit_price,
                            'total_price_at_change' => $costToReturn,
                            'notes' => "材料「{$oldName}」の変更/未購入化により在庫に戻されました。",
                        ]);
                    }
                }
            }

            // マテリアル情報を更新
            $material->update($updateData);
            $material->refresh();

            // 2. 新しい状態に基づいて在庫を減らす必要があるか判断
            if ($material->status === '購入済' && $material->inventory_item_id) {
                if (
                    $oldStatus === '未購入' || // 未購入から購入済になった
                    ($oldInventoryItemId != $material->inventory_item_id) || // 品目が変わった
                    ($oldInventoryItemId == $material->inventory_item_id && floatval($oldQuantityNeeded) != floatval($material->quantity_needed)) // 同じ品目で数量が変わった
                ) {
                    $currentInventoryItem = InventoryItem::find($material->inventory_item_id);
                    if ($currentInventoryItem) {
                        // 在庫チェックは既に行ったので、ここでは減算処理のみ（ただし、より安全にするなら再度チェックも可）
                        $currentInventoryQty = $currentInventoryItem->quantity; // 減算前の数量
                        $costToDecrease = ($material->unit_price_at_creation ?: $currentInventoryItem->average_unit_price) * floatval($material->quantity_needed);

                        $currentInventoryItem->decrement('quantity', floatval($material->quantity_needed));
                        $currentInventoryItem->decrement('total_cost', $costToDecrease);

                        InventoryLog::create([
                            'inventory_item_id' => $currentInventoryItem->id,
                            'user_id' => Auth::id(),
                            'change_type' => 'used_for_material',
                            'quantity_change' => -floatval($material->quantity_needed),
                            'quantity_before_change' => $currentInventoryQty,
                            'quantity_after_change' => $currentInventoryItem->quantity,
                            'related_material_id' => $material->id,
                            'unit_price_at_change' => $material->unit_price_at_creation ?: $currentInventoryItem->average_unit_price,
                            'total_price_at_change' => -$costToDecrease,
                            'notes' => "材料「{$material->name}」として使用 (更新処理)",
                        ]);
                    }
                }
            }

            // コストテーブルの更新ロジック
            $costsUpdatedFlag = false;
            $newCostItemDescription = $material->name;
            $newCostAmount = $material->price;

            if ($oldStatus !== '購入済' && $material->status === '購入済' && $newCostAmount > 0) {
                $character->costs()->create([
                    'item_description' => $newCostItemDescription,
                    'amount' => $newCostAmount,
                    'type' => '材料費',
                    'cost_date' => now()
                ]);
                $costsUpdatedFlag = true;
            } elseif ($oldStatus === '購入済' && $material->status === '未購入') {
                $costToDelete = $character->costs()->where('type', '材料費')->where('item_description', $oldName)->where('amount', $oldPrice)->first();
                if ($costToDelete) $costToDelete->delete();
                $costsUpdatedFlag = true;
            } elseif ($material->status === '購入済' && ($oldPrice != $newCostAmount || $oldName != $newCostItemDescription)) {
                $costToDelete = $character->costs()->where('type', '材料費')->where('item_description', $oldName)->where('amount', $oldPrice)->first();
                if ($costToDelete) $costToDelete->delete();
                if ($newCostAmount > 0) {
                    $character->costs()->create([
                        'item_description' => $newCostItemDescription,
                        'amount' => $newCostAmount,
                        'type' => '材料費',
                        'cost_date' => now()
                    ]);
                }
                $costsUpdatedFlag = true;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '材料情報を更新しました。',
                'material' => $material->load('inventoryItem'),
                'costs_updated' => $costsUpdatedFlag
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Material update failed: ' . $e->getMessage(), ['request' => $request->all(), 'material_id' => $material->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => '材料の更新中に予期せぬエラーが発生しました。詳細はサーバーログを確認してください。'], 500);
        }
    }

    public function destroy(Project $project, Character $character, Material $material)
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

            if ($wasPurchased && $inventoryItemId) {
                $inventoryItem = InventoryItem::find($inventoryItemId);
                if ($inventoryItem) {
                    $oldInventoryQty = $inventoryItem->quantity;
                    $costToReturn = ($unitPriceAtCreation ?: $inventoryItem->average_unit_price) * floatval($quantityNeeded);

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
                        'unit_price_at_change' => $unitPriceAtCreation ?: $inventoryItem->average_unit_price,
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
                'costs_updated' => $wasPurchased
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Material delete failed: ' . $e->getMessage(), ['material_id' => $material->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => '材料の削除中にエラーが発生しました。詳細はサーバーログを確認してください。'], 500);
        }
    }
}

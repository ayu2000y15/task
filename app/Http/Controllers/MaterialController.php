<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Material;
use App\Models\Character;
use App\Models\InventoryItem; // ★追加
use App\Models\InventoryLog;  // ★追加
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ★追加
use Illuminate\Support\Facades\DB;   // ★追加
use Illuminate\Support\Facades\Log;  // ★Logファサードを追加 (デバッグやエラー記録用)
use Illuminate\Support\Facades\Validator;
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
            'price' => 'required|numeric|min:0', // これは合計価格
            'unit_price_at_creation' => 'required|numeric|min:0',
            'quantity_needed' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'status' => ['nullable', Rule::in(['未購入', '購入済'])], // フォームからstatusも送信する場合
        ]);

        DB::beginTransaction();
        try {
            $materialData = $validated;
            $materialData['character_id'] = $character->id;
            $materialData['status'] = $request->input('status', '未購入'); // デフォルトは未購入

            // inventory_item_id がある場合は、name と unit を在庫品目のもので上書き (または確認)
            if (!empty($validated['inventory_item_id'])) {
                $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
                if ($inventoryItem) {
                    $materialData['name'] = $inventoryItem->name; // 在庫品目名を正として使用
                    $materialData['unit'] = $inventoryItem->unit; // 在庫品目単位を正として使用
                }
            }

            $material = Material::create($materialData);

            if ($material->status === '購入済' && $material->inventory_item_id) {
                $inventoryItem = InventoryItem::find($material->inventory_item_id);
                if ($inventoryItem) {
                    $quantityNeeded = floatval($material->quantity_needed);
                    if ($inventoryItem->quantity >= $quantityNeeded) {
                        $oldInventoryQty = $inventoryItem->quantity;
                        $costToDecrease = ($material->unit_price_at_creation ?: $inventoryItem->average_unit_price) * $quantityNeeded;

                        $inventoryItem->decrement('quantity', $quantityNeeded);
                        $inventoryItem->decrement('total_cost', $costToDecrease); // total_costも更新

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
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "在庫不足です。「{$inventoryItem->name}」の現在の在庫は {$inventoryItem->quantity}{$inventoryItem->unit} です。",
                            'errors' => ['quantity_needed' => ["在庫不足です。必要量: {$quantityNeeded}{$inventoryItem->unit}、現在庫: {$inventoryItem->quantity}{$inventoryItem->unit}"]],
                        ], 422);
                    }
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
            Log::error('Material store failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => '材料の追加中にエラーが発生しました。詳細はサーバーログを確認してください。',
            ], 500);
        }
    }

    public function update(Request $request, Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project);
        if ($material->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        // ステータスのみの更新か、全項目更新かを判定
        // statusキーのみが存在し、他の主要フィールドがない場合はステータス更新とみなす
        $isStatusUpdateRequest = $request->has('status') &&
            !$request->has('inventory_item_id') &&
            !$request->has('name') && // nameは隠しフィールドで送られるが、ここでは主要フィールドとしてチェック
            !$request->has('quantity_needed');


        if ($isStatusUpdateRequest) {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['未購入', '購入済'])],
            ]);
        } else {
            $validated = $request->validate([
                'inventory_item_id' => 'required_if:use_inventory_item,1|nullable|exists:inventory_items,id',
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required|numeric|min:0',
                'unit_price_at_creation' => 'required|numeric|min:0',
                'quantity_needed' => 'required|numeric|min:0.01',
                'notes' => 'nullable|string|max:1000',
                'status' => ['sometimes', 'required', Rule::in(['未購入', '購入済'])], // 通常の編集フォームにもstatusを含める場合
            ]);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $material->status;
            $oldPrice = $material->price; // これは合計金額
            $oldQuantityNeeded = $material->quantity_needed;
            $oldInventoryItemId = $material->inventory_item_id;
            $oldName = $material->name; // 古い名前を保持 (コスト更新用)

            $updateData = $validated;

            // inventory_item_id が変更された、または新規設定された場合は、nameとunitを在庫品目のものに更新
            if (!$isStatusUpdateRequest && isset($validated['inventory_item_id']) && $validated['inventory_item_id'] != $oldInventoryItemId) {
                $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
                if ($inventoryItem) {
                    $updateData['name'] = $inventoryItem->name;
                    $updateData['unit'] = $inventoryItem->unit;
                }
            } elseif (!$isStatusUpdateRequest && !isset($validated['inventory_item_id']) && $oldInventoryItemId) {
                // inventory_item_id がクリアされた場合（在庫品目との紐付け解除）
                // name と unit はフォームから送信されたもの（手入力）が使われる
                // もし name や unit がフォームにないなら、既存のものを維持するか、エラーにするか検討
            }


            $inventoryItemChanged = (!$isStatusUpdateRequest && $material->inventory_item_id != ($updateData['inventory_item_id'] ?? $material->inventory_item_id));
            $quantityChanged = (!$isStatusUpdateRequest && floatval($material->quantity_needed) != floatval($updateData['quantity_needed'] ?? $material->quantity_needed));
            $newStatus = $updateData['status'] ?? $oldStatus;


            // 1. 在庫調整ロジック: まず古い状態に基づいて在庫を戻す必要があるか判断
            if ($oldStatus === '購入済' && $oldInventoryItemId) {
                // 「未購入」になるか、品目か数量が変わる場合は、一度在庫を戻す
                if ($newStatus === '未購入' || $inventoryItemChanged || $quantityChanged) {
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
                            'quantity_before_change' => $oldInventoryItem->quantity - $qtyToReturn, // increment後の値から戻す
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
            $material->refresh(); // DBからの最新情報をモデルに反映

            // 2. 在庫調整ロジック: 新しい状態に基づいて在庫を減らす必要があるか判断
            if ($material->status === '購入済' && $material->inventory_item_id) {
                // 「未購入」から「購入済」になったか、品目や数量が変わって「購入済」のままの場合
                if ($oldStatus === '未購入' || $inventoryItemChanged || $quantityChanged) {
                    $currentInventoryItem = InventoryItem::find($material->inventory_item_id);
                    if ($currentInventoryItem) {
                        $quantityNeeded = floatval($material->quantity_needed);
                        if ($currentInventoryItem->quantity >= $quantityNeeded) {
                            $currentInventoryQty = $currentInventoryItem->quantity;
                            $costToDecrease = ($material->unit_price_at_creation ?: $currentInventoryItem->average_unit_price) * $quantityNeeded;

                            $currentInventoryItem->decrement('quantity', $quantityNeeded);
                            $currentInventoryItem->decrement('total_cost', $costToDecrease);

                            InventoryLog::create([
                                'inventory_item_id' => $currentInventoryItem->id,
                                'user_id' => Auth::id(),
                                'change_type' => 'used_for_material',
                                'quantity_change' => -$quantityNeeded,
                                'quantity_before_change' => $currentInventoryQty,
                                'quantity_after_change' => $currentInventoryItem->quantity,
                                'related_material_id' => $material->id,
                                'unit_price_at_change' => $material->unit_price_at_creation ?: $currentInventoryItem->average_unit_price,
                                'total_price_at_change' => -$costToDecrease,
                                'notes' => "材料「{$material->name}」として使用 (更新処理)",
                            ]);
                        } else {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "在庫不足です。「{$currentInventoryItem->name}」の現在の在庫は {$currentInventoryItem->quantity}{$currentInventoryItem->unit} です。",
                                'errors' => ['quantity_needed' => ["在庫不足です。必要量: {$quantityNeeded}{$currentInventoryItem->unit}、現在庫: {$currentInventoryItem->quantity}{$currentInventoryItem->unit}"]],
                            ], 422);
                        }
                    }
                }
            }

            // コストテーブルの更新ロジック
            $costsUpdatedFlag = false;
            $newCostItemDescription = $material->name;
            $newCostAmount = $material->price;

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
                // 購入済 -> 未購入: コスト削除 (古い名前と価格で検索)
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


            // 材料が「購入済」だった場合、関連する在庫を戻す
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
                        'related_material_id' => $material->id, // 削除されるマテリアルのID
                        'unit_price_at_change' => $unitPriceAtCreation ?: $inventoryItem->average_unit_price,
                        'total_price_at_change' => $costToReturn,
                        'notes' => "材料「{$materialNameForCost}」削除により在庫に戻されました。",
                    ]);
                }
            }

            // 材料を削除する前に、関連する自動生成されたコストも削除
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
            Log::error('Material delete failed: ' . $e->getMessage(), [
                'material_id' => $material->id,
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => '材料の削除中にエラーが発生しました。詳細はサーバーログを確認してください。',
            ], 500);
        }
    }
}

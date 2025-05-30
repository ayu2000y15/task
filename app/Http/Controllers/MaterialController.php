<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Material;
use Illuminate\Http\Request;
use App\Models\Cost;
use App\Models\Character;
use Illuminate\Support\Facades\Validator; // ★ Validatorファサードをuse

class MaterialController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMaterials', $project); // もしくは 'create', Material::class など

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'nullable|integer|min:0',
            'quantity_needed' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000', // ★ 備考のバリデーション追加
            // 'status' はフォームから送信されないため、バリデーション不要（デフォルト'未購入'を想定）
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = '未購入'; // 新規作成時はデフォルト「未購入」

        $material = $character->materials()->create($validated);

        // ★ ログ記録: 材料作成 (MaterialモデルのLogsActivityが発火)

        return response()->json([
            'success' => true,
            'message' => '材料を追加しました。',
            'material' => $material->fresh() // ★ 作成されたデータを返す
        ]);
    }

    public function update(Request $request, Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project); // もしくは 'update', $material など
        if ($material->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        // ★ AJAXからのリクエストは通常、全ての項目を更新するか、ステータスのみを更新するかの2パターンを想定
        // ★ ここでは全ての項目を更新する場合のバリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255', // 'sometimes' で項目が存在すればバリデーション
            'price' => 'sometimes|nullable|integer|min:0',
            'quantity_needed' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:未購入,購入済',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $oldStatus = $material->status;
        $oldPrice = $material->price;

        $material->update($validatedData);
        $newStatus = $material->status; // 更新後のステータスを取得

        // ★ 材料ステータス変更時のコスト自動登録/削除ロジック (既存のものを維持)
        if (array_key_exists('status', $validatedData)) { // ステータスがリクエストに含まれている場合のみ処理
            if ($oldStatus !== '購入済' && $newStatus === '購入済' && !is_null($material->price) && $material->price > 0) {
                $existingCost = $character->costs()
                    ->where('type', '材料費')
                    ->where('item_description', $material->name)
                    ->first();
                if (!$existingCost) {
                    $character->costs()->create([
                        'item_description' => $material->name,
                        'amount' => $material->price,
                        'type' => '材料費',
                        'cost_date' => now(),
                        // 'notes' => '材料「'.$material->name.'」購入により自動登録', // 必要なら備考も
                    ]);
                }
            } elseif ($oldStatus === '購入済' && $newStatus === '未購入' && !is_null($oldPrice) && $oldPrice > 0) {
                $costToDelete = $character->costs()
                    ->where('type', '材料費')
                    ->where('item_description', $material->name)
                    ->where('amount', $oldPrice)
                    ->first();
                if ($costToDelete) {
                    $costToDelete->delete();
                }
            }
        }

        // ★ ログ記録: 材料更新 (MaterialモデルのLogsActivityが発火)

        return response()->json([
            'success' => true,
            'message' => '材料情報を更新しました。',
            'material' => $material->fresh(), // ★ 更新後のデータを返す
            'costs_updated' => ($oldStatus !== $newStatus) // ★ コストタブの再描画が必要かどうかのフラグ
        ]);
    }

    public function destroy(Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project); // もしくは 'delete', $material など
        if ($material->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        $originalMaterialName = $material->name; // ログ用に保持

        // 材料を削除する前に、関連する自動生成されたコストも削除
        if ($material->status === '購入済' && !is_null($material->price) && $material->price > 0) {
            $costToDelete = $character->costs() // $material->character ではなく $character を使用
                ->where('type', '材料費')
                ->where('item_description', $material->name)
                ->where('amount', $material->price)
                ->first();
            if ($costToDelete) {
                $costToDelete->delete(); // CostモデルのLogsActivityが発火
            }
        }
        $material->delete(); // MaterialモデルのLogsActivityが発火

        return response()->json([
            'success' => true,
            'message' => '材料を削除しました。',
            'costs_updated' => ($material->status === '購入済') // ★ コストタブの再描画が必要かどうかのフラグ
        ]);
    }
}

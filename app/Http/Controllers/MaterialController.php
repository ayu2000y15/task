<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Material;
use Illuminate\Http\Request;
use App\Models\Cost;
use App\Models\Character;

class MaterialController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMaterials', $project);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|integer|min:0',
            'quantity_needed' => 'required|string|max:255',
        ]);

        $character->materials()->create($validated);
        return back()->with('success', '材料を追加しました。');
    }

    public function update(Request $request, Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project);
        abort_if($material->character_id !== $character->id, 404);

        $oldStatus = $material->status;
        $oldPrice = $material->price; // Store old price for comparison if status changes to 未購入

        $validated = $request->validate(['status' => 'required|in:未購入,購入済']);
        $material->update($validated);
        $newStatus = $material->status;

        // 「購入済」になった場合、かつ価格が入力されている場合、コストを自動登録
        if ($oldStatus !== '購入済' && $newStatus === '購入済' && !is_null($material->price) && $material->price > 0) {
            // 同じ材料費が既にコスト登録されていないか確認
            $existingCost = $character->costs()
                ->where('type', '材料費')
                ->where('item_description', $material->name)
                // ->where('amount', $material->price) // 金額まで一致するかは運用次第でコメントアウト解除
                ->first();

            if (!$existingCost) {
                $character->costs()->create([
                    'item_description' => $material->name,
                    'amount' => $material->price,
                    'type' => '材料費',
                    'cost_date' => now(),
                ]);
            }
        } elseif ($oldStatus === '購入済' && $newStatus === '未購入' && !is_null($oldPrice) && $oldPrice > 0) {
            // 「未購入」に戻された場合、自動登録された可能性のあるコストを削除
            // 材料名と以前の価格が一致するものを削除対象とする
            $costToDelete = $character->costs()
                ->where('type', '材料費')
                ->where('item_description', $material->name)
                ->where('amount', $oldPrice) // 以前の価格と一致するものを対象
                ->first();
            if ($costToDelete) {
                $costToDelete->delete();
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project);
        abort_if($material->character_id !== $character->id, 404);

        // 材料を削除する前に、関連する自動生成されたコストも削除
        // 「購入済」で価格があった場合に自動生成されたコストを想定
        if ($material->status === '購入済' && !is_null($material->price) && $material->price > 0) {
            $costToDelete = $material->character->costs()
                ->where('type', '材料費')
                ->where('item_description', $material->name)
                ->where('amount', $material->price) // 材料の価格と一致するものを対象
                ->first();
            if ($costToDelete) {
                $costToDelete->delete();
            }
        }
        $material->delete();
        return back()->with('success', '材料を削除しました。');
    }
}

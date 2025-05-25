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
        $validated = $request->validate(['status' => 'required|in:未購入,購入済']);
        $material->update($validated);

        // 「購入済」になった場合、かつ価格が入力されている場合、コストを自動登録
        if ($material->status === '購入済' && !is_null($material->price) && $material->price > 0) {
            // 同じ材料費が既にコスト登録されていないか確認
            $existingCost = $character->costs()->where('type', '材料費')->where('item_description', $material->name)->first();

            if (!$existingCost) {
                $character->costs()->create([
                    'item_description' => $material->name,
                    'amount' => $material->price,
                    'type' => '材料費',
                    'cost_date' => now(),
                ]);
            }
        }
        // 「未購入」に戻された場合、対応するコストを削除するロジックは、運用の複雑さを考慮し今回は見送り。
        // 必要であれば手動でコストを削除する運用とします。

        return response()->json(['success' => true]);
    }

    public function destroy(Project $project, Character $character, Material $material)
    {
        $this->authorize('manageMaterials', $project);
        abort_if($material->character_id !== $character->id, 404);
        $material->delete();
        return back()->with('success', '材料を削除しました。');
    }
}

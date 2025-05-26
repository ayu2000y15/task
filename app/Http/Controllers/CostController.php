<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Material; // Material モデルを use
use App\Models\Cost;
use Illuminate\Http\Request;
use App\Models\Character;

class CostController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageCosts', $project);
        $validated = $request->validate([
            'item_description' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'type' => 'required|string|max:50',
            'cost_date' => 'required|date',
        ]);

        $character->costs()->create($validated);

        return back()->with('success', 'コストを追加しました。');
    }

    public function destroy(Project $project, Character $character, Cost $cost)
    {
        $this->authorize('manageCosts', $project);
        abort_if($cost->character_id !== $character->id, 404);

        $deletedCostType = $cost->type;
        $deletedCostItemDescription = $cost->item_description;
        $deletedCostAmount = $cost->amount;
        $deletedCostCharacterId = $cost->character_id; // Character ID を取得

        $cost->delete();

        $successMessage = 'コストを削除しました。';

        // 削除されたコストが「材料費」タイプであった場合、関連する材料のステータスを更新
        if ($deletedCostType === '材料費') {
            // Costが属していたCharacterモデルを取得
            $costCharacter = Character::find($deletedCostCharacterId);
            if ($costCharacter) {
                $materialToUpdate = $costCharacter->materials()
                    ->where('name', $deletedCostItemDescription)
                    ->where('price', $deletedCostAmount) // 金額も一致するもの（自動追加された可能性が高い）
                    ->where('status', '購入済')        // 現在「購入済」であるもの
                    ->first();

                if ($materialToUpdate) {
                    $materialToUpdate->update(['status' => '未購入']);
                    $successMessage = 'コストを削除し、関連する材料のステータスを「未購入」に更新しました。';
                }
            }
        }

        return back()->with('success', $successMessage);
    }
}

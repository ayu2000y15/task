<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Material;
use App\Models\Cost;
use Illuminate\Http\Request;
use App\Models\Character;
use Illuminate\Support\Facades\Validator; // ★ Validatorファサードをuse

class CostController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageCosts', $project); // もしくは 'create', Cost::class など

        // ★ 並び順に合わせてバリデーションルールも調整
        $validator = Validator::make($request->all(), [
            'cost_date' => 'required|date_format:Y-m-d',
            'type' => 'required|string|max:50',
            'amount' => 'required|integer|min:0',
            'item_description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        $validated = $validator->validated();

        // ★ 新規作成時にdisplay_orderを設定
        $maxOrder = $character->costs()->max('display_order');
        $validated['display_order'] = $maxOrder + 1;

        $validated['project_id'] = $project->id;

        $cost = $character->costs()->create($validated);


        // ★ ログ記録: コスト作成 (CostモデルのLogsActivityが発火)

        return response()->json([
            'success' => true,
            'message' => 'コストを追加しました。',
            'cost' => $cost->fresh()->load('character.project') // ★ 作成されたデータを返す (必要ならリレーションも)
        ]);
    }

    /**
     * Update the specified cost in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Character  $character
     * @param  \App\Models\Cost  $cost
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Project $project, Character $character, Cost $cost)
    {
        $this->authorize('manageCosts', $project); // もしくは 'update', $cost など
        if ($cost->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'cost_date' => 'required|date_format:Y-m-d',
            'type' => 'required|string|max:50',
            'amount' => 'required|integer|min:0',
            'item_description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422);
        }

        // ★ 材料費から自動追加されたコストかどうかの判定ロジックは、更新時には複雑になるため、
        // ★ 一旦、単純な更新処理とし、ステータス変更による自動削除/追加は材料側で行うことを基本とします。
        // ★ もし、コストを編集した際に材料のステータスも連動させたい場合は、別途そのロジックを検討する必要があります。
        $cost->update($validator->validated());


        // ★ ログ記録: コスト更新 (CostモデルのLogsActivityが発火)

        return response()->json([
            'success' => true,
            'message' => 'コスト情報を更新しました。',
            'cost' => $cost->fresh()->load('character.project') // ★ 更新後のデータを返す
        ]);
    }

    public function destroy(Project $project, Character $character, Cost $cost)
    {
        $this->authorize('manageCosts', $project); // もしくは 'delete', $cost など
        if ($cost->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        $deletedCostType = $cost->type;
        $deletedCostItemDescription = $cost->item_description;
        $deletedCostAmount = $cost->amount;
        // $deletedCostCharacterId = $cost->character_id; // これは $character->id と同じはず

        $cost->delete(); // CostモデルのLogsActivityが発火

        $successMessage = 'コストを削除しました。';
        $materialStatusUpdated = false;

        // 削除されたコストが「材料費」タイプであった場合、関連する材料のステータスを更新
        if ($deletedCostType === '材料費') {
            // $character を使用
            $materialToUpdate = $character->materials()
                ->where('name', $deletedCostItemDescription)
                // ->where('price', $deletedCostAmount) // 金額の一致は必須としない方が柔軟性がある場合も
                ->where('status', '購入済')
                ->first();

            if ($materialToUpdate) {
                $materialToUpdate->update(['status' => '未購入']); // MaterialモデルのLogsActivityも発火
                $successMessage = 'コストを削除し、関連する材料のステータスを「未購入」に更新しました。';
                $materialStatusUpdated = true;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'material_status_updated' => $materialStatusUpdated, // ★ 材料ステータスも更新されたかフラグを返す
        ]);
    }

    /**
     * ★ コストデータの並び順を更新
     */
    public function updateOrder(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageCosts', $project);

        // $request->validate([
        //     'ids' => 'required|array',
        //     'ids.*' => 'integer|exists:costs,id',
        // ]);

        foreach ($request->ids as $index => $id) {
            Cost::where('id', $id)
                ->where('character_id', $character->id)
                ->update(['display_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'コストの並び順を更新しました。']);
    }
}

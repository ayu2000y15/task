<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Measurement;
use Illuminate\Http\Request;
use App\Models\Character;
use Illuminate\Support\Facades\Validator; // ★ Validatorファサードをuse

class MeasurementController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMeasurements', $project); // もしくは 'create', Measurement::class など

        $validator = Validator::make($request->all(), [
            'item' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            // 'unit' はフォームから削除したが、DBにはデフォルト値'cm'が入る想定、またはnullableなら不要
            'notes' => 'nullable|string|max:1000', // ★ 備考のバリデーション追加
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        $validated = $validator->validated();
        // unitをフォームから受け取らないため、デフォルト値を設定するかDBのデフォルトに任せる
        // $validated['unit'] = $validated['unit'] ?? 'cm'; // 例えば常にcmとする場合

        $measurement = $character->measurements()->create($validated);

        // ★ ログ記録: 採寸データ作成 (MeasurementモデルのLogsActivityが発火)
        // 必要であれば、ここで手動ログも追加可能だが、モデルのログで十分な場合が多い

        return response()->json([
            'success' => true,
            'message' => '採寸データを追加しました。',
            'measurement' => $measurement->fresh() // ★ 作成されたデータを返す
        ]);
    }

    /**
     * Update the specified measurement in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Character  $character
     * @param  \App\Models\Measurement  $measurement
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Project $project, Character $character, Measurement $measurement)
    {
        // ★ 認可: プロジェクトに対する権限と、キャラクターと採寸データの整合性チェック
        $this->authorize('manageMeasurements', $project); // もしくは 'update', $measurement など
        if ($measurement->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404); // または適切なエラーレスポンス
        }

        $validator = Validator::make($request->all(), [
            'item' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '入力内容にエラーがあります。',
                'errors' => $validator->errors()
            ], 422);
        }

        $measurement->update($validator->validated());

        // ★ ログ記録: 採寸データ更新 (MeasurementモデルのLogsActivityが発火)

        return response()->json([
            'success' => true,
            'message' => '採寸データを更新しました。',
            'measurement' => $measurement->fresh() // ★ 更新後のデータを返す
        ]);
    }


    public function destroy(Project $project, Character $character, Measurement $measurement)
    {
        $this->authorize('manageMeasurements', $project); // もしくは 'delete', $measurement など
        if ($measurement->character_id !== $character->id || $character->project_id !== $project->id) {
            abort(404);
        }

        $originalItemName = $measurement->item; // ログ用に保持

        $measurement->delete(); // MeasurementモデルのLogsActivityが発火 (deletedイベント)

        // ★ 手動で詳細な削除ログを残す場合はここで記述も可能
        // activity()
        //    ->causedBy(auth()->user())
        //    ->performedOn($character) // 親のキャラクターを対象とするなど
        //    ->withProperties(['deleted_measurement_item' => $originalItemName, 'character_name' => $character->name])
        //    ->log("キャラクター「{$character->name}」から採寸データ「{$originalItemName}」が削除されました。");

        return response()->json([
            'success' => true,
            'message' => '採寸データを削除しました。'
        ]);
    }
}

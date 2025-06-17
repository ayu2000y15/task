<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // ★ 追加
use Illuminate\Validation\Rule;          // ★ 追加

class CharacterController extends Controller
{
    public function store(Request $request, Project $project)
    {
        // プロジェクトを更新する権限があるかチェック (今回はprojects.updateで代用)
        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])], // ★ 追加
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'characterCreation') // ★ エラーバッグを指定
                ->withInput();
        }

        $project->characters()->create($validator->validated());

        return back()->with('success', 'キャラクターを追加しました。');
    }

    public function edit(Character $character)
    {
        $project = $character->project;
        $this->authorize('update', $project); // プロジェクト更新権限でキャラクター編集も許可
        return view('characters.edit', compact('project', 'character'));
    }

    public function update(Request $request, Character $character)
    {
        $project = $character->project;
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])], // ★ 追加
        ]);

        $character->update($validated);

        return redirect()->route('projects.show', $project)->with('success', 'キャラクター情報を更新しました。');
    }

    public function destroy(Character $character)
    {
        $project = $character->project;
        $this->authorize('update', $project); // プロジェクト更新権限でキャラクター削除も許可
        $character->delete(); // 関連する採寸・材料・コストもDBのカスケード削除で消える想定

        return redirect()->route('projects.show', $project)->with('success', 'キャラクターを削除しました。');
    }

    /**
     * キャラクターのコスト情報部分のHTMLを返す (AJAX用)
     */
    public function getCharacterCostsPartial(Project $project, Character $character)
    {
        $this->authorize('view', $project); // 親プロジェクトの閲覧権限で代用
        abort_if($character->project_id !== $project->id, 404);

        return view('projects.partials.character-costs-tailwind', compact('project', 'character'));
    }

    /**
     * ▼▼▼【新設】キャラクターの表示順を更新 ▼▼▼
     */
    public function updateOrder(Request $request, Project $project) // ★ 引数に $project を追加
    {
        // 案件の更新権限で代用
        $this->authorize('update', $project);

        $validated = $request->validate([
            'ids' => 'required|array',
            // ★ バリデーションルールを修正し、この案件に所属するキャラクターIDであることも検証
            'ids.*' => [
                'integer',
                Rule::exists('characters', 'id')->where('project_id', $project->id)
            ],
        ]);

        foreach ($validated['ids'] as $index => $id) {
            // ここでは既にバリデーションでproject_idが正しいことを確認済みなので、そのまま更新
            Character::where('id', $id)->update(['display_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'キャラクターの並び順を保存しました。']);
    }
}

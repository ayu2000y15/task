<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Character;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function store(Request $request, Project $project)
    {
        // プロジェクトを更新する権限があるかチェック (今回はprojects.updateで代用)
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->characters()->create($validated);

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

        return view('projects.partials.character_costs_list', compact('project', 'character'));
    }
}

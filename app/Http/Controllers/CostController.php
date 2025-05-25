<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
        $cost->delete();

        return back()->with('success', 'コストを削除しました。');
    }
}

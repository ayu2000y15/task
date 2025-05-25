<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Cost;
use Illuminate\Http\Request;

class CostController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'item_description' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'type' => 'required|string|max:50',
            'cost_date' => 'required|date',
        ]);

        $project->costs()->create($validated);

        return back()->with('success', 'コストを追加しました。');
    }

    public function destroy(Project $project, Cost $cost)
    {
        $cost->delete();

        return back()->with('success', 'コストを削除しました。');
    }
}

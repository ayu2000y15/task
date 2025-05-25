<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Measurement;
use Illuminate\Http\Request;
use App\Models\Character;

class MeasurementController extends Controller
{
    public function store(Request $request, Project $project, Character $character)
    {
        $this->authorize('manageMeasurements', $project);
        $validated = $request->validate([
            'item' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
        ]);

        $character->measurements()->create($validated);

        return back()->with('success', '採寸データを追加しました。');
    }

    public function destroy(Project $project, Character $character, Measurement $measurement)
    {
        $this->authorize('manageMeasurements', $project);
        abort_if($measurement->character_id !== $character->id, 404);
        $measurement->delete();

        return back()->with('success', '採寸データを削除しました。');
    }
}

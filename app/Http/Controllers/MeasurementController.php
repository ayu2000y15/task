<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Measurement;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'item' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
        ]);

        $project->measurements()->create($validated);

        return back()->with('success', '採寸データを追加しました。');
    }

    public function destroy(Project $project, Measurement $measurement)
    {
        $measurement->delete();

        return back()->with('success', '採寸データを削除しました。');
    }
}

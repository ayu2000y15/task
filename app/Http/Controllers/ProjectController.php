<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * プロジェクト一覧を表示
     */
    public function index()
    {
        $projects = Project::orderBy('title')->get();

        return view('projects.index', compact('projects'));
    }

    /**
     * 新規プロジェクト作成フォームを表示
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * 新規プロジェクトを保存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'color' => 'required|string|max:7',
        ]);

        $project = Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            'color' => $validated['color'],
            'is_favorite' => false,
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'プロジェクトが作成されました。');
    }

    /**
     * プロジェクト詳細を表示
     */
    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    /**
     * プロジェクト編集フォームを表示
     */
    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    /**
     * プロジェクトを更新
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'color' => 'required|string|max:7',
        ]);

        $project->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            'color' => $validated['color'],
            'is_favorite' => $request->has('is_favorite'),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'プロジェクトが更新されました。');
    }

    /**
     * プロジェクトを削除
     */
    public function destroy(Project $project)
    {
        // プロジェクトに関連するタスクも削除
        $project->tasks()->delete();
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'プロジェクトが削除されました。');
    }
}

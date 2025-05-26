<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * 衣装案件一覧を表示
     */
    public function index()
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::orderBy('title')->get();

        return view('projects.index', compact('projects'));
    }

    /**
     * 新規衣装案件作成フォームを表示
     */
    public function create()
    {
        $this->authorize('create', Project::class);
        return view('projects.create');
    }

    /**
     * 新規衣装案件を保存
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'series_title' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'color' => 'required|string|max:7',
        ]);

        $project = Project::create([
            'title' => $validated['title'],
            'series_title' => $validated['series_title'],
            'client_name' => $validated['client_name'],
            'description' => $validated['description'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            'color' => $validated['color'],
            'is_favorite' => false,
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', '衣装案件が作成されました。');
    }

    /**
     * 衣装案件詳細を表示
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);
        // キャラクター情報と、各キャラクターに紐づくタスクやその他の詳細情報も読み込む
        $project->load([
            'characters' => function ($query) {
                $query->with(['tasks', 'measurements', 'materials', 'costs'])->orderBy('name');
            },
            'tasksWithoutCharacter' => function ($query) {
                $query->orderBy('start_date');
            }
        ]);
        return view('projects.show', compact('project'));
    }

    /**
     * 衣装案件編集フォームを表示
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        return view('projects.edit', compact('project'));
    }

    /**
     * 衣装案件を更新
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'series_title' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'color' => 'required|string|max:7',
        ]);

        $project->update([
            'title' => $validated['title'],
            'series_title' => $validated['series_title'],
            'client_name' => $validated['client_name'],
            'description' => $validated['description'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            'color' => $validated['color'],
            'is_favorite' => $request->has('is_favorite'),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', '衣装案件が更新されました。');
    }

    /**
     * 衣装案件を削除
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        // 衣装案件に関連する工程も削除
        $project->tasks()->delete();
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', '衣装案件が削除されました。');
    }
}

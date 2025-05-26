<?php

namespace App\Http\Controllers;

use App\Models\ProcessTemplate;
use App\Models\ProcessTemplateItem;
use Illuminate\Http\Request;

class ProcessTemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ProcessTemplate::class);
        $templates = ProcessTemplate::orderBy('name')->get();
        return view('process_templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorize('create', ProcessTemplate::class);
        return view('process_templates.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ProcessTemplate::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:process_templates,name',
            'description' => 'nullable|string',
        ]);
        $template = ProcessTemplate::create($validated);
        return redirect()->route('process-templates.show', $template)->with('success', '工程テンプレートを作成しました。');
    }

    public function show(ProcessTemplate $processTemplate) // Route model binding
    {
        $this->authorize('view', $processTemplate);
        $processTemplate->load('items'); // Eager load items
        return view('process_templates.show', compact('processTemplate'));
    }

    public function update(Request $request, ProcessTemplate $processTemplate)
    {
        $this->authorize('update', $processTemplate);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:process_templates,name,' . $processTemplate->id,
            'description' => 'nullable|string',
        ]);
        $processTemplate->update($validated);
        return redirect()->route('process-templates.show', $processTemplate)->with('success', '工程テンプレートを更新しました。');
    }

    public function destroy(ProcessTemplate $processTemplate)
    {
        $this->authorize('delete', $processTemplate);
        $processTemplate->delete(); // Items will be deleted by cascading delete in DB
        return redirect()->route('process-templates.index')->with('success', '工程テンプレートを削除しました。');
    }

    // Template Items
    public function storeItem(Request $request, ProcessTemplate $processTemplate)
    {
        // 親テンプレートの更新権限でアイテム追加を制御
        $this->authorize('update', $processTemplate);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_duration' => 'nullable|integer|min:1',
            'order' => 'required|integer|min:0',
        ]);
        $processTemplate->items()->create($validated);
        return back()->with('success', 'テンプレートに工程項目を追加しました。');
    }

    public function destroyItem(ProcessTemplate $processTemplate, ProcessTemplateItem $item)
    {
        // 親テンプレートの更新権限でアイテム削除を制御
        $this->authorize('update', $processTemplate);
        if ($item->process_template_id !== $processTemplate->id) {
            abort(404);
        }
        $item->delete();
        return back()->with('success', 'テンプレートから工程項目を削除しました。');
    }
}

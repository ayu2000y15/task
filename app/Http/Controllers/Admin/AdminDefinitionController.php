<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralDefinition;

class AdminDefinitionController extends Controller
{
    protected $fileUploadService;

    public function index()
    {
        $definition = GeneralDefinition::orderBy('definition_id')->get();

        return view('admin.definition', data: compact('definition'));
    }

    public function store(Request $request)
    {
        GeneralDefinition::create($request->all());

        return redirect()->route('admin.definition')
            ->with('success', '「' . $request->definition . '」が登録されました。');
    }

    public function update(Request $request)
    {
        $title = GeneralDefinition::select('definition')->where('definition_id', $request->definition_id)->first();
        $def = GeneralDefinition::findOrFail($request->definition_id);
        $def->update($request->all());
        return redirect()->route('admin.definition')
            ->with('success', '「' . $title->definition . '」が更新されました。');
    }

    public function delete(Request $request)
    {
        $title = GeneralDefinition::select('definition')->where('definition_id', $request->definition_id)->first();
        GeneralDefinition::destroy($request->definition_id);
        return redirect()->route('admin.definition')
            ->with('success', '「' . $title->definition . '」が削除されました。');
    }
}

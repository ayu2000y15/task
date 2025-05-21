<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HpText;

class AdminHpTextController extends Controller
{
    protected $fileUploadService;

    public function index()
    {
        $hpText = HpText::select(
            'hp_text_id as t_id',
            'content',
            'memo',
            'spare1'
        )->orderBy('hp_text_id')->get();

        return view('admin.hptext', data: compact('hpText'));
    }

    public function store(Request $request)
    {
        HpText::create($request->all());

        return redirect()->route('admin.hptext')
            ->with('success', '「' . $request->hp_text_id . '」が登録されました。');
    }

    public function update(Request $request)
    {
        $def = HpText::findOrFail($request->hp_text_id);
        $def->update($request->all());
        return redirect()->route('admin.hptext')
            ->with('success', '「' . $request->hp_text_id . '」が更新されました。');
    }

    public function delete(Request $request)
    {
        HpText::destroy($request->hp_text_id);
        return redirect()->route('admin.hptext')
            ->with('success', '「' . $request->hp_text_id . '」が削除されました。');
    }
}

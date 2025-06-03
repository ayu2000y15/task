<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessTemplate;
use App\Models\ProcessTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // DB Facade を use
use Illuminate\Validation\Rule; // Rule を use

class ProcessTemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ProcessTemplate::class);
        // N+1 問題を避けるために items_count をロード
        $templates = ProcessTemplate::withCount('items')->orderBy('name')->get();
        return view('admin.process_templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorize('create', ProcessTemplate::class);
        return view('admin.process_templates.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ProcessTemplate::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:process_templates,name',
            'description' => 'nullable|string',
        ]);
        $template = ProcessTemplate::create($validated);
        return redirect()->route('admin.process-templates.show', $template)->with('success', '工程テンプレートを作成しました。');
    }

    public function show(ProcessTemplate $processTemplate)
    {
        $this->authorize('view', $processTemplate);
        // items リレーションをロードする際に order でソート
        $processTemplate->load(['items' => function ($query) {
            $query->orderBy('order', 'asc');
        }]);
        return view('admin.process_templates.show', compact('processTemplate'));
    }

    public function update(Request $request, ProcessTemplate $processTemplate)
    {
        $this->authorize('update', $processTemplate);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:process_templates,name,' . $processTemplate->id,
            'description' => 'nullable|string',
        ]);
        $processTemplate->update($validated);
        return redirect()->route('admin.process-templates.show', $processTemplate)->with('success', '工程テンプレートを更新しました。');
    }

    public function destroy(ProcessTemplate $processTemplate)
    {
        $this->authorize('delete', $processTemplate);
        // 関連するアイテムも削除されるようにモデル側で設定するか、ここで明示的に削除
        // $processTemplate->items()->delete(); // もしリレーションで cascadeOnDelete が設定されていなければ
        $processTemplate->delete();
        return redirect()->route('admin.process-templates.index')->with('success', '工程テンプレートを削除しました。');
    }

    // Template Items
    // メソッド名を storeItem から itemsStore に変更 (以前のCanvasで提案したルート名に合わせるため)
    public function storeItem(Request $request, ProcessTemplate $processTemplate)
    {
        $this->authorize('update', $processTemplate);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'default_duration_value' => 'nullable|numeric|min:0',
            'default_duration_unit' => [
                'nullable',
                'string',
                Rule::in(['days', 'hours', 'minutes']),
                // 工数値が入力されている場合のみ単位を必須とする
                Rule::requiredIf(function () use ($request) {
                    return $request->filled('default_duration_value') && $request->input('default_duration_value') > 0;
                }),
            ],
        ], [
            'default_duration_unit.required_if' => '工数値を入力した場合、単位も選択してください。'
        ]);

        $defaultDurationInMinutes = null;
        $defaultDurationUnit = null;

        if (isset($validated['default_duration_value']) && $validated['default_duration_value'] > 0 && isset($validated['default_duration_unit'])) {
            $value = (float)$validated['default_duration_value'];
            $unit = $validated['default_duration_unit'];
            $defaultDurationUnit = $unit; // 保存する単位

            switch ($unit) {
                case 'days':
                    $defaultDurationInMinutes = $value * 24 * 60; // 1日24時間
                    break;
                case 'hours':
                    $defaultDurationInMinutes = $value * 60;
                    break;
                case 'minutes':
                    $defaultDurationInMinutes = $value;
                    break;
            }
        } elseif (isset($validated['default_duration_value']) && (float)$validated['default_duration_value'] === 0.0) {
            // 工数値が0の場合は、単位に関わらず0分として保存
            $defaultDurationInMinutes = 0;
            $defaultDurationUnit = $validated['default_duration_unit'] ?? 'minutes'; // 単位がなければ分としておく
        }


        $processTemplate->items()->create([
            'name' => $validated['name'],
            'order' => $validated['order'],
            'default_duration' => $defaultDurationInMinutes,
            'default_duration_unit' => $defaultDurationUnit,
        ]);

        return redirect()->route('admin.process-templates.show', $processTemplate)
            ->with('success', 'テンプレートに工程項目を追加しました。');
    }

    /**
     * Update the specified item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProcessTemplate  $processTemplate
     * @param  \App\Models\ProcessTemplateItem  $item
     * @return \Illuminate\Http\Response
     */
    public function itemsUpdate(Request $request, ProcessTemplate $processTemplate, ProcessTemplateItem $item)
    {
        $this->authorize('update', $processTemplate); // 親テンプレートの更新権限で制御

        if ($item->process_template_id !== $processTemplate->id) {
            abort(403, '指定された項目はこのテンプレートに属していません。');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'default_duration_value' => 'nullable|numeric|min:0',
            'default_duration_unit' => [
                'nullable',
                'string',
                Rule::in(['days', 'hours', 'minutes']),
                Rule::requiredIf(function () use ($request) {
                    return $request->filled('default_duration_value') && $request->input('default_duration_value') > 0;
                }),
            ],
        ], [
            'default_duration_unit.required_if' => '工数値を入力した場合、単位も選択してください。'
        ]);

        $defaultDurationInMinutes = null;
        $defaultDurationUnit = null;

        if (isset($validated['default_duration_value']) && $validated['default_duration_value'] > 0 && isset($validated['default_duration_unit'])) {
            $value = (float)$validated['default_duration_value'];
            $unit = $validated['default_duration_unit'];
            $defaultDurationUnit = $unit;

            switch ($unit) {
                case 'days':
                    $defaultDurationInMinutes = $value * 24 * 60;
                    break;
                case 'hours':
                    $defaultDurationInMinutes = $value * 60;
                    break;
                case 'minutes':
                    $defaultDurationInMinutes = $value;
                    break;
            }
        } elseif (isset($validated['default_duration_value']) && (float)$validated['default_duration_value'] === 0.0) {
            $defaultDurationInMinutes = 0;
            $defaultDurationUnit = $validated['default_duration_unit'] ?? $item->default_duration_unit ?? 'minutes';
        } else { // 工数値が入力されなかった場合、既存の値を維持するか、nullにするか。ここではnullにする。
            $defaultDurationInMinutes = null;
            $defaultDurationUnit = null;
        }


        $item->update([
            'name' => $validated['name'],
            'order' => $validated['order'],
            'default_duration' => $defaultDurationInMinutes,
            'default_duration_unit' => $defaultDurationUnit,
        ]);

        return redirect()->route('admin.process-templates.show', $processTemplate)
            ->with('success', '工程項目「' . $item->name . '」を更新しました。');
    }


    // メソッド名を destroyItem から itemsDestroy に変更
    public function destroyItem(ProcessTemplate $processTemplate, ProcessTemplateItem $item)
    {
        $this->authorize('update', $processTemplate);
        if ($item->process_template_id !== $processTemplate->id) {
            abort(404);
        }
        $item->delete();
        return redirect()->route('admin.process-templates.show', $processTemplate)
            ->with('success', 'テンプレートから工程項目を削除しました。');
    }
}

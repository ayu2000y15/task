<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ProjectCategory::orderBy('display_order')->orderBy('name')->get();
        return view('admin.project_categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project_categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:project_categories,name',
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'display_order' => 'nullable|integer|min:0',
            ], [
                'name.regex' => 'カテゴリ名は半角英数字とアンダースコアのみ使用できます。',
                'name.unique' => 'このカテゴリ名は既に使用されています。',
            ]);


            // display_orderが未指定の場合、最大値+10を設定
            if (!isset($validated['display_order'])) {
                $maxOrder = ProjectCategory::max('display_order') ?? 0;
                $validated['display_order'] = $maxOrder + 10;
            }

            $category = ProjectCategory::create($validated);

            // AJAX リクエストの場合はJSONを返す
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'id' => $category->id,
                    'name' => $category->name,
                    'display_name' => $category->display_name,
                    'description' => $category->description,
                    'display_order' => $category->display_order,
                    'message' => '案件カテゴリが正常に作成されました。'
                ], 201);
            }

            return redirect()->route('admin.project-categories.index')
                ->with('success', '案件カテゴリが正常に作成されました。');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました。',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'エラーが発生しました: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

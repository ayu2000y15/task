<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    /**
     * 権限設定画面
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);
        // rolesテーブルの取得時に、リレーション先のusersの数をカウントして一緒に取得する (users_countとしてアクセス可能になる)
        $roles = Role::with('permissions')->withCount('users')->get(); //  <- ここを修正
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0]; // 'projects.view' -> 'projects'
        });

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function create()
    {
        $this->authorize('update', Role::class); // RolePolicy@create を呼び出す
        return view('admin.roles.create');
    }

    /**
     * 新しい役割（ロール）を保存
     */
    public function store(Request $request)
    {
        $this->authorize('update', Role::class); // RolePolicy@create を呼び出す

        $validatedData = $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'display_name' => 'required|string|max:255',
        ]);

        $role = Role::create([
            'name' => $validatedData['name'],
            'display_name' => $validatedData['display_name'],
        ]); //

        return redirect()->route('admin.roles.index')->with('success', '新しい役割「' . $role->display_name . '」を追加しました。');
    }

    /**
     * 役割（ロール）に紐づく権限を更新
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role); // RolePolicy@update を呼び出す
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permissions);

        return redirect()->route('admin.roles.index')->with('success', '「' . $role->display_name . '」の権限を更新しました。');
    }

    /**
     * 役割（ロール）を削除
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        $roleDisplayName = $role->display_name;

        // ロールに紐づくユーザーがいるか確認
        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->with('error', '役割「' . $roleDisplayName . '」にはユーザーが割り当てられているため削除できません。先にユーザーからこの役割を解除してください。');
        }

        try {
            DB::transaction(function () use ($role) {
                // ロールに紐づく権限の関連を解除
                $role->permissions()->detach();
                // ロールを削除
                $role->delete();
            });

            return redirect()->route('admin.roles.index')->with('success', '役割「' . $roleDisplayName . '」を削除しました。');
        } catch (\Exception $e) {
            // エラーハンドリング (ログ記録など)
            // Log::error('ロール削除エラー: ' . $e->getMessage()); // 必要に応じてログ出力を追加
            return redirect()->route('admin.roles.index')->with('error', '役割「' . $roleDisplayName . '」の削除中に予期せぬエラーが発生しました。');
        }
    }
}

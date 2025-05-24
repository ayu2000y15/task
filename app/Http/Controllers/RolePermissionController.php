<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * 権限設定画面
     */
    public function index()
    {
        $this->authorize('roles.viewAny');
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0]; // 'projects.view' -> 'projects'
        });

        return view('roles.index', compact('roles', 'permissions'));
    }

    /**
     * 役割（ロール）に紐づく権限を更新
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('roles.update');
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permissions);

        return redirect()->route('roles.index')->with('success', '「' . $role->display_name . '」の権限を更新しました。');
    }
}

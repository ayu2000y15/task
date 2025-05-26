<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    /**
     * ユーザー一覧画面
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('roles')->paginate(20);
        return view('users.index', compact('users'));
    }

    /**
     * ユーザーの役割編集画面
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user); // UserPolicy@update が呼び出される
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * ユーザーの役割を更新
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user); // UserPolicy@update が呼び出される
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->roles()->sync($request->roles);

        return redirect()->route('users.index')->with('success', 'ユーザーの役割を更新しました。');
    }

    // 必要に応じて、UserPolicy に合わせて create, store, show, destroy メソッドも追加・修正できます。
    // 例: public function create() { $this->authorize('create', User::class); ... }
    // 例: public function destroy(User $user) { $this->authorize('delete', $user); ... }
}

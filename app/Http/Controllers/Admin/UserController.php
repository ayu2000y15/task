<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * ユーザー一覧画面
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('roles')->orderBy('id')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * ユーザーの役割編集画面
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $roles = Role::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * ユーザーの役割とステータスを更新
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        // 役割とステータスの両方に対するバリデーションを定義
        $validated = $request->validate([
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,id',
            'status' => ['required', 'string', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE,
                User::STATUS_RETIRED,
            ])],
        ], [
            'status.required' => 'ステータスは必須です。',
            'status.in' => '無効なステータスが選択されました。',
        ]);

        try {
            // データベース処理をトランザクション内で実行し、処理の安全性を確保
            DB::transaction(function () use ($user, $request, $validated) {
                // 役割を更新
                $user->roles()->sync($request->input('roles', []));

                // ステータスを更新
                $user->status = $validated['status'];

                // モデルへの変更（役割とステータス）をデータベースに保存
                $user->save();
            });
        } catch (\Exception $e) {
            // 万が一エラーが発生した場合は、エラーメッセージと共に前の画面に戻る
            return redirect()->back()->with('error', 'ユーザー情報の更新中にエラーが発生しました。')->withInput();
        }

        // 成功メッセージを、更新内容がわかるように変更
        return redirect()->route('admin.users.index')->with('success', 'ユーザー情報（役割とステータス）を更新しました。');
    }

    // 必要に応じて、UserPolicy に合わせて create, store, show, destroy メソッドも追加・修正できます。
    // 例: public function create() { $this->authorize('create', User::class); ... }
    // 例: public function destroy(User $user) { $this->authorize('delete', $user); ... }
}

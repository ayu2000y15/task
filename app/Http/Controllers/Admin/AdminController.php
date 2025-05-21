<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AccessControl;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HpHeaderService;
use App\Services\HpMasterService;
use App\Services\HpDataService;


use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    public function loginAccess(Request $request)
    {
        $user = User::where('name', '=', $request->name)
            ->where('password', '=', $request->password);

        if ($user->count() == 0) {
            return redirect()->route('login')
                ->with('error', 'ログインに失敗しました。IDかパスワードが間違っています。');
        }
        $user = $user->first();
        $root = AccessControl::select('access_id', 'access_view', 'access_root')->where('access_id', $user['access_id'])->first();
        Session::put('access_id', $root->access_id);
        Session::put('access_view', $root->access_view);
        Session::put('user_id', $user->id);
        Session::put('last_access', $user->last_access);
        return redirect()->route($root->access_root);
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login')
            ->with('error', 'ログアウトしました。再度ログインしてください');
    }

    public function dashboards()
    {
        if (!Session::has('access_view')) {
            return redirect()->route('login')
                ->with('error', 'セッションがありません。ログインしなおしてください。');
        }

        $userId = Session::get('user_id');
        $user = User::where('id', $userId)->update(['last_access' => now()]);

        $masterId = 'T005';

        $access_view = Session::get('access_view');
        return view($access_view, compact('news', 'rowIdCount'));
    }
}

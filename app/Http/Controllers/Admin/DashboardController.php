<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentMaster;
use App\Models\ContentData;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Session::has('access_view')) {
            return redirect()->route('login')
                ->with('error', 'セッションがありません。ログインしなおしてください。');
        }
        // 最終アクセス日時を取得
        $lastAccess = Session::get('last_access', now()->subDays(30)->format('Y-m-d H:i:s'));

        // 現在の日時を最終アクセス日時として保存
        $userId = Session::get('user_id');
        $user = User::where('id', $userId)->update(['last_access' => now()]);

        // マスターデータを取得
        $masters = ContentMaster::where('delete_flg', '0')->get();

        // 全データを取得
        $allData = ContentData::where('delete_flg', '0')->get();

        // T005（管理者お知らせ）のデータを取得
        $adminNewsMaster = ContentMaster::where('master_id', 'T005')
            ->where('delete_flg', '0')
            ->first();

        $adminNews = [];
        $rowIdCount = 0;

        if ($adminNewsMaster) {
            $newsData = ContentData::where('master_id', 'T005')
                ->where('delete_flg', '0')
                ->where('public_flg', '1')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($newsData->count() > 0) {
                foreach ($newsData as $index => $item) {
                    if (isset($item->content) && is_array($item->content)) {
                        foreach ($item->content as $key => $value) {
                            $adminNews[] = [
                                'row_id' => $index,
                                'col_name' => strtoupper($key),
                                'data' => $value,
                                'created_at' => $item->created_at
                            ];
                        }
                        $rowIdCount = $index;
                    }
                }
            }
        }

        return view('admin.dashboard', compact('masters', 'allData', 'adminNews', 'rowIdCount', 'lastAccess'));
    }
}

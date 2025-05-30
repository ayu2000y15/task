<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Models\User; // ユーザー名での絞り込み用

class LogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Activity::class); // LogPolicy@viewAny を呼び出す

        $query = Activity::with(['causer', 'subject']) // 操作者と操作対象をEager load
            ->latest(); // デフォルトは最新順

        // 絞り込み条件の取得
        $filters = [
            'user_name' => $request->input('user_name'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'subject_type' => $request->input('subject_type'),
            'description' => $request->input('description'), // 操作内容（メソッド）用
            'keyword' => $request->input('keyword'),
        ];

        // ユーザー名での絞り込み (causerリレーション経由)
        if (!empty($filters['user_name'])) {
            $query->whereHas('causer', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['user_name'] . '%');
            });
        }

        // 日時範囲での絞り込み (From)
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        // 日時範囲での絞り込み (To)
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // 操作対象のモデルタイプでの絞り込み
        if (!empty($filters['subject_type'])) {
            // subject_type は App\Models\Project のような形式で保存されているため、
            // 短いエイリアス (例: Project, Task) から完全なクラス名へのマッピングが必要になる場合がある。
            // ここでは完全なクラス名が直接指定されるか、部分一致で検索する。
            $query->where('subject_type', 'like', '%' . $filters['subject_type'] . '%');
        }

        // 操作内容（description に記録されたメソッドなど）での絞り込み
        if (!empty($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        // キーワード検索 (description および properties カラムを対象)
        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%' . $filters['keyword'] . '%')
                    ->orWhere('properties', 'like', '%' . $filters['keyword'] . '%'); // properties は JSON 文字列として検索
            });
        }

        $activities = $query->paginate(50)->appends($filters); // ページネーション (1ページあたり50件)

        // 絞り込み用の操作対象モデルリスト (例)
        // 実際にログが存在する subject_type を取得して選択肢にするとより良い
        $availableSubjectTypes = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(function ($item) {
                // App\Models\Project -> Project のように表示名を整形
                $parts = explode('\\', $item);
                return [$item => end($parts)];
            })
            ->sort();


        return view('admin.logs.index', [
            'activities' => $activities,
            'filters' => $filters,
            'availableSubjectTypes' => $availableSubjectTypes,
        ]);
    }
}

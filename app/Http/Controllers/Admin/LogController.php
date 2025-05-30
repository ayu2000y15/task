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
        $this->authorize('viewAny', Activity::class);

        $query = Activity::with(['causer', 'subject'])
            ->latest();

        $filters = [
            'user_name' => $request->input('user_name'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'subject_type_short' => $request->input('subject_type_short'), // ★ 短縮名で受け取る
            'event' => $request->input('event'), // ★ 操作イベントで絞り込み
            'description' => $request->input('description'),
            'keyword' => $request->input('keyword'),
        ];

        // 操作対象モデルのクラス名と表示名のマッピング (必要に応じて拡張)
        $subjectTypeMap = [
            'Project' => \App\Models\Project::class,
            'Task' => \App\Models\Task::class,
            'Character' => \App\Models\Character::class,
            'Measurement' => \App\Models\Measurement::class,
            'Material' => \App\Models\Material::class,
            'Cost' => \App\Models\Cost::class,
            'FormFieldDefinition' => \App\Models\FormFieldDefinition::class,
            'Feedback' => \App\Models\Feedback::class,
            'FeedbackCategory' => \App\Models\FeedbackCategory::class,
            'ProcessTemplate' => \App\Models\ProcessTemplate::class,
            'ProcessTemplateItem' => \App\Models\ProcessTemplateItem::class,
            'User' => \App\Models\User::class,
            'TaskFile' => \App\Models\TaskFile::class, // ファイル操作ログ用
            'FeedbackFile' => \App\Models\FeedbackFile::class, // ファイル操作ログ用
            // 他のログ対象モデルがあれば追加
        ];
        $availableSubjectTypesForFilter = array_flip(array_map('class_basename', $subjectTypeMap)); // [Project => Project, Task => Task, ...]


        if (!empty($filters['user_name'])) {
            $query->whereHas('causer', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['user_name'] . '%');
            });
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        // ★ 操作対象モデルでの絞り込み (短縮名からフルパスに変換)
        if (!empty($filters['subject_type_short']) && isset($subjectTypeMap[$filters['subject_type_short']])) {
            $query->where('subject_type', $subjectTypeMap[$filters['subject_type_short']]);
        }
        // ★ 操作イベントでの絞り込み (created, updated, deletedなど)
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }
        if (!empty($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%' . $filters['keyword'] . '%')
                    ->orWhere('properties', 'like', '%' . $filters['keyword'] . '%');
            });
        }

        $activities = $query->paginate(50)->appends($filters);

        // ★ 絞り込み用の操作イベントの選択肢
        $availableEvents = [
            'created' => '作成',
            'updated' => '更新',
            'deleted' => '削除',
            // 手動でログした場合の 'event' は spatie/laravel-activitylog v4 以降では description や log_name で
            // 区別することが多く、event カラムはモデルイベント（created, updated, deletedなど）が主に入ります。
            // ログイン・ログアウトなどのカスタムイベントを event カラムに記録している場合は、ここに追加します。
            // 今回はモデルイベントのみを主要な選択肢とします。
            // 例: 'login' => 'ログイン', 'logout' => 'ログアウト'
        ];


        return view('admin.logs.index', [
            'activities' => $activities,
            'filters' => $filters,
            'availableSubjectTypesForFilter' => $availableSubjectTypesForFilter, // ★ 変更
            'availableEvents' => $availableEvents, // ★ 追加
            'subjectTypeMap' => $subjectTypeMap, // ★ ビューでフルパス名が必要な場合のために渡す (任意)
        ]);
    }
}

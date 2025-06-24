<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'project_id',
        'parent_id',
        'character_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'duration',
        'status',
        'progress',
        'is_paused',
        // 'assignee',
        'is_milestone',
        'is_folder',
        'is_rework_task',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'progress' => 'integer',
        'is_milestone' => 'boolean',
        'is_folder' => 'boolean',
        'duration' => 'integer',
        'is_paused' => 'boolean',
        'is_rework_task' => 'boolean'
    ];

    /**
     * 工程のステータスオプション
     */
    public const STATUS_OPTIONS = [
        'not_started' => '未着手',
        'in_progress' => '進行中',
        'completed' => '完了',
        'rework'      => '直し',
        'on_hold' => '一時停止中',
        'cancelled' => 'キャンセル',
    ];

    // ★ アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "工程「{$this->name}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
    }

    // ★ イベント名を日本語に変換するヘルパーメソッド (任意)
    protected function getEventDescription(string $eventName): string
    {
        switch ($eventName) {
            case 'created':
                return '作成';
            case 'updated':
                return '更新';
            case 'deleted':
                return '削除';
            default:
                return $eventName;
        }
    }

    /**
     * モデルの起動メソッド
     *
     * @return void
     */
    protected static function booted(): void
    {
        // --- ▼▼▼ 既存の updated イベントリスナーを修正 ▼▼▼ ---
        static::updated(function (Task $task) {
            if (!$task->parent_id) {
                return; // 親がいない場合は何もしない
            }

            // 【パターン1】「直し」の子工程が「完了」になった場合
            if ($task->is_rework_task && $task->isDirty('status') && $task->status === 'completed') {
                $task->updateParentReworkStatus();
            }

            // 【パターン2】「直し」の子工程が「キャンセル」になった場合
            if ($task->is_rework_task && $task->isDirty('status') && $task->status === 'cancelled') {
                $task->updateParentReworkStatus();
            }
        });
        // --- ▲▲▲ updated イベントリスナーの修正ここまで ▲▲▲ ---


        // --- ▼▼▼ 新しく deleted イベントリスナーを追加 ▼▼▼ ---
        static::deleted(function (Task $task) {
            if (!$task->parent_id) {
                return; // 親がいない場合は何もしない
            }

            // 【パターン3】「直し」の子工程が「削除」された場合
            if ($task->is_rework_task) {
                $task->updateParentReworkStatus();
            }
        });
        // --- ▲▲▲ deleted イベントリスナーの追加ここまで ▲▲▲ ---
    }

    /**
     * 親工程の「直し」ステータスを更新すべきか判断し、実行する
     * (privateにするためprotected)
     */
    protected function updateParentReworkStatus(): void
    {
        // isDirty() などで変更をチェックするために fresh() で最新の状態を取得
        $parent = $this->parent()->first();

        if (!$parent) {
            return;
        }

        // 他に未完了・未キャンセルの「直し」子工程が残っているか確認
        $hasOtherActiveReworks = $parent->children()
            ->where('is_rework_task', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        // 他にアクティブな「直し」がなければ、親のステータスを「完了」に戻す
        if (!$hasOtherActiveReworks) {
            $parent->status = 'completed';
            $parent->save();
        }
    }

    /**
     * この工程が属する衣装案件
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * この工程に紐づくファイルを取得します。
     */
    public function files()
    {
        return $this->hasMany(\App\Models\TaskFile::class);
    }

    /**
     * この工程が属するキャラクター (nullable)
     */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * この工程の親工程
     */
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * この工程の子工程
     */
    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /**
     * この工程の作業ログ
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    /**
     * 特定のユーザーのこのタスクにおける現在アクティブな作業ログを取得
     */
    public function getActiveWorkLogForUser(User $user)
    {
        return $this->workLogs()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->first();
    }

    /**
     * 整形された工数を取得 (例: 2日 3時間, 1時間30分)
     * 1日 = 8時間作業と仮定
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (is_null($this->duration) || $this->duration < 0) {
            return null;
        }

        $totalMinutes = $this->duration;

        if ($totalMinutes === 0) {
            return '0分';
        }

        $days = floor($totalMinutes / (8 * 60)); // 1日8時間作業
        $remainingMinutesAfterDays = $totalMinutes % (8 * 60);
        $hours = floor($remainingMinutesAfterDays / 60);
        $minutes = $remainingMinutesAfterDays % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . '日';
        }
        if ($hours > 0) {
            $parts[] = $hours . '時間';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . '分';
        }

        return empty($parts) ? ($totalMinutes > 0 ? $totalMinutes . '分' : null) : implode(' ', $parts);
    }

    /**
     * 工程の階層レベルを取得
     */
    public function getLevelAttribute()
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    /**
     * 工程の全子孫を取得
     */
    public function getAllDescendants()
    {
        $descendants = collect();
        $children = $this->children()->get(); // Eloquent Collectionとして取得

        while ($children->isNotEmpty()) {
            $descendants = $descendants->merge($children);
            $childrenIds = $children->pluck('id');
            $children = Task::whereIn('parent_id', $childrenIds)->get();
        }
        return $descendants;
    }

    /**
     * 指定された工程が自身の子孫であるか（自身が祖先であるか）をチェック
     *
     * @param Task $otherTask
     * @return boolean
     */
    public function isAncestorOf(Task $otherTask): bool
    {
        $parent = $otherTask->parent;
        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }

    /**
     * 担当者との多対多リレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user');
    }
}

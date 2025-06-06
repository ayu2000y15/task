<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity; // ★ 追加
use Spatie\Activitylog\LogOptions;          // ★ 追加

class Task extends Model
{
    use HasFactory, LogsActivity; // ★ LogsActivity トレイトを追加

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
        'assignee',
        'is_milestone',
        'is_folder',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'progress' => 'integer',
        'is_milestone' => 'boolean',
        'is_folder' => 'boolean',
        'duration' => 'integer',
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
     * 工程の期間（日数）を取得
     */
    // public function getDurationAttribute($value) // ★ $value を受け取るように変更
    // {
    //     // ★ is_folder や is_milestone の場合は duration を計算しないか、固定値を返す
    //     if ($this->is_folder) {
    //         return null;
    //     }
    //     if ($this->is_milestone) {
    //         return 1; // マイルストーンは常に1日
    //     }

    //     if (is_null($this->start_date) || is_null($this->end_date)) {
    //         // ★ fillable に duration がある場合は、DBの値か計算結果を返す
    //         // ここでは元々の $fillable に duration が含まれているため、DBに保存された値を優先する
    //         // ただし、start_date/end_dateから動的に計算したい場合は、このアクセサのロジックを調整
    //         return $this->attributes['duration'] ?? null;
    //     }
    //     // ★ start_date と end_date から計算する場合
    //     // return $this->start_date->diffInDays($this->end_date) + 1;
    //     // ★ 今回は $fillable に 'duration' が含まれているため、
    //     // ★ DBに保存された値を返すか、もしくはTaskControllerの保存ロジックでdurationを計算・保存し、
    //     // ★ ここではその値を返す。以下はDBの値がnullの場合に計算する例
    //     return $this->attributes['duration'] ?? ($this->start_date && $this->end_date ? $this->start_date->diffInDays($this->end_date) + 1 : null);
    // }

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
}

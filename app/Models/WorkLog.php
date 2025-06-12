<?php
// app/Models/WorkLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;



class WorkLog extends Model
{
    use HasFactory, LogsActivity;

    /**
     * fillableから一時停止関連の項目を削除
     */
    protected $fillable = [
        'user_id',
        'task_id',
        'start_time',
        'end_time',
        'status',
        'memo',
    ];

    /**
     * castsから一時停止関連の項目を削除
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    protected $appends = ['effective_duration'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * 実働時間を計算する (秒単位)
     * 計算ロジックを単純化
     */
    public function getEffectiveDurationAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        // 単純に終了時間と開始時間の差を返す
        return $this->start_time->diffInSeconds($this->end_time);
    }

    // アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'task_id', 'start_time', 'end_time', 'status', 'memo'])
            ->logExcept(['updated_at', 'last_access'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "作業時間(ID:{$this->id})が {$this->getEventDescription($eventName)}されました")
        ;
    }

    // イベント名を日本語に変換するヘルパーメソッド
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
}

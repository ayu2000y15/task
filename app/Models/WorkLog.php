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
        'is_manually_edited',
        'edited_start_time',
        'edited_end_time',
    ];

    /**
     * castsから一時停止関連の項目を削除
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'is_manually_edited' => 'boolean',
        'edited_start_time'  => 'datetime',
        'edited_end_time'    => 'datetime',
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
     * 表示用の開始時刻を取得するアクセサ
     */
    public function getDisplayStartTimeAttribute(): ?Carbon
    {
        return $this->is_manually_edited ? $this->edited_start_time : $this->start_time;
    }

    /**
     * 表示用の終了時刻を取得するアクセサ
     */
    public function getDisplayEndTimeAttribute(): ?Carbon
    {
        return $this->is_manually_edited ? $this->edited_end_time : $this->end_time;
    }

    /**
     * 実働時間を計算する (秒単位)
     * 計算ロジックを単純化
     */
    public function getEffectiveDurationAttribute(): int
    {
        // display_start_time と display_end_time を使うことで、
        // 手動修正されているかどうかを自動的に判定します。
        $startTime = $this->display_start_time;
        $endTime = $this->display_end_time;

        if (!$startTime || !$endTime) {
            return 0;
        }

        return $startTime->diffInSeconds($endTime);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attendance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'break_seconds', // ▲ actual_work_seconds から変更
        'actual_work_seconds',
        'note',
        'status',
        'daily_salary',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_seconds' => 'integer', // ▲ キャストを変更
        'actual_work_seconds' => 'integer',
    ];

    /**
     * 手動編集された休憩・中抜け時間とのリレーション
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAttendanceSecondsAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        return $this->start_time->diffInSeconds($this->end_time);
    }


    public function getDailySalaryAttribute(): float
    {
        if (!$this->user) {
            return 0;
        }
        $hourlyRate = $this->user->getHourlyRateForDate(Carbon::parse($this->date));
        if (!$hourlyRate || $hourlyRate <= 0) {
            return 0;
        }
        return ($this->actual_work_seconds / 3600) * $hourlyRate;
    }

    /**
     * 拘束時間（秒）を計算するアクセサ
     */
    public function getDetentionSecondsAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        return $this->start_time->diffInSeconds($this->end_time);
    }

    // アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "日次勤怠(ID:{$this->id})が {$this->getEventDescription($eventName)}されました");
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'break_seconds', // ▲ actual_work_seconds から変更
        'actual_work_seconds',
        'note',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_seconds' => 'integer', // ▲ キャストを変更
        'actual_work_seconds' => 'integer',
    ];

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
}

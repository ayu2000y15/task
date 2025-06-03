<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProcessTemplateItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'process_template_id',
        'name',
        'default_duration', // This will store duration in minutes
        'order',
        'default_duration_unit', // Stores 'days', 'hours', or 'minutes'
    ];

    protected $casts = [
        'order' => 'integer',
        'default_duration' => 'integer', // Duration in minutes
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "工程テンプレート項目「{$this->name}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
    }

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

    public function processTemplate(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplate::class);
    }

    /**
     * 整形された標準工数を取得 (例: 2日, 3時間, 30分)
     * 1日 = 24時間作業と仮定
     */
    public function getFormattedDefaultDurationAttribute(): ?string
    {
        if (is_null($this->default_duration) || $this->default_duration < 0) {
            return '-'; // 工数が設定されていない場合はハイフン
        }

        $totalMinutes = $this->default_duration;

        if ($totalMinutes === 0 && $this->default_duration_unit === 'minutes') {
            return '0分';
        }
        if ($totalMinutes === 0 && $this->default_duration_unit === 'hours') {
            return '0時間';
        }
        if ($totalMinutes === 0 && $this->default_duration_unit === 'days') {
            return '0日';
        }


        // 保存されている単位に基づいて表示を試みる
        if ($this->default_duration_unit === 'days') {
            // default_duration が既に日数で保存されているわけではないので、分から再計算
            $days = floor($totalMinutes / (24 * 60));
            if ($totalMinutes % (24 * 60) === 0 && $days > 0) return $days . '日';
            // 日数単位で保存されていても、端数がある場合は詳細表示
        } elseif ($this->default_duration_unit === 'hours') {
            $hours = floor($totalMinutes / 60);
            if ($totalMinutes % 60 === 0 && $hours > 0) return $hours . '時間';
            // 時間単位で保存されていても、端数がある場合は詳細表示
        } elseif ($this->default_duration_unit === 'minutes' && $totalMinutes > 0) {
            return $totalMinutes . '分';
        }


        // 単位指定がない場合や、単位指定があっても端数がある場合は分から再計算して詳細表示
        $days = floor($totalMinutes / (24 * 60));
        $remainingMinutesAfterDays = $totalMinutes % (24 * 60);
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

        return empty($parts) ? ($totalMinutes >= 0 ? $totalMinutes . '分' : '-') : implode(' ', $parts);
    }
}

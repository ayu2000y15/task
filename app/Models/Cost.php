<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Cost extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'project_id',
        'display_order',
        'character_id',
        'item_description',
        'amount',
        'type',
        'cost_date',
        'notes', // ★ 追加
    ];

    protected $casts = [
        'cost_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "コスト「{$this->item_description}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
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

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    // ★ 追加: Projectとのリレーション
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

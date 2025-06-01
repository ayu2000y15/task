<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MeasurementTemplate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'items',
        // 'user_id', // 必要に応じて
        // 'project_id', // 必要に応じて
    ];

    protected $casts = [
        'items' => 'array', // JSONカラムを配列として扱う
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "採寸テンプレート「{$this->name}」(ID:{$this->id}) が {$this->getEventDescription($eventName)}されました");
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

    // 必要であればリレーションを定義
    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

    // public function project()
    // {
    //     return $this->belongsTo(Project::class);
    // }
}

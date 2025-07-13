<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FormFieldCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'order',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    // アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "カスタム項目カテゴリ「{$this->display_name}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
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

    /**
     * 有効なカテゴリのみを取得するスコープ
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * お知らせカテゴリを除外するスコープ
     */
    public function scopeExcludeAnnouncement($query)
    {
        return $query->where('name', '!=', 'announcement');
    }

    /**
     * 順序で並び替えるスコープ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('display_name');
    }

    /**
     * このカテゴリに属するフォームフィールド定義
     */
    public function formFieldDefinitions()
    {
        return $this->hasMany(FormFieldDefinition::class, 'category', 'name');
    }
}

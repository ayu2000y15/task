<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BoardPostType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_default',
        'is_active',
        'order'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * この投稿タイプに関連する掲示板投稿
     */
    public function boardPosts(): HasMany
    {
        return $this->hasMany(BoardPost::class, 'board_post_type_id');
    }

    /**
     * この投稿タイプで使用するカスタム項目（フォーム項目定義）
     */
    public function formFieldDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(FormFieldDefinition::class, 'board_post_type_form_field_definition')
            ->withPivot('is_required', 'order')
            ->orderBy('pivot_order');
    }

    /**
     * アクティブな投稿タイプのみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * お知らせ以外の投稿タイプを取得するスコープ
     */
    public function scopeExcludeAnnouncement($query)
    {
        return $query->where('name', '!=', 'announcement');
    }

    /**
     * 表示順でソートするスコープ（お知らせは常に最上位）
     */
    public function scopeOrdered($query)
    {
        return $query->orderByRaw("CASE WHEN name = 'announcement' THEN 0 ELSE `order` END")
            ->orderBy('display_name');
    }

    /**
     * デフォルトの投稿タイプを取得
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "投稿タイプ「{$this->display_name}」が{$this->getEventDescription($eventName)}されました");
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
}

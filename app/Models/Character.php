<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity; // ★ 追加
use Spatie\Activitylog\LogOptions;          // ★ 追加

class Character extends Model
{
    use HasFactory, LogsActivity; // ★ LogsActivity トレイトを追加

    const GENDER_UNSELECTED = null;
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';

    public static array $genders = [
        self::GENDER_UNSELECTED => '選択しない',
        self::GENDER_MALE => '男性',
        self::GENDER_FEMALE => '女性',
    ];

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'gender', // ★ 追加
    ];

    // ★ アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "キャラクター「{$this->name}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
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
     * 性別の表示用ラベルを取得
     */
    public function getGenderLabelAttribute(): string
    {
        return self::$genders[$this->gender] ?? '未設定';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(Cost::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

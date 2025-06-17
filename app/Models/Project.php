<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity; // ★ 追加
use Spatie\Activitylog\LogOptions;          // ★ 追加

class Project extends Model
{
    use HasFactory, LogsActivity; // ★ LogsActivity トレイトを追加

    protected $fillable = [
        'title',
        'series_title',
        'client_name',
        'description',
        'start_date',
        'end_date',
        'color',
        'is_favorite',
        'delivery_flag',
        'payment_flag',
        'payment',
        'status',
        'tracking_info',
        'form_definitions',
        'attributes',
        'budget',
        'target_cost',
        'target_material_cost',     // ▼▼▼ 追加 ▼▼▼
        'target_labor_cost_rate',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'is_favorite'       => 'boolean',
        'tracking_info'     => 'array',
        'form_definitions'  => 'array',
        'attributes'        => 'array',
    ];

    // ★ アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // $fillable属性の変更をログに記録
            ->logOnlyDirty() // 変更があった属性のみをログに記録
            ->dontSubmitEmptyLogs() // 空のログを送信しない
            ->setDescriptionForEvent(fn(string $eventName) => "衣装案件「{$this->title}」が{$this->getEventDescription($eventName)}されました");
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

    public const PAYMENT_FLAG_OPTIONS = [
        'Pending'        => '未払い',
        'Processing'     => '支払い中',
        'Completed'      => '支払完了',
        'Partially Paid' => '一部支払い',
        'Overdue'        => '期限切れ',
        'Cancelled'      => 'キャンセル',
        'Refunded'       => '返金済み',
        'On Hold'        => '保留中',
    ];

    public const PROJECT_STATUS_OPTIONS = [
        'not_started' => '未着手',
        'in_progress' => '進行中',
        'completed'   => '完了',
        'on_hold'     => '保留中',
        'cancelled'   => 'キャンセル',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function tasksWithoutCharacter(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('character_id');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function getCustomAttributeValue(string $key, $default = null)
    {
        $customAttributes = $this->getAttribute('attributes');
        if (!is_array($customAttributes)) {
            $customAttributes = [];
        }
        return Arr::get($customAttributes, $key, $default);
    }

    public function __get($key)
    {
        if (
            array_key_exists($key, $this->attributes) || // self::originalだと初回アクセスでエラーになることがあるためattributesに変更
            array_key_exists($key, $this->casts) ||
            method_exists($this, $key) ||
            method_exists($this, 'get' . Str::studly($key) . 'Attribute') ||
            ($this->relationLoaded(Str::snake($key)) && array_key_exists(Str::snake($key), $this->relations ?? []))
        ) {
            return parent::__get($key);
        }

        $customAttributesArrayFromGetter = $this->getAttribute('attributes');
        if (is_array($customAttributesArrayFromGetter) && array_key_exists($key, $customAttributesArrayFromGetter)) {
            return $customAttributesArrayFromGetter[$key];
        }

        return parent::__get($key);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity; // ★ 追加
use Spatie\Activitylog\LogOptions;          // ★ 追加

class FormFieldDefinition extends Model
{
    use HasFactory, LogsActivity; // ★ LogsActivity トレイトを追加

    protected $fillable = [
        'name',
        'label',
        'type',
        'options',
        'placeholder',
        'is_required',
        'order',
        'max_length',
        'is_enabled',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_enabled' => 'boolean',
        'options' => 'array',
    ];

    // ★ アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "カスタム項目定義「{$this->label}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
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

    public const FIELD_TYPES = [
        'text' => '一行テキスト',
        'textarea' => '複数行テキスト',
        'date' => '日付',
        'number' => '数値',
        'select' => 'セレクトボックス',
        'checkbox' => 'チェックボックス',
        'tel' => '電話番号',
        'email' => 'メールアドレス',
        'url' => 'URL',
        'file_multiple' => 'ファイル (複数可)',
    ];
}

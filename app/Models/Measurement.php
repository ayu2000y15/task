<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity; // ★ 追加
use Spatie\Activitylog\LogOptions;          // ★ 追加
use Illuminate\Support\Str;

class Measurement extends Model
{
    use HasFactory, LogsActivity; // ★ LogsActivity トレイトを追加

    protected $fillable = [
        'display_order',
        'character_id',
        'item',
        'value',
        'unit',
        'notes',
    ];

    // /**
    //  * ★ 採寸項目のキーと日本語ラベルの対応表を追加
    //  * このモデル内でラベル情報を一元管理します。
    //  * @var array
    //  */
    // protected static array $itemLabels = [
    //     'total_height'        => '身長',
    //     'shoulder_width'      => '肩幅',
    //     'bust'                => 'バスト',
    //     'waist'               => 'ウエスト',
    //     'hip'                 => 'ヒップ',
    //     'inseam'              => '股下',
    //     'arm_length'          => '腕の長さ',
    //     'thigh_circumference' => '太もも周り',
    //     'back_width'          => '背肩幅',
    // ];

    // ★ アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "採寸データ「{$this->item}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
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
     * ★ item_label 属性のアクセサを追加
     * $measurement->item_label と呼び出された際に、このメソッドが自動的に実行されます。
     *
     * @return string
     */
    public function getItemLabelAttribute(): string
    {
        // DBのitemカラムの値（例: 'shoulder_width'）を基に、上の対応表から日本語ラベルを返す
        return self::$itemLabels[$this->item] ?? str_replace('_', ' ', Str::title($this->item));
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}

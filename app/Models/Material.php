<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Material extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'display_order',
        'character_id',
        'inventory_item_id',
        'name',
        'supplier',
        'price',
        'unit',
        'unit_price_at_creation',
        'quantity_needed',
        'status',
        'notes', // ★ 追加
    ];

    protected $casts = [
        'price' => 'decimal:0', // 合計費用は整数表示の例 (必要に応じて変更)
        'unit_price_at_creation' => 'decimal:4', // ★追加
        'quantity_needed' => 'decimal:2', // 必要量も小数点ありうるなら
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "材料「{$this->name}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}

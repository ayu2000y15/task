<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'inventory_item_id',
        'requested_by_user_id',
        'quantity_requested',
        'status',
        'managed_by_user_id',
        'managed_at',
        'expected_delivery_date',
        'notes',
        'manager_notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'managed_at' => 'datetime',
        'expected_delivery_date' => 'date',
    ];

    public const STATUS_OPTIONS = [
        'pending' => '申請中',
        'approved' => '承認済',
        'rejected' => '却下済',
        'ordered' => '発注済',
        'partially_received' => '一部入荷済', // 必要に応じて
        'received' => '入荷済',
        'cancelled' => 'キャンセル済',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "在庫発注申請 (ID: {$this->id}, 品目: {$this->inventoryItem->name}) が {$this->getEventDescription($eventName)} されました");
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function managedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_user_id');
    }
}

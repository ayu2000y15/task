<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'user_id',
        'change_type',
        'quantity_change',
        'quantity_before_change',
        'quantity_after_change',
        'related_material_id',
        'related_stock_order_id',
        'notes',
        'unit_price_at_change',  // ★ 追加
        'total_price_at_change', // ★ 追加
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'quantity_before_change' => 'decimal:2',
        'quantity_after_change' => 'decimal:2',
        'unit_price_at_change' => 'decimal:4',  // ★ 追加 (単価は小数点以下4桁など多めに持つことも)
        'total_price_at_change' => 'decimal:2', // ★ 追加
    ];

    // ... (既存のリレーション)
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'related_material_id');
    }

    public function stockOrder(): BelongsTo
    {
        return $this->belongsTo(StockOrder::class, 'related_stock_order_id');
    }
}

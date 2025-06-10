<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_number',
        'color_number',
        'description',
        'unit',
        'quantity',
        'minimum_stock_level',
        'supplier',
        'last_stocked_at',
        'total_cost', // ★ 追加
    ];

    protected $casts = [
        'last_stocked_at' => 'datetime',
        'quantity' => 'decimal:2',
        'minimum_stock_level' => 'decimal:2',
        'total_cost' => 'decimal:2', // ★ 追加
    ];

    // ★ 平均単価を計算するアクセサを追加
    public function getAverageUnitPriceAttribute(): float
    {
        if ($this->quantity > 0) {
            return round($this->total_cost / $this->quantity, 2); // 小数点2桁で丸める (必要に応じて4桁など)
        }
        return 0;
    }
}

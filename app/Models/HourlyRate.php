<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HourlyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rate',
        'effective_date',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_date' => 'date',
    ];

    /**
     * この時給が属するユーザーを取得します。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

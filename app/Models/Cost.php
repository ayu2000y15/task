<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cost extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'item_description',
        'amount',
        'type',
        'cost_date',
    ];

    protected $casts = [
        'cost_date' => 'date',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}

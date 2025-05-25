<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'name',
        'supplier',
        'price',
        'quantity_needed',
        'status',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'display_order', // ★ 追加
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer', // ★ 追加
    ];

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}

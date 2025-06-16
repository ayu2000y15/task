<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'content',
        'is_completed',
        'completed_by',
        'completed_at',
        'order',
        'my_day_date',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'my_day_date' => 'date',
    ];

    /**
     * この項目が属する親の依頼
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * この項目を完了したユーザー
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

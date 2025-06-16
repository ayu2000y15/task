<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'title',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * この依頼を作成したユーザー (依頼者)
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * この依頼に紐づくチェックリスト項目
     */
    public function items(): HasMany
    {
        return $this->hasMany(RequestItem::class)->orderBy('order');
    }

    /**
     * この依頼の担当者 (複数)
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'request_assignees');
    }
}

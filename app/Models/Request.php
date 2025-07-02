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
        'project_id',
        'request_category_id',
        'title',
        'notes',
        'completed_at',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
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

    /**
     * この依頼が紐づく案件 (nullable)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * この依頼のカテゴリ
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RequestCategory::class, 'request_category_id');
    }
}

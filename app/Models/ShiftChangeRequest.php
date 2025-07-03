<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftChangeRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'reason',
        'requested_type',
        'requested_name',
        'requested_start_time',
        'requested_end_time',
        'requested_location',
        'requested_notes',
        'status',
        'approver_id',
        'rejection_reason',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date', // 'date'型として扱う
        'processed_at' => 'datetime', // 'datetime'型として扱う
    ];

    /**
     * この申請を行ったユーザー（申請者）を取得します。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * この申請を処理したユーザー（承認/否認者）を取得します。
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}

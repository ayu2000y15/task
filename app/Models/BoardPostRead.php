<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // BelongsTo を use

class BoardPostRead extends Model
{
    use HasFactory;
    protected $table = 'board_post_reads';
    protected $fillable = ['user_id', 'board_post_id', 'read_at'];

    /**
     * この閲覧記録に紐づくユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

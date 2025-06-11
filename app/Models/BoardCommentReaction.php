<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardCommentReaction extends Model
{
    use HasFactory;
    protected $fillable = ['comment_id', 'user_id', 'emoji'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

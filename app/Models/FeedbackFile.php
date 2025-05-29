<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}

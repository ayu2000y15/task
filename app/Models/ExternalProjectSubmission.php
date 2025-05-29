<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalProjectSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'submitter_name',
        'submitter_email',
        'submitter_notes',
        'submitted_data',
        'status',
        'processed_by_user_id',
        'processed_at',
    ];

    protected $casts = [
        'submitted_data' => 'array', // JSONデータを配列として扱う
        'processed_at' => 'datetime',
    ];

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}

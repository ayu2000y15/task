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

    public const STATUS_OPTIONS = [
        'new' => '新規',
        'in_progress' => '検討中', // 例: プロジェクト化検討中など
        'processed' => '案件化済', // 例: 案件化済み
        'on_hold' => '保留',
        'rejected' => '却下',
    ];

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    // app/Models/ExternalProjectSubmission.php
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
    ];

    /**
     * このファイルが属する工程を取得します。
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentMaster extends Model
{
    protected $table = 'content_masters';
    protected $primaryKey = 'master_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'master_id',
        'title',
        'comment',
        'schema',
        'delete_flg'
    ];

    protected $casts = [
        'schema' => 'array',
    ];

    public function contentData()
    {
        return $this->hasMany(ContentData::class, 'master_id', 'master_id');
    }
}


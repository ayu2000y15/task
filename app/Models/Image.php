<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'images';
    protected $primaryKey = 'image_id';
    public $timestamps = true;

    protected $fillable = [
        'image_id',
        'file_name',
        'file_path',
        'view_flg',
        'priority',
        'alt',
        'spare1',
        'spare2',
        'delete_flg'
    ];

    protected $dates = ['created_at', 'updated_at'];
}

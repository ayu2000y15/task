<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewFlag extends Model
{
    protected $table = 'view_flags';
    public $timestamps = true;

    protected $fillable = [
        'view_flg',
        'comment',
        'spare1',
        'spare2',
        'delete_flg',
    ];

    protected $dates = ['created_at', 'updated_at'];
}

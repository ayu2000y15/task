<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessControl extends Model
{
    protected $table = 'access_controls';
    protected $primaryKey = 'access_id';
    public $timestamps = true;

    protected $fillable = [
        'access_id','access_name', 'access_view'
    ];

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralDefinition extends Model
{
    protected $table = 'general_definitions';
    protected $primaryKey = 'definition_id';
    public $timestamps = true;

    protected $fillable = [
        'definition',
        'item',
        'explanation',
        'spare1',
        'spare2',
        'delete_flg'
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function scopeActive($query)
    {
        return $query->where('delete_flg', '0');
    }
}

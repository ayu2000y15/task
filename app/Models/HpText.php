<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HpText extends Model
{
    protected $table = 'hp_texts';
    protected $primaryKey = 'text_id';
    public $timestamps = true;

    protected $fillable = [
        'text_id',
        'content',
        'memo',
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

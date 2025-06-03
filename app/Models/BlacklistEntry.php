<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'reason', // ブラックリスト登録理由
        'added_by_user_id', // 登録したユーザーID (任意)
    ];

    public function addedByUser()
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}

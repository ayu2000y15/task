<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'sent_email_id',
        'recipient_email', // 受信者メールアドレス
        'opened_at',
        'clicked_at', // 最初のクリック日時
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function sentEmail()
    {
        return $this->belongsTo(SentEmail::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_list_id',
        'email',
        'name',
        'company_name',
        'postal_code',      // ★ 追加
        'address',          // ★ 追加
        'phone_number',     // ★ 追加
        'fax_number',       // ★ 追加
        'url',              // ★ 追加
        'representative_name', // ★ 追加
        'establishment_date', // ★ 追加
        'industry',
        // 'job_title',     // 必要なら復活
        'subscribed_at',
        'unsubscribed_at',
        'status',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'establishment_date' => 'date', // ★ 追加 (date型なのでキャスト)
    ];

    public function emailList()
    {
        return $this->belongsTo(EmailList::class);
    }

    public function getReadableStatusAttribute(): string
    {
        $statuses = [
            'subscribed' => '購読中',
            'unsubscribed' => '解除済',
            'bounced' => 'エラー',
            'pending' => '保留中',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    /**
     * この購読者に関連する送信ログを取得します。
     */
    public function sentEmailLogs() // ★★★ このメソッドを追加 ★★★
    {
        return $this->hasMany(SentEmailLog::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_list_id',
        'managed_contact_id',
        'email', // 購読時のメールアドレスとしてManagedContactからコピー
        'subscribed_at',
        'unsubscribed_at',
        'status',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        // 'establishment_date' は削除されたのでキャストも不要
    ];

    public function emailList()
    {
        return $this->belongsTo(EmailList::class);
    }

    public function managedContact()
    {
        return $this->belongsTo(ManagedContact::class);
    }

    // Subscriberモデル自体はManagedContactの属性を持たなくなるため、
    // アクセサ経由でManagedContactの属性を取得するようにビューを修正するか、
    // ビューで直接 $subscriber->managedContact->name のようにアクセスします。
    // 例:
    // public function getNameAttribute()
    // {
    //     return $this->managedContact ? $this->managedContact->name : null;
    // }
    // public function getCompanyNameAttribute()
    // {
    //     return $this->managedContact ? $this->managedContact->company_name : null;
    // }
    // ... 他の属性についても同様にアクセサを定義できますが、ビューで直接リレーション経ゆが良い場合も多いです。

    public function getReadableStatusAttribute(): string
    {
        $statuses = [
            'subscribed' => '購読中',
            'unsubscribed' => '解除済',
            'bounced' => 'エラー',
            'pending' => '一時停止中',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function sentEmailLogs()
    {
        return $this->hasMany(SentEmailLog::class);
    }
}

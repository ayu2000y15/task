<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // SoftDeletesを追加

class EmailList extends Model
{
    use HasFactory, SoftDeletes; // SoftDeletes を use

    protected $fillable = [
        'name',
        'description',
        // 'emails_count', // メールアドレスの数をキャッシュする場合など
    ];

    /**
     * このリストに紐づくメールアドレスのデータ（別テーブルで管理する場合）。
     * 今回は簡略化のため、EmailListテーブルに直接カラムを持つのではなく、
     * 別途 EmailSubscriberのようなモデル・テーブルを作成することを推奨します。
     * ここでは例としてSentEmailとのリレーションを示します。
     */
    public function sentEmails()
    {
        return $this->hasMany(SentEmail::class);
    }

    /**
     * このリストに紐づく購読者。
     */
    public function subscribers() // ★★★ このメソッドを追加 ★★★
    {
        return $this->hasMany(Subscriber::class);
    }
}

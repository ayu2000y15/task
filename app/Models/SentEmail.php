<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_list_id', // 送信対象のリストID (NULL許容で個別送信にも対応する場合)
        'subject',
        'body_html',
        'body_text', // テキスト形式のメール本文
        'sent_at',
        'sender_email',
        'sender_name',
        'status', // 例: 'sending', 'sent', 'failed', 'draft'
        // 'total_recipients', // 総送信先数
        // 'opened_count', // 開封数 (EmailTrackingから集計)
        // 'clicked_count', // クリック数 (EmailTrackingから集計)
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * このメールが送信されたメールリスト (任意)。
     */
    public function emailList()
    {
        return $this->belongsTo(EmailList::class);
    }

    /**
     * このメールのトラッキング情報。
     */
    public function trackings()
    {
        return $this->hasMany(EmailTracking::class);
    }

    /**
     * この送信メールに関連する個別の送信ログを取得します。
     */
    public function recipientLogs() // ★★★ このメソッドを追加 ★★★
    {
        return $this->hasMany(SentEmailLog::class);
    }

    /**
     * 送信メール全体のステータスを日本語で取得します。
     *
     * @return string
     */
    public function getReadableStatusAttribute(): string // ★★★ このアクセサを追加 ★★★
    {
        $statuses = [
            'queuing' => 'キュー投入中',
            'queued' => 'キュー投入済',
            'sending' => '送信中', // (将来的に使用する場合)
            'sent' => '送信完了',
            'partially_failed' => '一部失敗',
            'failed' => '送信失敗',
            'all_blacklisted_or_failed' => '全件BL等で失敗',
            'all_queue_failed' => '全件キュー投入失敗',
            'no_recipients' => '送信対象なし',
            'draft' => '下書き', // (将来的に使用する場合)
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }
}

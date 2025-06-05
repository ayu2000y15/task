<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentEmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sent_email_id',
        'subscriber_id',
        'recipient_email',
        'status',
        'error_message',
        'processed_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'message_identifier',
        'original_message_id',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /**
     * このログが関連する送信メール情報を取得します。
     */
    public function sentEmail()
    {
        return $this->belongsTo(SentEmail::class);
    }

    /**
     * このログが関連する購読者情報を取得します。
     */
    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    /**
     * ステータスを日本語で取得します。
     *
     * @return string
     */
    public function getReadableStatusAttribute(): string // ★★★ このアクセサを追加 ★★★
    {
        $statuses = [
            'queued' => 'キュー投入済',
            'sent' => '送信成功',
            'failed' => '送信失敗',
            'bounced' => 'バウンス',
            'opened' => '開封済',
            'clicked' => 'クリック済',
            'unsubscribed_via_this_email' => 'このメールで解除',
            'skipped_blacklist' => 'BLスキップ済',
            'queue_failed' => 'キュー投入失敗',
            'unsubscribed_via_link' => '配信停止希望',
            // 必要に応じて他のステータスと日本語訳を追加
        ];
        return $statuses[$this->status] ?? ucfirst($this->status); // マッピングにない場合は先頭大文字でそのまま表示
    }
}

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
            'queued' => 'キュー投入済', // SalesToolController.php で使用されているため追加
            'processing' => '処理中',
            'completed_all_sent' => '完了（全て成功）', // ユーザーリクエストの "Completed_sent" に対応
            'completed_sent' => '完了（全て成功）', // Alias for consistency if used elsewhere
            'completed_partially' => '完了（一部成功）', // ユーザーリクエスト
            'completed_all_failed_or_bounced' => '完了（全て失敗/バウンス）',
            'all_skipped' => '完了（全てスキップ）',
            'all_blacklisted' => '完了（全てブラックリスト該当）', // SalesToolController.php で使用されているため追加
            'all_queue_failed' => '完了（全てキュー投入失敗）',
            'all_skipped_or_failed' => '完了（全てスキップ/失敗）',
            'no_recipients' => '宛先なし',
            'no_recipients_processed' => '処理対象なし', // from UpdateSentEmailLogStatus
            'processing_issue' => '処理問題あり',
            'review_needed' => '確認要',
            'completed_with_no_valid_targets' => '有効対象なしで完了',
            // 必要に応じて他のステータスと日本語訳を追加
        ];

        // $this->status が null の場合や、キーが存在しない場合のフォールバック
        if (is_null($this->status) || !isset($statuses[$this->status])) {
            return ucfirst(str_replace('_', ' ', $this->status ?? '不明'));
        }

        return $statuses[$this->status];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BoardPostReaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['board_post_id', 'user_id', 'emoji'];

    /**
     * このリアクションを行ったユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このリアクションが属する投稿を取得
     */
    public function boardPost(): BelongsTo
    {
        return $this->belongsTo(BoardPost::class);
    }

    /**
     * アクティビティログのオプションを設定
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['emoji'])
            ->setDescriptionForEvent(function (string $eventName) {
                // $this->boardPost->title にアクセスするために上記のリレーションが必要
                if ($eventName === 'created') {
                    return "投稿「{$this->boardPost->title}」に「{$this->emoji}」でリアクションしました";
                }
                if ($eventName === 'deleted') {
                    return "投稿「{$this->boardPost->title}」へのリアクション「{$this->emoji}」を取り消しました";
                }
                return "リアクションが{$eventName}されました";
            });
    }
}

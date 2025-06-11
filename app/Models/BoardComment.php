<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardComment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'board_post_id',
        'user_id',
        'parent_id',
        'body',
    ];

    /**
     * このコメントを投稿したユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このコメントが属する投稿を取得
     */
    public function boardPost(): BelongsTo
    {
        return $this->belongsTo(BoardPost::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BoardComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(BoardComment::class, 'parent_id')->with('user', 'reactions', 'replies'); // 返信の返信も再帰的に読み込む
    }

    /**
     * コメント本文内のメンションをバッジに変換するためのアクセサ
     *
     * @return string
     */
    public function getFormattedBodyAttribute(): string
    {
        $cleanBody = clean($this->body);

        // 2. クリーニングされたHTMLに対して、メンションをバッジに置換する
        $formattedBody = preg_replace_callback('/@([\p{L}\p{N}_-]+)/u', function ($matches) {
            $mention = $matches[1];

            if ($mention === 'all') {
                // @all 専用のバッジ
                $badgeClass = "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 no-underline font-bold";
                return "<span class=\"{$badgeClass}\"><i class=\"fas fa-bullhorn mr-1\"></i>all</span>";
            } else {
                // 通常のメンションバッジ
                $badgeClass = "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 no-underline";
                return "<span class=\"{$badgeClass}\"><i class=\"fas fa-at mr-1\"></i>{$mention}</span>";
            }
        }, $cleanBody);

        // TinyMCEが<p>タグなどを自動で付与するため、nl2br()は不要になります。
        return $formattedBody;
    }

    // ▼▼▼【追加】アクティビティログのオプションを設定 ▼▼▼
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['body'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            // ログの説明に親投稿のタイトルを含める
            ->setDescriptionForEvent(fn(string $eventName) => "投稿「{$this->boardPost->title}」へのコメントが{$this->getEventDescription($eventName)}されました");
    }

    /**
     * ログの説明を日本語に変換するヘルパー
     * ▼▼▼【重要】このメソッドを追加または確認してください ▼▼▼
     */
    protected function getEventDescription(string $eventName): string
    {
        switch ($eventName) {
            case 'created':
                return '投稿';
            case 'updated':
                return '更新';
            case 'deleted':
                return '削除';
            default:
                return $eventName;
        }
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(BoardCommentReaction::class, 'comment_id');
    }
}

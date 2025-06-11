<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class BoardPost extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'role_id', 'title', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BoardComment::class);
    }

    public function readableUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_post_user_read');
    }

    /**
     * この投稿に付けられたタグ (多対多)
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'board_post_tag');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(BoardPostReaction::class);
    }

    /**
     * 本文内のメンションとタグをバッジに変換するためのアクセサ
     *
     * @return string
     */
    public function getFormattedBodyAttribute(): string
    {
        $body = $this->body;

        // #タグ を検索し、リンク付きのバッジに置換
        $body = preg_replace_callback('/\[([^\]]+?)\]/u', function ($matches) {
            $tag = $matches[1];
            $url = route('community.posts.index', ['tag' => $tag]);
            $badgeClass = "tag-badge inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800 transition no-underline";
            return "<a href=\"{$url}\" class=\"{$badgeClass}\"><i class=\"tag-badge fas fa-tag mr-1\"></i>{$tag}</a>";
        }, $body);

        // @ユーザー名 を検索し、バッジに置換
        $body = preg_replace_callback('/@([\p{L}\p{N}_-]+)/u', function ($matches) {
            $mention = $matches[1];

            if ($mention === 'all') {
                // @all 専用のバッジ
                $badgeClass = "mention-badge inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 no-underline font-bold";
                return "<span class=\"{$badgeClass}\"><i class=\"mention-badge fas fa-bullhorn mr-1\"></i>all</span>";
            } else {
                // 通常のメンションバッジ
                $badgeClass = "mention-badge inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300 no-underline";
                return "<span class=\"{$badgeClass}\"><i class=\"mention-badge fas fa-at mr-1\"></i>{$mention}</span>";
            }
        }, $body);

        return $body;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'body']) // これらのカラムの変更のみを記録
            ->logOnlyDirty() // 実際に変更があった場合のみログを記録
            ->dontSubmitEmptyLogs() // 空のログは記録しない
            ->setDescriptionForEvent(fn(string $eventName) => "投稿「{$this->title}」が{$this->getEventDescription($eventName)}されました");
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
}

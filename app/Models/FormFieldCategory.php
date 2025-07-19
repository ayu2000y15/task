<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FormFieldCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'type',
        'slug',
        'form_title',
        'form_description',
        'thank_you_title',
        'thank_you_message',
        'delivery_estimate_text',
        'is_external_form',
        'requires_approval',
        'send_completion_email',
        'notification_emails',
        'order',
        'is_enabled',
        'project_category_id',
    ];

    protected $casts = [
        'is_external_form' => 'boolean',
        'requires_approval' => 'boolean',
        'send_completion_email' => 'boolean',
        'is_enabled' => 'boolean',
        'notification_emails' => 'array',
    ];

    // アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "カスタム項目カテゴリ「{$this->display_name}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
    }

    protected function getEventDescription(string $eventName): string
    {
        switch ($eventName) {
            case 'created':
                return '作成';
            case 'updated':
                return '更新';
            case 'deleted':
                return '削除';
            default:
                return $eventName;
        }
    }

    // スコープメソッド
    public function scopeBoard($query)
    {
        return $query->where('type', 'board');
    }

    public function scopeForm($query)
    {
        return $query->where('type', 'form');
    }

    /**
     * 有効なカテゴリのみを取得するスコープ
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 外部フォームとして公開されているカテゴリのスコープ
     */
    public function scopeExternalForm($query)
    {
        return $query->where('is_external_form', true)->where('is_enabled', true);
    }

    /**
     * お知らせカテゴリを除外するスコープ
     */
    public function scopeExcludeAnnouncement($query)
    {
        return $query->where('name', '!=', 'announcement');
    }

    /**
     * スラッグからカテゴリを取得
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * 順序で並び替えるスコープ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('display_name');
    }

    /**
     * このカテゴリに属するフォームフィールド定義
     */
    public function formFieldDefinitions()
    {
        return $this->hasMany(FormFieldDefinition::class, 'category', 'name');
    }

    /**
     * 外部フォームのURLを生成
     */
    public function getExternalFormUrlAttribute()
    {
        if (!$this->is_external_form || !$this->slug) {
            return null;
        }
        return route('external-form.show', ['slug' => $this->slug]);
    }

    /**
     * このカテゴリが使用されているかチェック
     */
    public function isBeingUsed()
    {
        return $this->formFieldDefinitions()->exists();
    }

    /**
     * このカテゴリのフィールド数を取得
     */
    public function getFieldCount()
    {
        return $this->formFieldDefinitions()->count();
    }

    /**
     * このカテゴリの有効フィールド数を取得
     */
    public function getEnabledFieldCount()
    {
        return $this->formFieldDefinitions()->where('is_enabled', true)->count();
    }

    /**
     * 関連するプロジェクトカテゴリ
     */
    public function projectCategory()
    {
        return $this->belongsTo(ProjectCategory::class);
    }

    /**
     * 通知先メールアドレスを文字列として取得
     */
    public function getNotificationEmailsStringAttribute()
    {
        if (!$this->notification_emails) {
            return '';
        }
        return implode(', ', $this->notification_emails);
    }

    /**
     * 通知先メールアドレスを配列から設定
     */
    public function setNotificationEmailsFromString($emailString)
    {
        if (empty($emailString)) {
            $this->notification_emails = null;
            return;
        }

        $emails = array_map('trim', explode(',', $emailString));
        $emails = array_filter($emails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        $this->notification_emails = empty($emails) ? null : array_values($emails);
    }
}

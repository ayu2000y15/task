<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity; // ★ 追加
use Spatie\Activitylog\LogOptions;          // ★ 追加

class FormFieldDefinition extends Model
{
    use HasFactory, LogsActivity; // ★ LogsActivity トレイトを追加

    protected $fillable = [
        'name',
        'category',
        'label',
        'type',
        'options',
        'placeholder',
        'is_required',
        'order',
        'max_length',
        'is_enabled',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_enabled' => 'boolean',
        'options' => 'array',
    ];

    // ★ アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "案件依頼項目定義「{$this->label}」(ID:{$this->id}) が{$this->getEventDescription($eventName)}されました");
    }

    // ★ イベント名を日本語に変換するヘルパーメソッド (任意)
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

    public const FIELD_TYPES = [
        'text' => '一行テキスト',
        'textarea' => '複数行テキスト',
        'date' => '日付',
        'number' => '数値',
        'select' => 'セレクトボックス',
        'checkbox' => 'チェックボックス',
        'tel' => '電話番号',
        'email' => 'メールアドレス',
        'url' => 'URL',
        'file_multiple' => 'ファイル (複数可)',
    ];

    public const CATEGORIES = [
        'project' => '案件依頼',
        'board' => '掲示板',
        'announcement' => 'お知らせ',
        'proposal' => '企画書',
    ];

    /**
     * カテゴリでフィルタするスコープ
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 有効なフィールドのみを取得するスコープ
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 順序で並び替えるスコープ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('label');
    }

    /**
     * このフィールド定義が属するカテゴリ
     */
    public function categoryModel()
    {
        return $this->belongsTo(FormFieldCategory::class, 'category', 'name');
    }

    /**
     * このフィールド定義が使用されているかチェック
     */
    public function isBeingUsed()
    {
        // BoardPostCustomFieldValueで使用されているかチェック
        return \App\Models\BoardPostCustomFieldValue::where('form_field_definition_id', $this->id)->exists();
    }

    /**
     * このフィールド定義を使用している投稿数を取得
     */
    public function getUsageCount()
    {
        return \App\Models\BoardPostCustomFieldValue::where('form_field_definition_id', $this->id)->count();
    }

    /**
     * このフィールド定義を使用している投稿のタイトルを取得（最大5件）
     */
    public function getUsedInPosts()
    {
        return \App\Models\BoardPostCustomFieldValue::where('form_field_definition_id', $this->id)
            ->with('boardPost:id,title')
            ->limit(5)
            ->get()
            ->pluck('boardPost.title')
            ->filter()
            ->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalProjectSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'submitter_name',
        'submitter_email',
        'submitter_notes',
        'submitted_data',
        'status',
        'processed_by_user_id',
        'processed_at',
    ];

    protected $casts = [
        'submitted_data' => 'array', // JSONデータを配列として扱う
        'processed_at' => 'datetime',
    ];

    public const STATUS_OPTIONS = [
        'new' => '新規',
        'in_progress' => '検討中', // 例: プロジェクト化検討中など
        'processed' => '案件化済', // 例: 案件化済み
        'on_hold' => '保留',
        'rejected' => '却下',
    ];

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    // app/Models/ExternalProjectSubmission.php
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * フォームカテゴリを取得
     */
    public function getFormCategory()
    {
        // 新しいデータ: form_category_idから取得
        $formCategoryId = $this->submitted_data['form_category_id'] ?? null;
        if ($formCategoryId) {
            return \App\Models\FormFieldCategory::find($formCategoryId);
        }

        // 古いデータ: form_category_nameから取得
        $formCategoryName = $this->submitted_data['form_category_name'] ?? null;
        if ($formCategoryName) {
            return \App\Models\FormFieldCategory::where('name', $formCategoryName)->first();
        }

        // 最後の手段: 外部フォームの最初のカテゴリを返す（暫定対応）
        return \App\Models\FormFieldCategory::where('type', 'form')
            ->where('is_external_form', true)
            ->first();
    }

    /**
     * フォームカテゴリプロパティ（リレーションのように使える）
     */
    public function getFormCategoryAttribute()
    {
        return $this->getFormCategory();
    }

    /**
     * 案件化ボタンの表示判定
     */
    public function shouldShowProjectButton()
    {
        $formCategory = $this->getFormCategory();
        return $formCategory && $formCategory->requires_approval;
    }
}

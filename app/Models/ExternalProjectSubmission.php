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
        'form_category_id',      // ★ 追加
        'form_category_name',    // ★ 追加
    ];

    protected $casts = [
        'submitted_data' => 'array',
        'processed_at' => 'datetime',
    ];

    public const STATUS_OPTIONS = [
        'new' => '新規',
        'in_progress' => '検討中',
        'processed' => '案件化済',
        'on_hold' => '保留',
        'rejected' => '却下',
    ];

    // processedByリレーションは変更なし
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * ★ FormFieldCategoryへの新しいリレーションシップを定義
     */
    public function formCategory(): BelongsTo
    {
        return $this->belongsTo(FormFieldCategory::class, 'form_category_id');
    }

    /**
     * ★ フォームカテゴリプロパティ（アクセサ）をリファクタリング
     * 新しいリレーションを優先し、古いデータ（JSON内）はフォールバックとして扱う
     */
    public function getFormCategoryAttribute()
    {
        // 1. 新しいリレーションシップを優先して利用
        if ($this->relationLoaded('formCategory')) {
            return $this->getRelationValue('formCategory');
        }
        if ($this->form_category_id) {
            return $this->formCategory()->first();
        }

        // 2. フォールバック: 古いデータ形式（JSON内）から取得
        $formCategoryName = $this->submitted_data['form_category_name'] ?? null;
        if ($formCategoryName) {
            return FormFieldCategory::where('name', $formCategoryName)->first();
        }

        // 該当なしの場合はnullを返す
        return null;
    }

    /**
     * ★ 案件化ボタンの表示判定をリファクタリング
     * 新しいアクセサを利用して簡潔に記述
     */
    public function shouldShowProjectButton(): bool
    {
        // $this->form_category は上記の getFormCategoryAttribute() アクセサを呼び出す
        return $this->form_category && $this->form_category->requires_approval;
    }
}

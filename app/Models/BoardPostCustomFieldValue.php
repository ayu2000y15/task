<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardPostCustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_post_id',
        'form_field_definition_id',
        'value'
    ];

    /**
     * この値が属する掲示板投稿
     */
    public function boardPost(): BelongsTo
    {
        return $this->belongsTo(BoardPost::class);
    }

    /**
     * この値が対応するフォーム項目定義
     */
    public function formFieldDefinition(): BelongsTo
    {
        return $this->belongsTo(FormFieldDefinition::class);
    }

    /**
     * 値の型変換やフォーマットされた値を取得
     */
    public function getFormattedValueAttribute()
    {
        $fieldType = $this->formFieldDefinition->type ?? 'text';

        switch ($fieldType) {
            case 'date':
                return $this->value ? \Carbon\Carbon::parse($this->value)->format('Y年m月d日') : '';
            case 'number':
                return $this->value ? number_format($this->value) : '';
            case 'checkbox':
                return $this->value ? '✓' : '';
            case 'file_multiple':
                // ファイルの場合は配列として処理（必要に応じて実装）
                return $this->value;
            default:
                return $this->value;
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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

    public const FIELD_TYPES = [
        'text' => '一行テキスト',
        'textarea' => '複数行テキスト',
        'date' => '日付',
        'number' => '数値',
        'select' => 'セレクトボックス',
        'checkbox' => 'チェックボックス',
        // 'radio' => 'ラジオボタン', // 必要であれば追加
        'tel' => '電話番号',         // ★追加
        'email' => 'メールアドレス',   // ★追加
        'url' => 'URL',              // ★追加
        'file_multiple' => 'ファイル (複数可)', // ★追加 (内部名は file_multiple)
    ];
}

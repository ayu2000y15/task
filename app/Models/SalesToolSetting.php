<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesToolSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'send_interval_minutes',
        'emails_per_batch',
        'image_sending_enabled',
        'batch_delay_seconds', // ★ 必要であればこのカラムをマイグレーションで追加
    ];

    protected $casts = [
        'image_sending_enabled' => 'boolean',
        'send_interval_minutes' => 'integer',
        'emails_per_batch' => 'integer',
        'batch_delay_seconds' => 'integer', // ★
    ];

    /**
     * 設定値を取得するスタティックメソッドの例
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        // sales_tool_settings テーブルには1レコードしか存在しない想定
        $settings = self::first();
        if ($settings && isset($settings->{$key})) {
            return $settings->{$key};
        }
        return $default;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesToolSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'max_emails_per_minute', // 1分あたりの最大メール送信数 (固定間隔用)
        'image_sending_enabled', // メール本文への画像送信の可否
        'send_timing_type',      // ★追加: 送信タイミングの種類 ('fixed', 'random')
        'random_send_min_seconds', // ★追加: ランダム送信の最小間隔 (秒)
        'random_send_max_seconds', // ★追加: ランダム送信の最大間隔 (秒)
        'daily_send_limit',      // ★追加: 1日の送信上限数
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'image_sending_enabled' => 'boolean',
        'max_emails_per_minute' => 'integer',
        'random_send_min_seconds' => 'integer', // ★追加
        'random_send_max_seconds' => 'integer', // ★追加
        'daily_send_limit' => 'integer',      // ★追加
    ];

    /**
     * 設定値を取得するスタティックメソッド。
     * sales_tool_settings テーブルには1レコードのみ存在し、
     * そのIDが1であることを前提としています。
     *
     * @param string $key 設定名
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        // 常にID=1のレコードを対象とするか、first()で最初のレコードを取得
        $settings = self::first(); // もしくは self::find(1);

        if ($settings && isset($settings->{$key})) {
            return $settings->{$key};
        }
        return $default;
    }

    /**
     * 設定を更新または作成するスタティックメソッドの例。
     * sales_tool_settings テーブルにID=1のレコードが1つだけあることを前提とします。
     *
     * @param array $data 更新するデータ
     * @return self
     */
    public static function updateSettings(array $data): self
    {
        // ID=1のレコードを更新、なければ作成
        return self::updateOrCreate(
            ['id' => 1], // 検索条件 (常にID=1のレコードを対象)
            $data        // 更新または作成するデータ
        );
    }
}

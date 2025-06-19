<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; // 必要であればソフトデリートを有効化
use Carbon\Carbon; // Carbon を use する

class ManagedContact extends Model
{
    use HasFactory; // 必要であれば SoftDeletes を追加

    protected $fillable = [
        'email',
        'name',
        'company_name',
        'postal_code',
        'address',
        'phone_number',
        'fax_number',
        'url',
        'representative_name',
        'establishment_date',
        'industry',
        'notes',
        'status',
        'source_info',
    ];


    /**
     * ステータスの選択肢
     * @var array
     */
    public const STATUS_OPTIONS = [
        'active' => '有効', //
        'do_not_contact' => '連絡不要', //
        'archived' => 'アーカイブ済', //
    ];
    /**
     * ステータスの設定（表示ラベルとCSSクラス）を取得します。
     *
     * @param string|null $status
     * @return array
     */
    public static function getStatusConfig(string $status = null): array
    {
        $statuses = [
            'active' => ['label' => '有効', 'class' => 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200'],
            'do_not_contact' => ['label' => '連絡不要', 'class' => 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200'],
            'archived' => ['label' => 'アーカイブ済', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200'],
        ];

        if ($status === null) {
            // 全てのステータス設定を返す（例: フィルターのオプション用）
            return $statuses;
        }

        return $statuses[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300'];
    }

    /**
     * この管理連絡先に関連する全ての購読者を取得します。
     */
    public function subscribers() // ★ このリレーションメソッドを追加
    {
        return $this->hasMany(Subscriber::class);
    }

    /**
     * updated_at のアクセサ (ビューで Carbon のメソッドを直接使えるように)
     *
     * @param  string  $value
     * @return \Carbon\Carbon
     */
    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }

    /**
     * created_at のアクセサ (ビューで Carbon のメソッドを直接使えるように)
     *
     * @param  string  $value
     * @return \Carbon\Carbon
     */
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'series_title',
        'client_name',
        'description',
        'start_date',
        'end_date',
        'color',
        'is_favorite',
        'delivery_flag',
        'payment_flag',
        'payment',
        'status',
        'form_definitions',
        'attributes',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'is_favorite'       => 'boolean',
        'form_definitions'  => 'array',
        'attributes'        => 'array',
    ];

    public const PAYMENT_FLAG_OPTIONS = [
        'Pending'        => '未払い',
        'Processing'     => '支払い中',
        'Completed'      => '支払完了',
        'Partially Paid' => '一部支払い',
        'Overdue'        => '期限切れ',
        'Cancelled'      => 'キャンセル',
        'Refunded'       => '返金済み',
        'On Hold'        => '保留中',
    ];

    public const PROJECT_STATUS_OPTIONS = [
        'not_started' => '未着手',
        'in_progress' => '進行中',
        'completed'   => '完了',
        'on_hold'     => '保留中',
        'cancelled'   => 'キャンセル',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function tasksWithoutCharacter(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('character_id');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function getCustomAttributeValue(string $key, $default = null)
    {
        // getAttribute() を使って、キャストが適用された 'attributes' カラムの値を取得
        $customAttributes = $this->getAttribute('attributes');

        // $customAttributes が null や配列でない場合（例: DBカラムがNULL）のフォールバック
        if (!is_array($customAttributes)) {
            $customAttributes = [];
        }

        return Arr::get($customAttributes, $key, $default);
    }

    public function __get($key)
    {
        if (
            array_key_exists($key, $this->original) ||
            array_key_exists($key, $this->casts) ||
            method_exists($this, $key) ||
            method_exists($this, 'get' . Str::studly($key) . 'Attribute') ||
            ($this->relationLoaded(Str::snake($key)) && array_key_exists(Str::snake($key), $this->relations ?? []))
        ) {
            return parent::__get($key);
        }

        // __get 内でも getAttributeValue を経由するか、同様のロジックでキャスト済みの値を取得
        // ここでは getCustomAttributeValue に処理を委任する形も考えられるが、
        // パフォーマンスや直接的な属性アクセスとの兼ね合いで、現状の __get のロジックが
        // 意図した動作をしているか確認が必要。
        // 今回の直接的な問題は getCustomAttributeValue のため、そちらを優先して修正。
        // ただし、__get もキャストされた配列を期待しているはずなので、
        // $this->attributes['attributes'] が常にキャスト済み配列を返すか、
        // あるいは $this->getAttribute('attributes') を使うべきか、という点は同様。
        // 今回は getCustomAttributeValue の修正に絞り、__get は元のロジックを維持しつつ、
        // getCustomAttributeValue が正しく動けば __get も間接的に恩恵を受けるか、
        // 別途 __get も getAttribute('attributes') ベースに修正する必要があるかもしれない。

        $customAttributesArrayFromGetter = $this->getAttribute('attributes'); // キャストされた値を取得
        if (is_array($customAttributesArrayFromGetter) && array_key_exists($key, $customAttributesArrayFromGetter)) {
            return $customAttributesArrayFromGetter[$key];
        }
        // 元の __get のロ_ジックも残す（ただし、上記でreturnされればここは通らない）
        // $customAttributesArray = $this->attributes['attributes'] ?? null;
        // if (is_array($customAttributesArray) && array_key_exists($key, $customAttributesArray)) {
        // return $customAttributesArray[$key];
        // }


        return parent::__get($key);
    }
}

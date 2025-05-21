<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentData extends Model
{
    protected $table = 'content_data';
    protected $primaryKey = 'data_id';

    protected $fillable = [
        'master_id',
        'content',
        'public_flg',
        'sort_order',
        'delete_flg'
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function contentMaster()
    {
        return $this->belongsTo(ContentMaster::class, 'master_id', 'master_id');
    }

    /**
     * contentの特定のキーの値を取得する
     *
     * @param string $key キー名
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function getContentValue($key, $default = null)
    {
        return $this->content[$key] ?? $default;
    }

    /**
     * contentの最初のキーの値を取得する
     *
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function getFirstContentValue($default = null)
    {
        if (empty($this->content)) {
            return $default;
        }

        return reset($this->content) ?: $default;
    }

    /**
     * contentの値を全て取得する
     *
     * @return array
     */
    public function getAllContentValues()
    {
        return $this->content ?? [];
    }

    /**
     * contentの値を指定したキーのみ取得する
     *
     * @param array $keys 取得したいキーの配列
     * @return array
     */
    public function getSelectedContentValues(array $keys)
    {
        $result = [];

        foreach ($keys as $key) {
            if (isset($this->content[$key])) {
                $result[$key] = $this->content[$key];
            }
        }

        return $result;
    }
}

<?php

namespace App\Services;

use App\Models\ContentData;
use App\Models\ContentMaster;
use Illuminate\Support\Facades\DB;

class ContentDataService
{
    public function getAllData()
    {
        return ContentData::where('delete_flg', '0')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDataByMasterId($masterId)
    {
        return ContentData::where('master_id', $masterId)
            ->where('delete_flg', '0')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDataById($dataId)
    {
        return ContentData::where('data_id', $dataId)
            ->where('delete_flg', '0')
            ->first();
    }

    public function getPublicDataByMasterId($masterId)
    {
        return ContentData::where('master_id', $masterId)
            ->where('public_flg', '1')
            ->where('delete_flg', '0')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPublicDataByDataId($dataId)
    {
        return ContentData::where('data_id', $dataId)
            ->where('public_flg', '1')
            ->where('delete_flg', '0')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store($masterId, $content, $publicFlg = '0', $sortOrder = 0)
    {
        // マスターが存在するか確認
        $master = ContentMaster::where('master_id', $masterId)
            ->where('delete_flg', '0')
            ->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターIDが見つかりません。"
            ];
        }

        // スキーマに基づいてバリデーション
        $schema = $master->schema;
        $validatedContent = [];

        foreach ($schema as $field) {
            $colName = $field['col_name'];
            $requiredFlg = $field['required_flg'] ?? '0';

            // 必須項目チェック
            if ($requiredFlg === '1' && (!isset($content[$colName]) || empty($content[$colName]))) {
                return [
                    "status" => "error",
                    "mess" => "{$field['view_name']}は必須項目です。"
                ];
            }

            $validatedContent[$colName] = $content[$colName] ?? null;
        }

        // データ保存
        $contentData = new ContentData();
        $contentData->master_id = $masterId;
        $contentData->content = $validatedContent;
        $contentData->public_flg = $publicFlg;
        $contentData->sort_order = $sortOrder;
        $contentData->save();

        return [
            "status" => "success",
            "mess" => "データが登録されました。",
            "data_id" => $contentData->data_id
        ];
    }

    public function update($dataId, $content, $publicFlg = null, $sortOrder = null)
    {
        $contentData = ContentData::where('data_id', $dataId)
            ->where('delete_flg', '0')
            ->first();

        if (!$contentData) {
            return [
                "status" => "error",
                "mess" => "データが見つかりません。"
            ];
        }

        // マスターのスキーマを取得
        $master = ContentMaster::where('master_id', $contentData->master_id)
            ->where('delete_flg', '0')
            ->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターデータが見つかりません。"
            ];
        }

        // スキーマに基づいてバリデーション
        $schema = $master->schema;
        $currentContent = $contentData->content;
        $updatedContent = [];

        foreach ($schema as $field) {
            $colName = $field['col_name'];
            $requiredFlg = $field['required_flg'] ?? '0';

            // コンテンツに含まれている場合は更新、そうでなければ現在の値を維持
            if (array_key_exists($colName, $content)) {
                // 必須項目チェック
                if ($requiredFlg === '1' && empty($content[$colName]) && $field['type'] !== 'files') {
                    return [
                        "status" => "error",
                        "mess" => "{$field['view_name']}は必須項目です。"
                    ];
                }

                $updatedContent[$colName] = $content[$colName];
            } else {
                $updatedContent[$colName] = $currentContent[$colName] ?? null;
            }
        }

        // データ更新
        $contentData->content = $updatedContent;

        if ($publicFlg !== null) {
            $contentData->public_flg = $publicFlg;
        }

        if ($sortOrder !== null) {
            $contentData->sort_order = $sortOrder;
        }

        $contentData->save();

        return [
            "status" => "success",
            "mess" => "データが更新されました。"
        ];
    }

    public function delete($dataId)
    {
        $contentData = ContentData::where('data_id', $dataId)->first();

        if (!$contentData) {
            return [
                "status" => "error",
                "mess" => "データが見つかりません。"
            ];
        }

        $contentData->delete_flg = '1';
        $contentData->save();

        return [
            "status" => "success",
            "mess" => "データが削除されました。"
        ];
    }

    public function updateOrder($sortData)
    {
        DB::beginTransaction();

        try {
            foreach ($sortData as $item) {
                $dataId = $item['id'];
                $order = $item['order'];

                ContentData::where('data_id', $dataId)
                    ->update(['sort_order' => $order]);
            }

            DB::commit();

            return [
                "status" => "success",
                "mess" => "表示順が更新されました。"
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                "status" => "error",
                "mess" => "表示順の更新に失敗しました。" . $e->getMessage()
            ];
        }
    }

    /**
     * コンテンツデータのcontentフィールドのみを更新
     */
    public function updateContent($dataId, $content)
    {
        try {
            $contentData = $this->getDataById($dataId);

            if (!$contentData) {
                return [
                    'status' => 'error',
                    'mess' => 'データが見つかりません。'
                ];
            }

            $contentData->content = $content;
            $contentData->save();

            return [
                'status' => 'success',
                'mess' => 'コンテンツを更新しました。'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'mess' => 'コンテンツの更新中にエラーが発生しました。'
            ];
        }
    }

    /**
     * マスターID、データIDを指定してコンテンツデータを取得する汎用メソッド
     *
     * @param string $masterId マスターID
     * @param int $limit 取得件数（0の場合は全件取得）
     * @param array $mapping キーのマッピング配列
     * @param array $options 追加オプション
     * @return array コンテンツデータの配列
     */
    public function getContentByMasterId($masterId, $limit = 0, $mapping = [], $options = [], $dataId = null)
    {
        if ($dataId == null) {
            $content = ContentData::where('master_id', $masterId)
                ->where('delete_flg', '0')
                ->where('public_flg', '1')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $content = ContentData::where('master_id', $masterId)
                ->where('data_id', $dataId)
                ->where('delete_flg', '0')
                ->where('public_flg', '1')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $defaultMapping = [
            'id' => 'id',
            'public_flg' => 'public_flg',
            'priority' => 'priority',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        $i = 0;
        foreach ($content as $item) {
            foreach ($item->content as $key => $value) {
                // デフォルトのマッピング
                $defaultMapping += [
                    $key => $key,
                ];
                $i++;
            }
        }

        // マッピングをマージ
        $mapping = array_merge($defaultMapping, $mapping);

        $defaultOptions = [
            ['sort_order', true],
            ['priority', true],
            ['created_at', true]
        ];

        if ($options == []) {
            $options = $defaultOptions;
        }

        // // オプションをマージ
        // $options = array_merge($defaultOptions, $options);

        // コンテンツデータを取得
        if ($dataId == null) {
            $contentData = $this->getPublicDataByMasterId($masterId);
        } else {
            $contentData = $this->getPublicDataByDataId($dataId);
        }
        $result = [];

        foreach ($contentData as $content) {
            // 基本データを準備
            $item = [
                $mapping['id'] => $content->data_id,
                $mapping['public_flg'] => $content->public_flg,
                $mapping['priority'] => $content->sort_order,
                $mapping['created_at'] => $content->created_at,
                $mapping['updated_at'] => $content->updated_at
            ];

            // コンテンツフィールドを追加
            foreach ($content->getAllContentValues() as $key => $value) {
                // マッピングがあればそれを使用、なければオリジナルのキー名を使用
                $mappedKey = $mapping[$key] ?? $key;
                $item[$mappedKey] = $value;
            }
            $result[] = (object)$item;
        }
        // 結果をソート
        $result = collect($result)->sortBy($options)->values();

        // 件数制限がある場合
        if ($limit > 0) {
            $result = $result->take($limit);
        }

        $result = $result->all();

        //dataIdがnullでない場合
        if ($dataId <> null) {
            foreach ($result as $r) {
                $result = $r;
            }
        }

        return $result;
    }

    /**
     * マスターIDを指定して、スキーマの表示名と値を組み合わせたデータを取得する
     *
     * @param string $masterId マスターID
     * @param int|null $dataId 特定のデータIDを指定する場合（nullの場合は最初のデータを使用）
     * @return array スキーマの表示名と値を組み合わせた配列
     */
    public function getContentWithSchema($masterId, $dataId = null)
    {
        // マスターのスキーマを取得
        $master = ContentMaster::where('master_id', $masterId)
            ->where('delete_flg', '0')
            ->first();

        if (!$master || !isset($master->schema)) {
            return [];
        }

        // コンテンツデータを取得
        $contentData = null;

        if ($dataId) {
            // 特定のデータIDが指定されている場合
            $contentData = $this->getDataById($dataId);
        } else {
            // 指定がない場合は公開データの最初のものを使用
            $dataList = $this->getPublicDataByMasterId($masterId);
            if (count($dataList) > 0) {
                $contentData = $dataList[0];
            }
        }

        if (!$contentData || !isset($contentData->content)) {
            return [];
        }

        // スキーマと値を組み合わせる
        $result = [];
        $schema = $master->schema;
        $content = $contentData->content;

        foreach ($schema as $field) {
            $colName = $field['col_name'];
            if (isset($content[$colName])) {
                $result[] = [
                    'col_name' => $colName,
                    'view_name' => $field['view_name'],
                    'value' => $content[$colName],
                    'type' => $field['type'] ?? 'text'
                ];
            }
        }

        return $result;
    }

    /**
     * マスターIDを指定してスキーマ情報のみを取得する
     *
     * @param string $masterId マスターID
     * @param bool $sortByOrder 表示順でソートするかどうか（デフォルトはtrue）
     * @param bool $publicOnly 公開フィールドのみを取得するかどうか（デフォルトはfalse）
     * @return array スキーマ情報の配列
     */
    public function getSchemaByMasterId($masterId, $sortByOrder = true, $publicOnly = false)
    {
        // マスターのスキーマを取得
        $master = ContentMaster::where('master_id', $masterId)
            ->where('delete_flg', '0')
            ->first();

        if (!$master || !isset($master->schema)) {
            return [];
        }

        $schema = $master->schema;

        // 公開フィールドのみを取得する場合
        if ($publicOnly) {
            $schema = array_filter($schema, function ($field) {
                return isset($field['public_flg']) && $field['public_flg'] === '1';
            });
        }

        // 表示順でソートする場合
        if ($sortByOrder) {
            usort($schema, function ($a, $b) {
                $sortA = isset($a['sort_order']) ? (int)$a['sort_order'] : 0;
                $sortB = isset($b['sort_order']) ? (int)$b['sort_order'] : 0;
                return $sortA - $sortB;
            });
        }

        return $schema;
    }

    /**
     * マスターIDを指定して特定のフィールドのスキーマ情報を取得する
     *
     * @param string $masterId マスターID
     * @param string $colName カラム名
     * @return array|null スキーマ情報、見つからない場合はnull
     */
    public function getFieldSchema($masterId, $colName)
    {
        $schema = $this->getSchemaByMasterId($masterId, false);

        foreach ($schema as $field) {
            if ($field['col_name'] === $colName) {
                return $field;
            }
        }

        return null;
    }

    /**
     * コンテンツデータの公開状態を更新
     */
    public function updatePublicStatus($dataId, $publicFlg)
    {
        try {
            $data = ContentData::findOrFail($dataId);
            $data->public_flg = $publicFlg;
            $data->save();

            return [
                'status' => 'success',
                'mess' => $publicFlg == '1' ? 'データを公開しました。' : 'データを非公開にしました。'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'mess' => 'データの更新に失敗しました: ' . $e->getMessage()
            ];
        }
    }
}

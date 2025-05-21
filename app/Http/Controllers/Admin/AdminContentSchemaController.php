<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentMasterService;
use App\Services\ContentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminContentSchemaController extends Controller
{
    protected $contentMaster;
    protected $contentData;

    public function __construct(ContentMasterService $contentMaster, ContentDataService $contentData)
    {
        $this->contentMaster = $contentMaster;
        $this->contentData = $contentData;
    }

    public function index()
    {
        $masters = $this->contentMaster->getMasterAll();
        return view('admin.content-schema', compact('masters'));
    }

    public function addField(Request $request)
    {
        $validatedData = $request->validate([
            'master_id' => 'required|string',
            'col_name' => 'required|string',
            'view_name' => 'required|string',
            'type' => 'required|string',
            'sort_order' => 'required|integer|min:0',
            'required_flg' => 'required|string',
            'public_flg' => 'required|string',
            'options' => 'nullable|string', // selectの選択肢用
            'array_items.name.*' => 'nullable|string', // 配列項目名
            'array_items.type.*' => 'nullable|string', // 配列項目タイプ
        ]);

        $field = [
            'col_name' => $validatedData['col_name'],
            'view_name' => $validatedData['view_name'],
            'type' => $validatedData['type'],
            'sort_order' => $validatedData['sort_order'],
            'required_flg' => $validatedData['required_flg'],
            'public_flg' => $validatedData['public_flg'],
        ];

        // selectタイプの場合は選択肢を追加
        if ($validatedData['type'] === 'select' && !empty($validatedData['options'])) {
            $options = $this->parseOptions($validatedData['options']);
            if (!empty($options)) {
                $field['options'] = $options;
            }
        }

        // arrayタイプの場合は配列項目を追加
        if ($validatedData['type'] === 'array' && isset($request->array_items)) {
            $arrayItems = $this->parseArrayItems($request->array_items);
            if (!empty($arrayItems)) {
                $field['array_items'] = $arrayItems;
            }
        }

        $result = $this->contentMaster->addSchemaField($validatedData['master_id'], $field);

        // スキーマ追加を既存データに反映（空の値で追加）
        if ($result["status"] === "success") {
            $this->addFieldToExistingData(
                $validatedData['master_id'],
                $validatedData['col_name']
            );
        }

        return redirect()->route('admin.content-schema')
            ->with($result["status"], $result["mess"])
            ->with('active_master_id', $validatedData['master_id']);
    }

    public function updateField(Request $request)
    {
        $validatedData = $request->validate([
            'master_id' => 'required|string',
            'original_col_name' => 'required|string',
            'col_name' => 'required|string',
            'view_name' => 'required|string',
            'type' => 'required|string',
            'sort_order' => 'required|integer|min:0',
            'required_flg' => 'required|string',
            'public_flg' => 'required|string',
            'options' => 'nullable|string', // selectの選択肢用
            'array_items.name.*' => 'nullable|string', // 配列項目名
            'array_items.type.*' => 'nullable|string', // 配列項目タイプ
        ]);

        $field = [
            'col_name' => $validatedData['col_name'],
            'view_name' => $validatedData['view_name'],
            'type' => $validatedData['type'],
            'sort_order' => $validatedData['sort_order'],
            'required_flg' => $validatedData['required_flg'],
            'public_flg' => $validatedData['public_flg'],
        ];

        // selectタイプの場合は選択肢を追加
        if ($validatedData['type'] === 'select') {
            if (!empty($validatedData['options'])) {
                $options = $this->parseOptions($validatedData['options']);
                if (!empty($options)) {
                    $field['options'] = $options;
                }
            } else {
                // 空の選択肢の場合は空の配列を設定
                $field['options'] = [];
            }
        }

        // arrayタイプの場合は配列項目を追加
        if ($validatedData['type'] === 'array') {
            if (isset($request->array_items)) {
                $arrayItems = $this->parseArrayItems($request->array_items);
                if (!empty($arrayItems)) {
                    $field['array_items'] = $arrayItems;
                }
            } else {
                // 空の配列項目の場合は空の配列を設定
                $field['array_items'] = [];
            }
        }

        $result = $this->contentMaster->updateSchemaField(
            $validatedData['master_id'],
            $validatedData['original_col_name'],
            $field
        );

        // スキーマ変更を既存データに反映
        if ($result["status"] === "success") {
            $this->updateExistingData(
                $validatedData['master_id'],
                $validatedData['original_col_name'],
                $validatedData['col_name']
            );
        }

        return redirect()->route('admin.content-schema')
            ->with($result["status"], $result["mess"])
            ->with('active_master_id', $validatedData['master_id']);
    }

    public function deleteField(Request $request)
    {
        $validatedData = $request->validate([
            'master_id' => 'required|string',
            'col_name' => 'required|string',
        ]);

        $result = $this->contentMaster->deleteSchemaField(
            $validatedData['master_id'],
            $validatedData['col_name']
        );

        // フィールド削除を既存データに反映
        if ($result["status"] === "success") {
            $this->removeFieldFromExistingData(
                $validatedData['master_id'],
                $validatedData['col_name']
            );
        }

        return redirect()->route('admin.content-schema')
            ->with($result["status"], $result["mess"])
            ->with('active_master_id', $validatedData['master_id']);
    }

    public function updateOrder(Request $request, $masterId)
    {
        $validatedData = $request->validate([
            'schema_order' => 'required|json',
        ]);

        $schemaOrder = json_decode($validatedData['schema_order'], true);

        $result = $this->contentMaster->updateSchemaOrder($masterId, $schemaOrder);

        return redirect()->route('admin.content-schema')
            ->with($result["status"], $result["mess"])
            ->with('active_master_id', $masterId);
    }

    /**
     * 選択肢テキストを解析して配列に変換する
     *
     * @param string $optionsText 選択肢テキスト（各行が「値:表示名」の形式）
     * @return array 選択肢の配列
     */
    private function parseOptions($optionsText)
    {
        $options = [];
        // 改行コードを統一して分割
        $lines = preg_split('/\r\n|\r|\n/', $optionsText);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 値:表示名 の形式をチェック
            if (strpos($line, ':') !== false) {
                list($value, $label) = explode(':', $line, 2);
                $value = trim($value);
                $label = trim($label);

                if (!empty($value)) {
                    $options[] = [
                        'value' => $value,
                        'label' => $label ?: $value
                    ];
                }
            } else {
                // 区切り文字がない場合は値と表示名を同じにする
                $value = $line;
                $options[] = [
                    'value' => $value,
                    'label' => $value
                ];
            }
        }

        return $options;
    }

    /**
     * 配列項目データを解析して配列に変換する
     *
     * @param array $arrayItemsData 配列項目データ
     * @return array 配列項目の配列
     */
    private function parseArrayItems($arrayItemsData)
    {
        $items = [];

        if (!isset($arrayItemsData['name']) || !isset($arrayItemsData['type'])) {
            return $items;
        }

        $names = $arrayItemsData['name'];
        $types = $arrayItemsData['type'];

        foreach ($names as $index => $name) {
            $name = trim($name);
            if (empty($name)) continue;

            $type = isset($types[$index]) ? trim($types[$index]) : 'text';

            $items[] = [
                'name' => $name,
                'type' => $type
            ];
        }

        return $items;
    }

    /**
     * スキーマ変更を既存データに反映する
     *
     * @param string $masterId マスターID
     * @param string $originalColName 元のカラム名
     * @param string $newColName 新しいカラム名
     * @return void
     */
    private function updateExistingData($masterId, $originalColName, $newColName)
    {
        // カラム名が変更された場合のみ処理
        if ($originalColName !== $newColName) {
            $contentData = $this->contentData->getDataByMasterId($masterId);

            foreach ($contentData as $data) {
                $content = $data->content;

                // 元のカラムが存在する場合、新しいカラム名に変更
                if (isset($content[$originalColName])) {
                    $content[$newColName] = $content[$originalColName];
                    unset($content[$originalColName]);

                    // データを更新
                    DB::table('content_data')
                        ->where('data_id', $data->data_id)
                        ->update(['content' => json_encode($content)]);
                }
            }
        }
    }

    /**
     * 削除されたフィールドを既存データから削除する
     *
     * @param string $masterId マスターID
     * @param string $colName 削除するカラム名
     * @return void
     */
    private function removeFieldFromExistingData($masterId, $colName)
    {
        $contentData = $this->contentData->getDataByMasterId($masterId);

        foreach ($contentData as $data) {
            $content = $data->content;

            // カラムが存在する場合、削除
            if (isset($content[$colName])) {
                unset($content[$colName]);

                // データを更新
                DB::table('content_data')
                    ->where('data_id', $data->data_id)
                    ->update(['content' => json_encode($content)]);
            }
        }
    }

    /**
     * 新しいフィールドを既存データに空の値で追加する
     *
     * @param string $masterId マスターID
     * @param string $colName 追加するカラム名
     * @return void
     */
    private function addFieldToExistingData($masterId, $colName)
    {
        $contentData = $this->contentData->getDataByMasterId($masterId);

        foreach ($contentData as $data) {
            $content = $data->content ?? [];

            // 既にカラムが存在しない場合のみ、空の値で追加
            if (!isset($content[$colName])) {
                $content[$colName] = '';

                // データを更新
                DB::table('content_data')
                    ->where('data_id', $data->data_id)
                    ->update(['content' => json_encode($content)]);
            }
        }
    }
}

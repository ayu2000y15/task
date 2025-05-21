<?php

namespace App\Services;

use App\Models\ContentMaster;
use Illuminate\Support\Facades\DB;

class ContentMasterService
{
    public function getMasterAll()
    {
        return ContentMaster::where('delete_flg', '0')
            ->orderBy('master_id')
            ->get();
    }

    public function getMasterById($masterId)
    {
        return ContentMaster::where('master_id', $masterId)
            ->where('delete_flg', '0')
            ->first();
    }

    public function getMasterInId($masterId)
    {
        return ContentMaster::whereRaw('master_id in (' . $masterId . ')')
            ->where('delete_flg', '0')
            ->get();
    }

    public function updateSchema($masterId, $schema)
    {
        $master = ContentMaster::where('master_id', $masterId)->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターIDが見つかりません。"
            ];
        }

        $currentSchema = $master->schema ?? [];
        $newSchema = array_merge($currentSchema, $schema);

        $master->schema = $newSchema;
        $master->save();

        return [
            "status" => "success",
            "mess" => "スキーマが更新されました。"
        ];
    }

    public function addSchemaField($masterId, $field)
    {
        $master = ContentMaster::where('master_id', $masterId)->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターIDが見つかりません。"
            ];
        }

        $schema = $master->schema ?? [];

        // 同じcol_nameが存在するか確認
        foreach ($schema as $existingField) {
            if ($existingField['col_name'] === $field['col_name']) {
                return [
                    "status" => "error",
                    "mess" => "同じカラム名が既に存在します。"
                ];
            }
        }

        $schema[] = $field;
        $master->schema = $schema;
        $master->save();

        return [
            "status" => "success",
            "mess" => "フィールドが追加されました。"
        ];
    }

    public function updateSchemaField($masterId, $colName, $field)
    {
        $master = ContentMaster::where('master_id', $masterId)->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターIDが見つかりません。"
            ];
        }

        $schema = $master->schema ?? [];
        $updated = false;

        foreach ($schema as $key => $existingField) {
            if ($existingField['col_name'] === $colName) {
                $schema[$key] = $field;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            return [
                "status" => "error",
                "mess" => "指定されたカラム名が見つかりません。"
            ];
        }

        $master->schema = $schema;
        $master->save();

        return [
            "status" => "success",
            "mess" => "フィールドが更新されました。"
        ];
    }

    public function deleteSchemaField($masterId, $colName)
    {
        $master = ContentMaster::where('master_id', $masterId)->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターIDが見つかりません。"
            ];
        }

        $schema = $master->schema ?? [];
        $newSchema = [];
        $deleted = false;

        foreach ($schema as $existingField) {
            if ($existingField['col_name'] !== $colName) {
                $newSchema[] = $existingField;
            } else {
                $deleted = true;
            }
        }

        if (!$deleted) {
            return [
                "status" => "error",
                "mess" => "指定されたカラム名が見つかりません。"
            ];
        }

        $master->schema = $newSchema;
        $master->save();

        return [
            "status" => "success",
            "mess" => "フィールドが削除されました。"
        ];
    }

    public function updateSchemaOrder($masterId, $orderData)
    {
        $master = ContentMaster::where('master_id', $masterId)->first();

        if (!$master) {
            return [
                "status" => "error",
                "mess" => "マスターIDが見つかりません。"
            ];
        }

        $schema = $master->schema ?? [];
        $updatedSchema = [];

        // 既存のスキーマを連想配列に変換（col_nameをキーにする）
        $schemaMap = [];
        foreach ($schema as $field) {
            $schemaMap[$field['col_name']] = $field;
        }

        // 新しい順序でスキーマを再構築
        foreach ($orderData as $item) {
            $colName = $item['col_name'];
            if (isset($schemaMap[$colName])) {
                $field = $schemaMap[$colName];
                $field['sort_order'] = $item['sort_order'];
                $updatedSchema[] = $field;
            }
        }

        $master->schema = $updatedSchema;
        $master->save();

        return [
            "status" => "success",
            "mess" => "スキーマの表示順が更新されました。"
        ];
    }
}

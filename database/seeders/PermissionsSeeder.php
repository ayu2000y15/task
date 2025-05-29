<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // パーミッションの基本セット
        $permissions = [
            // 衣装案件関連
            ['name' => 'projects.viewAny', 'display_name' => '衣装案件一覧（閲覧）'],
            ['name' => 'projects.view', 'display_name' => '衣装案件詳細（閲覧）'],
            ['name' => 'projects.create', 'display_name' => '衣装案件（作成）'],
            ['name' => 'projects.update', 'display_name' => '衣装案件（更新）'],
            ['name' => 'projects.delete', 'display_name' => '衣装案件（削除）'],

            // 工程関連
            ['name' => 'tasks.viewAny', 'display_name' => '工程（閲覧）'],
            ['name' => 'tasks.create', 'display_name' => '工程（作成）'],
            ['name' => 'tasks.update', 'display_name' => '工程（更新）'],
            ['name' => 'tasks.delete', 'display_name' => '工程（削除）'],

            ['name' => 'tasks.file-view', 'display_name' => 'ファイル（閲覧）'],
            ['name' => 'tasks.file-upload', 'display_name' => 'ファイル（アップロード）'],
            ['name' => 'tasks.file-download', 'display_name' => 'ファイル（ダウンロード）'],

            // ユーザー管理
            ['name' => 'users.viewAny', 'display_name' => 'ユーザー（閲覧）'],
            ['name' => 'users.update', 'display_name' => 'ユーザー（ロール編集）'],
            ['name' => 'users.delete', 'display_name' => 'ユーザー（削除）'],

            // 権限管理
            ['name' => 'roles.viewAny', 'display_name' => '権限設定（閲覧）'],
            ['name' => 'roles.update', 'display_name' => '権限設定（更新）'],

            ['name' => 'measurements.manage', 'display_name' => '採寸データ管理 (ALL)'],
            ['name' => 'measurements.update', 'display_name' => '採寸データ管理 (追加・更新)'],
            ['name' => 'measurements.delete', 'display_name' => '採寸データ管理 (削除)'],

            ['name' => 'materials.manage', 'display_name' => '材料リスト管理 (ALL)'],
            ['name' => 'materials.update', 'display_name' => '材料リスト管理 (追加・更新)'],
            ['name' => 'materials.delete', 'display_name' => '材料リスト管理 (削除)'],

            ['name' => 'costs.manage', 'display_name' => 'コスト管理 (ALL)'],
            ['name' => 'costs.update', 'display_name' => 'コスト管理 (追加・更新)'],
            ['name' => 'costs.delete', 'display_name' => 'コスト管理 (削除)'],

            ['name' => 'process_templates.viewAny', 'display_name' => '工程テンプレート一覧（閲覧）'],
            ['name' => 'process_templates.update', 'display_name' => '工程テンプレート（登録、更新）'],
            ['name' => 'process_templates.delete', 'display_name' => '工程テンプレート（削除）'],

            ['name' => 'form-definition.viewAny', 'display_name' => 'フォーム情報（閲覧）'],
            ['name' => 'form-definition.update', 'display_name' => 'フォーム情報（登録、更新）'],
            ['name' => 'form-definition.delete', 'display_name' => 'フォーム情報（削除）'],

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}

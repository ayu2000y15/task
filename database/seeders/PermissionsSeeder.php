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
            ['name' => 'projects.viewAny', 'display_name' => '衣装案件一覧表示'],
            ['name' => 'projects.view', 'display_name' => '衣装案件詳細表示'],
            ['name' => 'projects.create', 'display_name' => '衣装案件作成'],
            ['name' => 'projects.update', 'display_name' => '衣装案件編集'],
            ['name' => 'projects.delete', 'display_name' => '衣装案件削除'],

            // 工程関連
            ['name' => 'tasks.viewAny', 'display_name' => '工程一覧表示'],
            ['name' => 'tasks.create', 'display_name' => '工程作成'],
            ['name' => 'tasks.update', 'display_name' => '工程編集'],
            ['name' => 'tasks.delete', 'display_name' => '工程削除'],

            // ユーザー管理
            ['name' => 'users.viewAny', 'display_name' => 'ユーザー一覧表示'],
            ['name' => 'users.update', 'display_name' => 'ユーザー役割編集'],
            ['name' => 'users.delete', 'display_name' => 'ユーザー削除'],

            // 権限管理
            ['name' => 'roles.viewAny', 'display_name' => '権限設定表示'],
            ['name' => 'roles.update', 'display_name' => '権限設定編集'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}

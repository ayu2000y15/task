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
            // プロジェクト関連
            ['name' => 'projects.viewAny', 'display_name' => 'プロジェクト一覧表示'],
            ['name' => 'projects.view', 'display_name' => 'プロジェクト詳細表示'],
            ['name' => 'projects.create', 'display_name' => 'プロジェクト作成'],
            ['name' => 'projects.update', 'display_name' => 'プロジェクト編集'],
            ['name' => 'projects.delete', 'display_name' => 'プロジェクト削除'],

            // タスク関連
            ['name' => 'tasks.viewAny', 'display_name' => 'タスク一覧表示'],
            ['name' => 'tasks.create', 'display_name' => 'タスク作成'],
            ['name' => 'tasks.update', 'display_name' => 'タスク編集'],
            ['name' => 'tasks.delete', 'display_name' => 'タスク削除'],

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

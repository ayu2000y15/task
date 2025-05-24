<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 初期ロールの作成
        Role::create(['name' => 'system_developer', 'display_name' => 'システム開発者']);
        Role::create(['name' => 'company_admin', 'display_name' => '会社管理者']);
        Role::create(['name' => 'manager', 'display_name' => 'マネージャー']);
        Role::create(['name' => 'staff', 'display_name' => 'スタッフ']);

        // パーミッション（操作権限）の登録は、権限設定画面の実装時に行います。
    }
}

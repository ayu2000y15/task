<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Testing Database Relationships ===\n";

    // permission_roleテーブルの内容を確認
    echo "Sample permission_role records:\n";
    $pivotRecords = DB::table('permission_role')->limit(5)->get();
    foreach ($pivotRecords as $record) {
        echo "Role ID: {$record->role_id}, Permission ID: {$record->permission_id}\n";
    }

    $user = App\Models\User::first();
    echo "\nUser ID: " . $user->id . "\n";
    echo "User name: " . $user->name . "\n";
    echo "User roles count: " . $user->roles()->count() . "\n";

    if ($user->roles()->count() > 0) {
        $role = $user->roles()->first();
        echo "First role: " . $role->name . " (ID: {$role->id})\n";
        echo "Role permissions count: " . $role->permissions()->count() . "\n";

        // そのロールの権限を確認
        $permissionsForRole = DB::table('permission_role')
            ->where('role_id', $role->id)
            ->count();
        echo "Direct DB query permissions count for role {$role->id}: {$permissionsForRole}\n";

        if ($role->permissions()->count() > 0) {
            $permission = $role->permissions()->first();
            echo "First permission: " . $permission->name . "\n";
        }
    }

    // boards.create権限の存在確認
    $boardsCreatePermission = App\Models\Permission::where('name', 'boards.create')->first();
    if ($boardsCreatePermission) {
        echo "\nboards.create permission exists (ID: {$boardsCreatePermission->id})\n";
    } else {
        echo "\nboards.create permission not found\n";
    }

    // hasPermissionToメソッドをテスト
    echo "\nTesting hasPermissionTo method...\n";
    $result = $user->hasPermissionTo('boards.create');
    echo "hasPermissionTo('boards.create'): " . ($result ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_field_definitions', function (Blueprint $table) {
            $table->boolean('is_inventory_linked')->default(false)->after('is_enabled')->comment('在庫連携の有無');
            $table->json('option_inventory_map')->nullable()->after('is_inventory_linked')->comment('選択肢と在庫の紐付けマップ');
        });
    }

    public function down(): void
    {
        Schema::table('form_field_definitions', function (Blueprint $table) {
            $table->dropColumn('is_inventory_linked');
            $table->dropColumn('option_inventory_map');
        });
    }
};

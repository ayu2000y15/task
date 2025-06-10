<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // ★追加
            $table->string('product_number')->nullable()->after('name'); // 品番
            $table->string('color_number')->nullable()->after('product_number'); // 色番
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // ★追加
            $table->dropColumn(['product_number', 'color_number']);
        });
    }
};

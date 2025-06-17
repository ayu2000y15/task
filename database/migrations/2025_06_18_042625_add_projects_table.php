<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // target_cost の後ろに新しいカラムを追加
            $table->integer('target_material_cost')->nullable()->after('target_cost')->comment('目標材料費');
            $table->integer('target_labor_cost_rate')->nullable()->after('target_material_cost')->comment('目標人件費計算用の固定時給');
            $table->json('tracking_info')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('target_material_cost');
            $table->dropColumn('target_labor_cost_rate');
            $table->dropColumn('tracking_info');
        });
    }
};

<?php

// database/migrations/xxxx_xx_xx_xxxxxx_update_costs_table_for_project_costs.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costs', function (Blueprint $table) {
            // project_id カラムを追加 (外部キー)
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            // character_id を nullable に変更
            $table->foreignId('character_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('costs', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->foreignId('character_id')->nullable(false)->change();
        });
    }
};

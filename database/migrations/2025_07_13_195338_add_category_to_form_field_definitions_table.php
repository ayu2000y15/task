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
        Schema::table('form_field_definitions', function (Blueprint $table) {
            $table->string('category')->default('project')->comment('カテゴリ (project: 案件依頼, board: 掲示板)')->after('name');
            $table->index(['category', 'is_enabled', 'order']);

            // nameの一意制約を削除して、カテゴリ別にユニークにする
            $table->dropUnique(['name']);
            $table->unique(['category', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_field_definitions', function (Blueprint $table) {
            $table->dropUnique(['category', 'name']);
            $table->dropIndex(['category', 'is_enabled', 'order']);
            $table->dropColumn('category');
            $table->unique(['name']);
        });
    }
};

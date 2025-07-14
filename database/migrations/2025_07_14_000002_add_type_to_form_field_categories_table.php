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
        Schema::table('form_field_categories', function (Blueprint $table) {
            $table->enum('type', ['board', 'form'])->default('board')->comment('カテゴリタイプ（board:掲示板用, form:外部フォーム用）')->after('name');

            // typeとis_enabledのインデックスを追加
            $table->index(['type', 'is_enabled']);
        });

        // 既存データのtypeを設定
        DB::table('form_field_categories')->where('is_external_form', true)->update(['type' => 'form']);
        DB::table('form_field_categories')->where('is_external_form', false)->update(['type' => 'board']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_field_categories', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_enabled']);
            $table->dropColumn('type');
        });
    }
};

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
        Schema::table('external_project_submissions', function (Blueprint $table) {
            // form_field_categoriesテーブルへの外部キー制約付きIDカラム。既存データはnullになるためnullable()を許容。
            $table->foreignId('form_category_id')
                ->nullable()
                ->after('status') // statusカラムの後に追加
                ->constrained('form_field_categories') // form_field_categoriesテーブルのidを参照
                ->onDelete('set null'); // 参照先が削除されたらNULLを設定

            // 冗長性を持たせる、または古いデータとの互換性のためのカテゴリ名カラム
            $table->string('form_category_name')
                ->nullable()
                ->after('form_category_id'); // form_category_idの後に追加
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_project_submissions', function (Blueprint $table) {
            // 外部キー制約を先に削除
            $table->dropForeign(['form_category_id']);
            // カラムを削除
            $table->dropColumn(['form_category_id', 'form_category_name']);
        });
    }
};

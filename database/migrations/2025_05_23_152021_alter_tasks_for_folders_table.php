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
        Schema::table('tasks', function (Blueprint $table) {
            // フォルダの場合は日付や工数が不要になるため、nullableに変更
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->integer('duration')->nullable()->change();
            $table->integer('progress')->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 元に戻す処理（nullableでない制約を再度追加）
            // 注意：nullableに変更したカラムにnullのデータがあると、このマイグレーションは失敗します。
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->integer('duration')->nullable(false)->change();
            $table->integer('progress')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
};

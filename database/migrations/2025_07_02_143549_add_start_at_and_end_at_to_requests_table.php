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
        Schema::table('requests', function (Blueprint $table) {
            // notes カラムの後ろに nullable な timestamp 型のカラムを追加
            $table->timestamp('start_at')->nullable()->after('notes');
            $table->timestamp('end_at')->nullable()->after('start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // ロールバック用にカラム削除処理を記述
            $table->dropColumn(['start_at', 'end_at']);
        });
    }
};

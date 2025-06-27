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
        Schema::table('attendances', function (Blueprint $table) {
            // 'actual_work_seconds'カラムの後に、日給を保存するカラムを追加
            // decimal(桁数, 小数点以下の桁数) で金額を扱うのに適した型を指定
            $table->decimal('daily_salary', 10, 2)->nullable()->default(0)->after('actual_work_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('daily_salary');
        });
    }
};

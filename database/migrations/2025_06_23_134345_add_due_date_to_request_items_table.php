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
        Schema::table('request_items', function (Blueprint $table) {
            // my_day_date の後ろに nullable な datetime 型のカラムを追加
            $table->dateTime('my_day_date')->nullable()->comment('開始日時')->change();
            $table->renameColumn('my_day_date', 'start_at');

            $table->dateTime('end_at')->nullable()->after('start_at')->comment('終了日時');
        });
    }

    public function down(): void
    {
        Schema::table('request_items', function (Blueprint $table) {
            $table->renameColumn('start_at', 'my_day_date');
            $table->dropColumn('end_at');
        });
    }
};

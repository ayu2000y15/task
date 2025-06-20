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
        Schema::table('default_shift_patterns', function (Blueprint $table) {
            // end_timeカラムの後にlocationカラムを追加
            $table->string('location')->default('office')->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('default_shift_patterns', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};

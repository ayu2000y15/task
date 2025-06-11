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
        Schema::table('measurements', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('id')->comment('表示順');
            $table->index('display_order');
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('id')->comment('表示順');
            $table->index('display_order');
        });
        Schema::table('costs', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('id')->comment('表示順');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
        Schema::table('costs', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
    }
};

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
        Schema::table('projects', function (Blueprint $table) {
            // status カラムの後に budget カラムを追加
            $table->integer('budget')->unsigned()->nullable()->after('status')->comment('予算');
            // budget カラムの後に target_cost カラムを追加
            $table->integer('target_cost')->unsigned()->nullable()->after('budget')->comment('目標コスト');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['budget', 'target_cost']);
        });
    }
};

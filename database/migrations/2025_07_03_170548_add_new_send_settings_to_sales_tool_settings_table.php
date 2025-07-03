<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_tool_settings', function (Blueprint $table) {
            // image_sending_enabledカラムの後に新しいカラムを追加
            $table->integer('daily_send_limit')->default(10000)->after('image_sending_enabled')->comment('1日の最大メール送信数');
            $table->string('send_timing_type')->default('fixed')->after('daily_send_limit')->comment('送信タイミングの種類 (fixed, random)');
            $table->integer('random_send_min_seconds')->default(2)->after('send_timing_type')->comment('ランダム送信の最小間隔 (秒)');
            $table->integer('random_send_max_seconds')->default(10)->after('random_send_min_seconds')->comment('ランダム送信の最大間隔 (秒)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_tool_settings', function (Blueprint $table) {
            $table->dropColumn([
                'daily_send_limit',
                'send_timing_type',
                'random_send_min_seconds',
                'random_send_max_seconds',
            ]);
        });
    }
};

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
        Schema::table('work_logs', function (Blueprint $table) {
            $table->boolean('is_manually_edited')->default(false)->after('memo')->comment('手動修正フラグ');
            $table->timestamp('edited_start_time')->nullable()->after('is_manually_edited')->comment('手動修正された開始時間');
            $table->timestamp('edited_end_time')->nullable()->after('edited_start_time')->comment('手動修正された終了時間');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn(['is_manually_edited', 'edited_start_time', 'edited_end_time']);
        });
    }
};

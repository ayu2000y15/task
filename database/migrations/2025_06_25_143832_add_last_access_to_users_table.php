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
        Schema::table('users', function (Blueprint $table) {
            // last_access カラムを access_id の後に追加します。
            // 既存のレコードには値がないため、nullable() を指定します。
            $table->timestamp('last_access')->nullable()->after('access_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // ロールバック時に last_access カラムを削除します。
            $table->dropColumn('last_access');
        });
    }
};

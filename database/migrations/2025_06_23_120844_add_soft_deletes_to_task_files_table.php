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
        Schema::table('task_files', function (Blueprint $table) {
            $table->softDeletes(); // deleted_at カラムを追加
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_files', function (Blueprint $table) {
            $table->dropSoftDeletes(); // deleted_at カラムを削除
        });
    }
};

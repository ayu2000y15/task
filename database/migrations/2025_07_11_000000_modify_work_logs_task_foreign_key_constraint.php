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
        Schema::table('work_logs', function (Blueprint $table) {
            // 既存の外部キー制約を削除
            $table->dropForeign(['task_id']);

            // 新しい外部キー制約を追加（restrict設定）
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            // 制約を元に戻す
            $table->dropForeign(['task_id']);

            // 元のcascade制約を復元
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('cascade');
        });
    }
};

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
        Schema::table('requests', function (Blueprint $table) {
            // project_id カラムを追加。案件が削除されたらNULLにする
            $table->foreignId('project_id')->nullable()->after('requester_id')->constrained()->onDelete('set null');
            // request_category_id カラムを追加。カテゴリが削除されたらエラーにする(restrict)
            $table->foreignId('request_category_id')->nullable()->after('project_id')->constrained()->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['request_category_id']);
            $table->dropColumn(['project_id', 'request_category_id']);
        });
    }
};

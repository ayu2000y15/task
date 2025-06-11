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
        Schema::table('users', function (Blueprint $table) {
            // emailカラムの後に、ユニーク制約付きのaccess_idカラムを追加
            // 既存のユーザーがいる場合を考慮し、nullable()（空を許容）にしています。
            $table->string('access_id')->unique()->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_id');
        });
    }
};

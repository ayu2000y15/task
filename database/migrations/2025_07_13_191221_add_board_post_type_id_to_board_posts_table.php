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
        Schema::table('board_posts', function (Blueprint $table) {
            $table->foreignId('board_post_type_id')->nullable()->after('role_id')->constrained('board_post_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_posts', function (Blueprint $table) {
            $table->dropForeign(['board_post_type_id']);
            $table->dropColumn('board_post_type_id');
        });
    }
};

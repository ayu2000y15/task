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
        Schema::table('sales_tool_settings', function (Blueprint $table) {
            // 'image_sending_enabled' の後など、適切な位置に追加
            $table->unsignedInteger('max_emails_per_minute')->default(60)->after('image_sending_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_tool_settings', function (Blueprint $table) {
            $table->dropColumn('max_emails_per_minute');
        });
    }
};

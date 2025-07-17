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
        Schema::table('form_field_definitions', function (Blueprint $table) {
            // 最小選択数 (主に image_select で使用)
            $table->unsignedInteger('min_selections')->nullable()->after('max_length');
            // 最大選択数 (主に image_select で使用)
            $table->unsignedInteger('max_selections')->nullable()->after('min_selections');
            $table->mediumText('options')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_field_definitions', function (Blueprint $table) {
            $table->dropColumn(['min_selections', 'max_selections']);
            $table->text('options')->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_add_display_order_to_characters_table.php
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('gender');
        });
    }
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
    }
};

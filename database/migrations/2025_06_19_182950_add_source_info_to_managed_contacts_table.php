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
        Schema::table('managed_contacts', function (Blueprint $table) {
            // statusカラムの後にsource_infoカラムを追加
            $table->string('source_info')->nullable()->after('status');
            $table->string('establishment_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('managed_contacts', function (Blueprint $table) {
            $table->dropColumn('source_info');
            $table->date('establishment_date')->nullable()->change();
        });
    }
};

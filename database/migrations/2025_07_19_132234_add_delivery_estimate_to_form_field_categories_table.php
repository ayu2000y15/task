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
        Schema::table('form_field_categories', function (Blueprint $table) {
            // thank_you_messageカラムの後に新しいカラムを追加
            $table->string('delivery_estimate_text')->nullable()->after('thank_you_message')->comment('納期目安テキスト');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_field_categories', function (Blueprint $table) {
            $table->dropColumn('delivery_estimate_text');
        });
    }
};

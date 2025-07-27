<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('form_field_definitions', function (Blueprint $table) {
            // `placeholder` カラムの後に `help_text` カラムを追加
            $table->text('help_text')->nullable()->after('placeholder');
        });
    }

    public function down()
    {
        Schema::table('form_field_definitions', function (Blueprint $table) {
            $table->dropColumn('help_text');
        });
    }
};

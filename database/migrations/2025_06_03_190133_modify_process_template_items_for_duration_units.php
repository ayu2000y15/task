<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_template_items', function (Blueprint $table) {
            $table->string('default_duration_unit')->nullable()->after('default_duration')->comment('工数の単位 (days, hours, minutes)');
            // default_duration カラムのコメントを更新 (既存データは手動または別スクリプトで分単位に変換が必要)
            $table->integer('default_duration')->nullable()->comment('分単位の標準工数')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_template_items', function (Blueprint $table) {
            $table->dropColumn('default_duration_unit');
            $table->integer('default_duration')->nullable()->comment('標準工数(日)')->change();
        });
    }
};

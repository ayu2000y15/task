<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateContentDataTable extends Migration
{
    public function up()
    {
        Schema::create('content_data', function (Blueprint $table) {
            $table->id('data_id')->comment('データID');
            $table->string('master_id')->comment('マスターID（外部キー）');
            $table->json('content')->nullable()->comment('コンテンツデータ（JSON形式）');
            $table->char('public_flg', 2)->default('0')->comment('公開フラグ');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->timestamp('created_at')->useCurrent()->comment('登録日');
            $table->timestamp('updated_at')->useCurrent()->comment('更新日');
            $table->char('delete_flg', 2)->default('0')->comment('削除フラグ');

            $table->foreign('master_id')->references('master_id')->on('content_masters')->onUpdate('cascade');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        DB::statement("ALTER TABLE `content_data` comment 'コンテンツデータ'");
    }

    public function down()
    {
        Schema::dropIfExists('content_data');
    }
}

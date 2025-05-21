<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateImagesTable extends Migration
{
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->integer('image_id')->primary()->autoIncrement()->comment('画像ID');
            $table->string('file_name', 200)->comment('ファイル名');
            $table->string('file_path', 100)->nullable()->comment('格納先パス');
            $table->char('view_flg',  20)->default('00')->comment('表示フラグ');
            $table->integer('priority')->nullable()->comment('表示優先度');
            $table->string('alt', 200)->nullable()->comment('写真の説明(alt)');
            $table->string('spare1', 200)->nullable()->comment('予備１');
            $table->string('spare2', 200)->nullable()->comment('予備２');
            $table->timestamp('created_at')->useCurrent()->comment('登録日');
            $table->timestamp('updated_at')->useCurrent()->comment('更新日');
            $table->char('delete_flg', 2)->default('0')->comment('削除フラグ');

            $table->foreign('view_flg')->references('view_flg')->on('view_flags')->onUpdate('cascade');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        DB::statement("ALTER TABLE `images` comment '写真一覧'");
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
}

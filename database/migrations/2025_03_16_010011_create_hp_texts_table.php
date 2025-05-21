<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateHpTextsTable extends Migration
{
    public function up()
    {
        Schema::create('hp_texts', function (Blueprint $table) {
            $table->string('hp_text_id', 20)->primary()->comment('HPテキストID');
            $table->text('content')->nullable()->comment('内容');
            $table->string('memo', 300)->nullable()->comment('メモ');
            $table->string('spare1', 300)->nullable()->comment('予備１');
            $table->string('spare2', 300)->nullable()->comment('予備２');
            $table->timestamp('created_at')->useCurrent()->comment('登録日');
            $table->timestamp('updated_at')->useCurrent()->comment('更新日');
            $table->char('delete_flg', 2)->default('0')->comment('削除フラグ');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        DB::statement("ALTER TABLE `hp_texts` comment 'HPテキスト'");
    }

    public function down()
    {
        Schema::dropIfExists('hp_texts');
    }
}

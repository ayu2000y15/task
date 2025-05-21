<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateContentMastersTable extends Migration
{
    public function up()
    {
        Schema::create('content_masters', function (Blueprint $table) {
            $table->string('master_id')->primary()->comment('マスターID');
            $table->string('title')->nullable()->comment('タイトル');
            $table->string('comment')->nullable()->comment('コメント');
            $table->json('schema')->nullable()->comment('スキーマ定義（JSON形式）');
            $table->timestamp('created_at')->useCurrent()->comment('登録日');
            $table->timestamp('updated_at')->useCurrent()->comment('更新日');
            $table->char('delete_flg', 2)->default('0')->comment('削除フラグ');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        DB::statement("ALTER TABLE `content_masters` comment 'コンテンツマスタ'");

        // データの挿入
        $data = [
            ['T001', '受注管理', null, null],
            ['T002', '会社情報', null, null],
            ['T999', 'お知らせ（管理者）', null, json_encode([
                ['col_name' => 'title', 'view_name' => 'タイトル', 'type' => 'text', 'required_flg' => '1', 'public_flg' => '1'],
                ['col_name' => 'content', 'view_name' => '内容', 'type' => 'textarea', 'required_flg' => '1', 'public_flg' => '1'],
                ['col_name' => 'publish_date', 'view_name' => '公開日', 'type' => 'date', 'required_flg' => '1', 'public_flg' => '1'],
                ['col_name' => 'author', 'view_name' => '作成者', 'type' => 'text', 'required_flg' => '1', 'public_flg' => '1'],
            ])],
        ];

        foreach ($data as $item) {
            DB::table('content_masters')->insert([
                'master_id' => $item[0],
                'title' => $item[1],
                'comment' => $item[2],
                'schema' => $item[3],
                'created_at' => DB::raw('CURRENT_TIMESTAMP'),
                'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('content_masters');
    }
}

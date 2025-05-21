<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateViewFlagsTable extends Migration
{
    public function up()
    {
        Schema::create('view_flags', function (Blueprint $table) {
            $table->char('view_flg', 20)->primary()->comment('表示フラグID');
            $table->string('comment', 300)->nullable()->comment('表示先');
            $table->integer('max_count')->default(0)->comment('最大枚数');
            $table->string('spare1', 300)->nullable()->comment('予備１');
            $table->string('spare2', 300)->nullable()->comment('予備２');
            $table->timestamp('created_at')->useCurrent()->comment('登録日');
            $table->timestamp('updated_at')->useCurrent()->comment('更新日');
            $table->char('delete_flg', 2)->default('0')->comment('削除フラグ');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        DB::statement("ALTER TABLE `view_flags` comment 'HP表示管理'");

        // データの挿入
        $data = [
            ['00', '非公開', 0, null, null, '0'],
            ['01', 'TALENTページに表示', 1, null, null, '0'],
            ['02', 'TALENT個人ページに表示', 0, null, null, '0'],
            ['S001', 'TOPページのバックグラウンド画像', 1, null, null, '0'],
            ['S002', 'ABOUTページのバックグラウンド画像', 1, null, null, '0'],
            ['S003', 'TALENTページのバックグラウンド画像', 1, null, null, '0'],
            ['S004', 'NEWSページのバックグラウンド画像', 1, null, null, '0'],
            ['S005', 'AUDITIONページのバックグラウンド画像', 1, null, null, '0'],
            ['S006', 'CONTACTページのバックグラウンド画像', 1, null, null, '0'],
            ['S201', 'TOPページのスライドバナー', 0, null, null, '0'],
            ['S204', 'TOPページの画像', 1, null, null, '0'],
            ['S999', 'ロゴ', 1, null, null, '0']
        ];

        foreach ($data as $item) {
            DB::table('view_flags')->insert([
                'view_flg' => $item[0],
                'comment' => $item[1],
                'max_count' => $item[2],
                'spare1' => $item[3],
                'spare2' => $item[4],
                'created_at' => DB::raw('CURRENT_TIMESTAMP'),
                'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
                'delete_flg' => $item[5]
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('view_flags');
    }
}

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
        Schema::create('measurement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('テンプレート名');
            $table->text('description')->nullable();
            $table->json('items')->comment('採寸項目 (item, notes の配列)');
            // $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // 必要であれば作成ユーザーを紐付ける
            // $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade'); // プロジェクト固有にする場合
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('measurement_templates');
    }
};

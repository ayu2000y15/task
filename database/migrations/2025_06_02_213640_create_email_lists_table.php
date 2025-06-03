<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // リスト名 (一意)
            $table->text('description')->nullable(); // 説明
            // $table->integer('emails_count')->default(0); // リスト内の有効なメールアドレス数をキャッシュする場合
            $table->timestamps();
            $table->softDeletes(); // ソフトデリート用カラム
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_lists');
    }
};

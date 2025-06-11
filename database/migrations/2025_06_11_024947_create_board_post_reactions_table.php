<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_post_id')->constrained('board_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('emoji', 8); // 絵文字は複数バイトになるため string で
            $table->timestamps();

            // 1人のユーザーが同じ投稿に同じ絵文字を複数登録できないようにユニーク制約を設定
            $table->unique(['board_post_id', 'user_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_post_reactions');
    }
};

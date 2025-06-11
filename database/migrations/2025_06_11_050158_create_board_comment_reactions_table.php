<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('board_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('board_comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('emoji', 8);
            $table->timestamps();
            $table->unique(['comment_id', 'user_id', 'emoji']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('board_comment_reactions');
    }
};

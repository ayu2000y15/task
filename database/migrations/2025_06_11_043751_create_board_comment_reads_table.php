<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('board_comment_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('comment_id')->constrained('board_comments')->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_comment_reads');
    }
};

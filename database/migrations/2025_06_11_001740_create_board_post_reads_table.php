<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_post_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('board_post_id')->constrained('board_posts')->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'board_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_post_reads');
    }
};

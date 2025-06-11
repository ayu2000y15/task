<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_post_user_read', function (Blueprint $table) {
            $table->foreignId('board_post_id')->constrained('board_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->primary(['board_post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_post_user_read');
    }
};

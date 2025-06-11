<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('board_post_tag', function (Blueprint $table) {
            $table->foreignId('board_post_id')->constrained('board_posts')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->primary(['board_post_id', 'tag_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('board_post_tag');
    }
};

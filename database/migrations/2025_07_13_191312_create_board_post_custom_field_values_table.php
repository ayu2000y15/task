<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_post_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_post_id')->constrained('board_posts')->onDelete('cascade');
            $table->foreignId('form_field_definition_id')->constrained('form_field_definitions')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['board_post_id', 'form_field_definition_id'], 'unique_post_field_value');
            $table->index('board_post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_post_custom_field_values');
    }
};

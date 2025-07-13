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
        Schema::create('board_post_type_form_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_post_type_id')->constrained('board_post_types')->onDelete('cascade');
            $table->foreignId('form_field_definition_id')->constrained('form_field_definitions')->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['board_post_type_id', 'form_field_definition_id'], 'unique_post_type_form_field');
            $table->index(['board_post_type_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_post_type_form_field');
    }
};

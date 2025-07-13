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
        Schema::create('board_post_type_form_field_definition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_post_type_id')
                ->constrained('board_post_types')
                ->onDelete('cascade')
                ->name('bpt_ffd_board_post_type_foreign');
            $table->foreignId('form_field_definition_id')
                ->constrained('form_field_definitions')
                ->onDelete('cascade')
                ->name('bpt_ffd_form_field_definition_foreign');
            $table->boolean('is_required')->default(false)->comment('このタイプでの必須フラグ');
            $table->integer('order')->default(0)->comment('表示順');
            $table->timestamps();

            $table->unique(['board_post_type_id', 'form_field_definition_id'], 'bpt_ffd_unique');
            $table->index(['board_post_type_id', 'order'], 'bpt_ffd_type_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_post_type_form_field_definition');
    }
};

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
        Schema::create('form_field_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('カテゴリ名（英語、システム内部用）');
            $table->string('display_name', 100)->comment('表示名（日本語）');
            $table->text('description')->nullable()->comment('カテゴリの説明');
            $table->integer('order')->default(0)->comment('表示順序');
            $table->boolean('is_enabled')->default(true)->comment('有効フラグ');
            $table->timestamps();

            $table->index(['is_enabled', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_field_categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_template_id')->constrained()->onDelete('cascade');
            $table->string('name'); // 工程名 (例: パターン作成)
            $table->integer('default_duration')->nullable(); // 標準工数（日）
            $table->integer('order')->default(0); // 表示順
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_template_items');
    }
};

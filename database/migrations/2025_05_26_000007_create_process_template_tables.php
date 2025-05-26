<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('process_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_template_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('default_duration')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_template_items');
        Schema::dropIfExists('process_templates');
    }
};

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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('assignee')->nullable();
            $table->integer('duration');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('not_started'); // not_started, in_progress, completed
            $table->integer('progress')->default(0); // 0-100%
            $table->string('color')->default('#6c757d'); // デフォルト色
            $table->boolean('is_milestone')->default(false);
            $table->boolean('is_folder')->default(false); // フォルダタイプの工程
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name'); // 例: 赤いサテン生地
            $table->string('supplier')->nullable(); // 例: オカダヤ
            $table->decimal('price', 10, 2)->nullable(); // 金額
            $table->string('quantity_needed'); // 例: 3m
            $table->string('status')->default('未購入'); // 未購入, 購入済
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};

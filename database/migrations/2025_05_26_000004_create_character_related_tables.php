<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->string('item');
            $table->string('value');
            $table->string('unit')->default('cm');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('supplier')->nullable();
            $table->decimal('price', 10, 0)->nullable();
            $table->string('quantity_needed');
            $table->string('status')->default('未購入');
            $table->timestamps();
        });

        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->string('item_description');
            $table->decimal('amount', 10, 0);
            $table->string('type')->default('材料費');
            $table->date('cost_date')->default(now());
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costs');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('measurements');
    }
};

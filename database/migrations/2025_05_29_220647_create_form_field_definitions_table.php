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
        Schema::create('form_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('フィールドの内部名 (スラグ)');
            $table->string('label')->comment('表示ラベル');
            $table->string('type')->comment('フィールドタイプ (text, textarea, date, number, select, checkbox)');
            $table->text('options')->nullable()->comment('select, radio, checkbox 用の選択肢 (JSONまたはカンマ区切り)');
            $table->string('placeholder')->nullable()->comment('プレースホルダー');
            $table->boolean('is_required')->default(false)->comment('必須フラグ');
            $table->integer('order')->default(0)->comment('表示順');
            $table->integer('max_length')->nullable()->comment('最大文字数など');
            $table->boolean('is_enabled')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_field_definitions');
    }
};

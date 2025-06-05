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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // テンプレート名 (一意)
            $table->string('subject')->nullable(); // 件名テンプレート
            $table->longText('body_html')->nullable(); // HTML本文テンプレート
            $table->longText('body_text')->nullable(); // プレーンテキスト本文テンプレート (任意)
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // 作成者
            $table->timestamps();
            $table->softDeletes(); // ソフトデリート
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

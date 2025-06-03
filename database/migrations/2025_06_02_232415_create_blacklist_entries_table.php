<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklist_entries', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique(); // ブラックリスト対象のメールアドレス
            $table->text('reason')->nullable(); // 登録理由
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // 登録者
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_entries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_holidays', function (Blueprint $table) {
            $table->id();
            // user_idがNULLの場合は全社共通の休日として扱います
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('date');
            $table->string('period_type', 10)->default('full');
            $table->timestamps();

            // 同じユーザーが同じ日に重複して休日を登録できないように設定
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_holidays');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->dateTime('start_time')->nullable()->comment('出勤時刻');
            $table->dateTime('end_time')->nullable()->comment('退勤時刻');
            $table->unsignedInteger('break_seconds')->default(0)->comment('休憩時間（秒）');
            $table->unsignedInteger('actual_work_seconds')->default(0)->comment('実働時間（秒）');
            $table->text('note')->nullable()->comment('備考');
            $table->string('status')->default('calculated')->comment('ステータス: calculated, edited, confirmed');
            $table->timestamps();

            // ユーザーIDと日付の組み合わせはユニーク
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

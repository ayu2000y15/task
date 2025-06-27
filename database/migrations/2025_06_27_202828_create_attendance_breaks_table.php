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
        // 手動編集された勤怠の休憩・中抜け時間を保存するテーブル
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            // attendanceレコードへの関連付け
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            // 'break' (休憩) or 'away' (中抜け)
            $table->enum('type', ['break', 'away'])->default('break');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};

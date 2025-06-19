<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User; // Userモデルをインポート

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            // 打刻種別 (出勤, 退勤, 休憩開始/終了, 中抜け開始/終了)
            $table->enum('type', ['clock_in', 'clock_out', 'break_start', 'break_end', 'away_start', 'away_end']);
            $table->timestamp('timestamp'); // 打刻日時
            $table->string('memo')->nullable(); // メモ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};

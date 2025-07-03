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
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('申請者');
            $table->date('date')->comment('対象日');
            $table->string('reason')->comment('申請理由');

            // 申請内容
            $table->string('requested_type');
            $table->string('requested_name')->nullable();
            $table->time('requested_start_time')->nullable();
            $table->time('requested_end_time')->nullable();
            $table->string('requested_location')->nullable();
            $table->text('requested_notes')->nullable();

            // 承認情報
            $table->string('status')->default('pending')->comment('pending, approved, rejected');
            $table->foreignId('approver_id')->nullable()->constrained('users')->comment('承認/否認者');
            $table->text('rejection_reason')->nullable()->comment('否認理由');
            $table->timestamp('processed_at')->nullable()->comment('処理日時');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_change_requests');
    }
};

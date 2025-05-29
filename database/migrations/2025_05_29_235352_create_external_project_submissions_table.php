<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_project_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submitter_name')->nullable()->comment('申請者名');
            $table->string('submitter_email')->nullable()->comment('申請者メールアドレス');
            $table->text('submitter_notes')->nullable()->comment('申請者からの備考');
            $table->json('submitted_data')->comment('カスタムフィールドの入力データ');
            $table->string('status')->default('new')->comment('申請ステータス (new, processed, rejected)');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('処理担当者ID');
            $table->timestamp('processed_at')->nullable()->comment('処理日時');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_project_submissions');
    }
};

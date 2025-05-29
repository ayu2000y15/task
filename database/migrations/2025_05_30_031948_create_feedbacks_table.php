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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('送信者ユーザーID')->constrained()->onDelete('cascade');
            $table->string('user_name')->comment('送信者名 (ユーザー情報からコピー)');
            $table->string('email')->nullable()->comment('連絡先メールアドレス');
            $table->string('phone')->nullable()->comment('連絡先電話番号');
            $table->foreignId('feedback_category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title')->comment('タイトル');
            $table->text('content')->comment('内容');
            $table->string('status')->default('unread')->comment('対応ステータス: unread, not_started, in_progress, completed, cancelled, on_hold');
            $table->string('assignee_text')->nullable()->comment('対応者名 (自由記述)');
            $table->timestamp('completed_at')->nullable()->comment('完了日');
            $table->text('admin_memo')->nullable()->comment('管理者用メモ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};

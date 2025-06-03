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
        Schema::create('sent_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_email_id')->constrained('sent_emails')->onDelete('cascade');
            $table->foreignId('subscriber_id')->nullable()->constrained('subscribers')->onDelete('set null'); // 購読者が削除されてもログは残す
            $table->string('recipient_email'); // 送信先メールアドレス（購読者が削除された場合も参照可能）
            $table->string('status')->default('queued'); // 例: queued, sent, failed, bounced, opened, clicked, unsubscribed_via_this_email, blacklisted_skipped
            $table->text('error_message')->nullable();   // 送信失敗時のエラーメッセージ
            $table->timestamp('processed_at')->nullable(); // キューワーカーが処理した日時（送信試行日時）
            $table->timestamp('delivered_at')->nullable(); // (将来用) 配信確認日時
            $table->timestamp('opened_at')->nullable();    // (将来用) 開封日時
            $table->timestamp('clicked_at')->nullable();   // (将来用) クリック日時
            $table->timestamps(); // created_at はキュー投入時、updated_at はステータス更新時
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_email_logs');
    }
};

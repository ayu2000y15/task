<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_email_id')->constrained('sent_emails')->onDelete('cascade');
            $table->string('recipient_email')->index(); // トラッキング対象のメールアドレス
            $table->timestamp('opened_at')->nullable(); // 開封日時
            $table->timestamp('clicked_at')->nullable(); // リンククリック日時 (最初にクリックされた日時など)
            $table->ipAddress('ip_address')->nullable(); // 開封/クリック時のIPアドレス
            $table->string('user_agent', 500)->nullable(); // 開封/クリック時のユーザーエージェント
            $table->timestamps(); // created_at はトラッキングイベント発生日時として使える

            $table->unique(['sent_email_id', 'recipient_email'], 'sent_email_recipient_unique'); // 同じメールの同じ受信者へのトラッキングは1つ
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_trackings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('set null'); // 関連するメールリストID (NULL許容)
            $table->string('subject'); // 件名
            $table->longText('body_html'); // HTML形式のメール本文
            $table->longText('body_text')->nullable(); // テキスト形式のメール本文
            $table->timestamp('sent_at')->nullable(); // 送信日時 (送信予約の場合は未来日時も)
            $table->string('sender_email'); // 送信元メールアドレス
            $table->string('sender_name')->nullable(); // 送信元名
            $table->string('status')->default('draft'); // 送信ステータス (draft, queued, sending, sent, failed)
            // $table->unsignedInteger('total_recipients')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_emails');
    }
};

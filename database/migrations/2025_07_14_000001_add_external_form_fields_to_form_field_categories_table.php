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
        Schema::table('form_field_categories', function (Blueprint $table) {
            $table->string('slug', 100)->unique()->nullable()->comment('外部フォーム用URLスラッグ')->after('description');
            $table->string('form_title', 200)->nullable()->comment('外部フォーム画面タイトル')->after('slug');
            $table->text('form_description')->nullable()->comment('外部フォーム画面説明文')->after('form_title');
            $table->string('thank_you_title', 200)->nullable()->comment('送信完了画面タイトル')->after('form_description');
            $table->text('thank_you_message')->nullable()->comment('送信完了画面メッセージ')->after('thank_you_title');
            $table->boolean('is_external_form')->default(false)->comment('外部フォームとして公開するか')->after('thank_you_message');
            $table->boolean('requires_approval')->default(true)->comment('提出後に承認が必要か')->after('is_external_form');
            $table->json('notification_emails')->nullable()->comment('通知先メールアドレス（JSON配列）')->after('requires_approval');

            // インデックスを追加
            $table->index(['is_external_form', 'is_enabled']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_field_categories', function (Blueprint $table) {
            $table->dropIndex(['is_external_form', 'is_enabled']);
            $table->dropIndex(['slug']);

            $table->dropColumn([
                'slug',
                'form_title',
                'form_description',
                'thank_you_title',
                'thank_you_message',
                'is_external_form',
                'requires_approval',
                'notification_emails'
            ]);
        });
    }
};

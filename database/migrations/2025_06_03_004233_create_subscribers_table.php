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
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_list_id')->constrained('email_lists')->onDelete('cascade');
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('postal_code', 20)->nullable(); // 郵便番号 (例: xxx-xxxx)
            $table->text('address')->nullable();          // 住所
            $table->string('phone_number', 30)->nullable(); // 電話番号
            $table->string('fax_number', 30)->nullable();   // FAX番号
            $table->string('url')->nullable();              // URL
            $table->string('representative_name')->nullable(); // 代表者名
            $table->date('establishment_date')->nullable(); // 設立日
            $table->string('industry')->nullable();         // 業種 (既存)
            // $table->string('job_title')->nullable(); // 役職 (今回のリストにはなかったのでコメントアウト、必要なら復活)
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('status')->default('subscribed');
            $table->timestamps();

            $table->unique(['email_list_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};

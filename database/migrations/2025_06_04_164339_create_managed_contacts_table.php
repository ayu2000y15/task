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
        Schema::create('managed_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique(); // メールアドレス (ユニーク)
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('fax_number', 30)->nullable();
            $table->string('url')->nullable();
            $table->string('representative_name')->nullable();
            $table->date('establishment_date')->nullable();
            $table->string('industry')->nullable();
            $table->text('notes')->nullable(); // 備考
            $table->string('status')->default('active'); // 例: active, do_not_contact
            // $table->softDeletes(); // 必要に応じてソフトデリートカラムを追加
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('managed_contacts');
    }
};

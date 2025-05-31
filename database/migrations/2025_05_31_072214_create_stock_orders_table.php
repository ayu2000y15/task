<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade')->comment('対象在庫品目ID');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('申請者ユーザーID');
            $table->decimal('quantity_requested', 8, 2)->comment('申請数量');
            $table->string('status')->default('pending')->comment('申請ステータス: pending, approved, rejected, ordered, partially_received, received, cancelled');
            $table->foreignId('managed_by_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('対応者ユーザーID (承認/却下/発注/入荷などを行った管理者)');
            $table->timestamp('managed_at')->nullable()->comment('最終対応日時');
            $table->date('expected_delivery_date')->nullable()->comment('納品予定日');
            $table->text('notes')->nullable()->comment('申請者からの備考');
            $table->text('manager_notes')->nullable()->comment('管理者からの備考・メモ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_orders');
    }
};

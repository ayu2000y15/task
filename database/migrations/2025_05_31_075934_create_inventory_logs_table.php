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
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade')->comment('対象在庫品目ID');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('操作者ユーザーID (システム操作の場合はNULL)');
            $table->string('change_type')->comment('変動種別 (例: stocked, used, adjusted, order_received, manual_stock_in)');
            $table->decimal('quantity_change', 10, 2)->comment('変動量 (入荷・増加なら正、使用・減少なら負)');
            $table->decimal('quantity_before_change', 10, 2)->comment('変動前の在庫数');
            $table->decimal('quantity_after_change', 10, 2)->comment('変動後の在庫数');
            $table->decimal('unit_price_at_change', 15, 4)->nullable()->comment('変動時の単価');
            $table->decimal('total_price_at_change', 15, 2)->nullable()->comment('変動時の総額 (入荷時など)');
            $table->foreignId('related_material_id')->nullable()->constrained('materials')->onDelete('set null')->comment('関連材料ID (使用時)');
            $table->foreignId('related_stock_order_id')->nullable()->constrained('stock_orders')->onDelete('set null')->comment('関連発注申請ID (発注・入荷時)');
            $table->text('notes')->nullable()->comment('備考 (理由、関連情報など)');
            $table->timestamps(); // created_at がログ記録日時となる
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};

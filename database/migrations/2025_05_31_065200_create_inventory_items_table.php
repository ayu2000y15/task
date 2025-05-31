<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('品名'); // 在庫品目の名前
            $table->text('description')->nullable()->comment('説明');
            $table->string('unit')->default('個')->comment('単位 (例: m, 個, 袋)'); // デフォルトを「個」とする
            $table->decimal('total_cost', 15, 2)->default(0)->comment('総原価');
            $table->decimal('quantity', 8, 2)->default(0)->comment('現在の在庫数'); // 在庫数 (小数点2桁まで許容)
            $table->decimal('minimum_stock_level', 8, 2)->default(0)->comment('最小在庫数/発注点');
            $table->string('supplier')->nullable()->comment('仕入先');
            $table->timestamp('last_stocked_at')->nullable()->comment('最終入荷日');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

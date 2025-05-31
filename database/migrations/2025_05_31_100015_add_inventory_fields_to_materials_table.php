<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->after('character_id')
                ->constrained('inventory_items')->onDelete('set null')->comment('関連在庫品目ID');
            $table->string('unit')->nullable()->after('price')->comment('単位 (在庫品目からコピー)'); // ★単位も保存
            $table->decimal('unit_price_at_creation', 15, 4)->nullable()->after('unit')->comment('作成/購入時点の単価');
            // 'price' カラムは 'total_price_at_creation' (作成/購入時点の合計費用) として意味合いを変更するか、
            // もしくはそのまま残して、在庫品目から計算された参考価格として扱う。
            // ここでは既存の 'price' を合計費用として扱うように変更するイメージで進めます。
            // 必要であれば、マイグレーションで既存の 'price' カラムのコメントを変更しても良い。
            // $table->renameColumn('price', 'total_price_at_creation'); // もしカラム名ごと変えるなら
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropColumn('inventory_item_id');
            $table->dropColumn('unit');
            $table->dropColumn('unit_price_at_creation');
            // $table->renameColumn('total_price_at_creation', 'price');
        });
    }
};

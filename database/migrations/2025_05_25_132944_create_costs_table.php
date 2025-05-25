<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('item_description'); // 例: 赤いサテン生地, 作業費
            $table->decimal('amount', 10, 0); // 金額 (小数点以下は不要なため0に)
            $table->string('type')->default('材料費'); // 材料費, 作業費, その他
            $table->date('cost_date')->default(now()); // 発生日
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_tool_settings', function (Blueprint $table) {
            $table->id(); // 通常1レコードのみ使用するが、履歴管理などで複数レコード持つ構成も考えられる
            $table->unsignedInteger('send_interval_minutes')->default(5); // デフォルト5分間隔
            $table->unsignedInteger('emails_per_batch')->default(100); // デフォルト100通/バッチ
            $table->boolean('image_sending_enabled')->default(true); // 画像送信はデフォルトで有効
            $table->unsignedInteger('batch_delay_seconds')->default(60); // デフォルト60秒
            // 他の必要な設定カラム
            $table->timestamps();
        });

        // 初期設定レコードを作成 (任意)
        // \App\Models\SalesToolSetting::create([]); // SalesToolSettingモデルがfillableを持たない場合は空でcreate
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_tool_settings');
    }
};

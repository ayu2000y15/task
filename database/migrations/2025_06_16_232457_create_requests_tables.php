<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 依頼の親テーブル
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->comment('依頼者ID')->constrained('users')->onDelete('cascade');
            $table->string('title')->comment('依頼の件名');
            $table->text('notes')->nullable()->comment('補足事項');
            $table->timestamp('completed_at')->nullable()->comment('全ての項目が完了した日時');
            $table->timestamps();
        });

        // 依頼のチェックリスト項目テーブル
        Schema::create('request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->comment('依頼ID')->constrained('requests')->onDelete('cascade');
            $table->text('content')->comment('依頼内容');
            $table->boolean('is_completed')->default(false)->comment('完了フラグ');
            $table->foreignId('completed_by')->nullable()->comment('完了者ID')->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable()->comment('完了日時');
            $table->unsignedInteger('order')->default(0)->comment('表示順');
            $table->date('my_day_date')->nullable()->comment('「今日のやること」に追加した日付');
            $table->index('my_day_date');
            $table->timestamps();
        });

        // 依頼と担当者の関連（中間）テーブル
        Schema::create('request_assignees', function (Blueprint $table) {
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->foreignId('user_id')->comment('担当者ID')->constrained('users')->onDelete('cascade');
            $table->primary(['request_id', 'user_id']); // 複合主キー
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_assignees');
        Schema::dropIfExists('request_items');
        Schema::dropIfExists('requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Feedback; // Feedbackモデルをインポート

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            // contentカラムの後にpriorityカラムを追加することを想定
            // 既存のカラム構成に合わせてafter()の指定は調整してください。
            // Feedbackモデルに PRIORITY_MEDIUM 定数が存在することが前提です。
            $table->tinyInteger('priority')->default(Feedback::PRIORITY_MEDIUM)->after('content')->comment('優先度: 1=高, 2=中, 3=低 など');
        });

        // 既存のデータに初期の順序を設定 (任意、ID順など) - この部分は既実行なら不要
        // if (Schema::hasTable('feedbacks')) {
        //     $feedbacksWithoutPriority = Feedback::whereNull('priority')->get();
        //     if ($feedbacksWithoutPriority->isNotEmpty()) {
        //         foreach ($feedbacksWithoutPriority as $feedback) {
        //             $feedback->priority = Feedback::PRIORITY_MEDIUM; // デフォルト値を設定
        //             $feedback->save();
        //         }
        //     }
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};

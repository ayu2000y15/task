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
        Schema::table('projects', function (Blueprint $table) {
            // 新規専用カラムの追加 (TEXT型, NULL許容)
            if (!Schema::hasColumn('projects', 'delivery_flag')) {
                $table->char('delivery_flag', 1)->nullable()->after('is_favorite');
            }
            if (!Schema::hasColumn('projects', 'payment_flag')) {
                $table->string('payment_flag', 50)->nullable()->after('delivery_flag');
            }
            if (!Schema::hasColumn('projects', 'payment')) {
                $table->text('payment')->nullable()->after('payment_flag');
            }

            // プロジェクト固有のフォームフィールド定義を格納するJSONカラム
            if (!Schema::hasColumn('projects', 'form_definitions')) {
                $table->json('form_definitions')->nullable()->after('payment');
            }

            // form_definitions で定義されたフィールドの値を格納するJSONカラム
            // (既存の attributes カラムがないことを確認して追加)
            if (!Schema::hasColumn('projects', 'attributes')) {
                $table->json('attributes')->nullable()->after('form_definitions');
            }

            if (!Schema::hasColumn('projects', 'status')) {
                $table->string('status', 50)->nullable()->after('attributes');
            }

            // 既存の start_date と end_date を NULL許容に変更
            // (既にNULL許容でない場合のみ変更)
            if (Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')->nullable()->change();
            }
            if (Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // 追加したカラムを削除
            if (Schema::hasColumn('projects', 'payment')) {
                $table->dropColumn('payment');
            }
            if (Schema::hasColumn('projects', 'payment_flag')) {
                $table->dropColumn('payment_flag');
            }
            if (Schema::hasColumn('projects', 'delivery_flag')) {
                $table->dropColumn('delivery_flag');
            }
            if (Schema::hasColumn('projects', 'form_definitions')) {
                $table->dropColumn('form_definitions');
            }
            if (Schema::hasColumn('projects', 'attributes')) {
                $table->dropColumn('attributes');
            }
            if (Schema::hasColumn('projects', 'status')) {
                $table->dropColumn('status');
            }
            // start_date と end_date を NOT NULL に戻す (元の状態に戻す)
            // 注意: ロールバック時にこれらのカラムにNULLデータが存在するとエラーになる可能性があります。
            // データ整合性を保つためには、ロールバック前にNULLデータを処理する必要があります。
            // ここではスキーマの変更のみを記述します。
            if (Schema::hasColumn('projects', 'start_date')) {
                // Laravelのchange()でNOT NULLに戻すには、元の定義（デフォルト値なし）を正確に再現する必要がある。
                // もしデフォルト値があった場合はそれも指定する。
                // ここでは、単純に NOT NULL にする試みとして false を渡すが、
                // DBによってはこの操作が期待通りに動作しないか、より詳細なDB固有の操作が必要な場合がある。
                // 安全策としては、DBのツールで直接修正するか、nullable(false)が機能するか確認。
                // 通常、一度nullableにしたものをNOT NULLに戻すのはデータ依存で慎重な操作が求められる。
                // 今回は、スキーマ定義のロールバックとして記述。
                $table->date('start_date')->nullable(false)->change();
            }
            if (Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')->nullable(false)->change();
            }
        });
    }
};

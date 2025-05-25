<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            // project_id を削除する前に外部キー制約を削除
            // 制約名は 'measurements_project_id_foreign' のような形式が一般的
            // DBやLaravelのバージョンにより異なる場合があるので、エラーが出る場合は適宜調整
            if (DB::getDriverName() !== 'sqlite') { // SQLiteは外部キー制約のDROPをサポートしないことがある
                $table->dropForeign(['project_id']);
            }
            $table->dropColumn('project_id');
            $table->foreignId('character_id')->after('id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['character_id']);
            }
            $table->dropColumn('character_id');
            $table->foreignId('project_id')->after('id')->constrained()->onDelete('cascade');
        });
    }
};

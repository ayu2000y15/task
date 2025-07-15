<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_log_id')->nullable()->after('id')->comment('元の自動記録ログID（手修正分のみ）');
            $table->enum('edit_type', ['auto', 'manual'])->default('auto')->after('parent_log_id');
            $table->enum('edit_status', ['pending', 'approved', 'rejected'])->nullable()->after('edit_type');
            $table->text('edit_reject_reason')->nullable()->after('edit_status');
            $table->foreign('parent_log_id')->references('id')->on('work_logs')->onDelete('set null');
        });
    }
    public function down()
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropForeign(['parent_log_id']);
            $table->dropColumn(['parent_log_id', 'edit_type', 'edit_status', 'edit_reject_reason']);
        });
    }
};

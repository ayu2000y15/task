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
        Schema::table('sent_email_logs', function (Blueprint $table) {
            $table->string('message_identifier')->nullable()->unique()->after('recipient_email'); // バウンス特定用ID
            $table->string('original_message_id')->nullable()->unique()->after('message_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sent_email_logs', function (Blueprint $table) {
            $table->dropColumn('message_identifier');
            $table->dropColumn('original_message_id');
        });
    }
};

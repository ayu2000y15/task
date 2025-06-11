<?php
// ...
return new class extends Migration {
    public function up(): void
    {
        Schema::table('board_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('board_comments')->onDelete('cascade');
        });
    }
    public function down(): void
    { /* ... */
    }
};

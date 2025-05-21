<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_controls', function (Blueprint $table) {
            $table->char('access_id', 4)->primary()->comment('権限ID');
            $table->string('access_name')->nullable()->comment('権限名');
            $table->string('access_view')->nullable()->comment('表示先view');
            $table->string('access_root')->nullable()->comment('表示先root');
        });

        $data = [
            ['0', '開発者', 'admin.dashboard', 'admin.dashboard'],
            ['1', 'サイト管理者', 'admin.dashboard', 'admin.dashboard'],
            ['2', '一般ユーザー', 'admin.index-guest', 'admin.guest']
        ];

        foreach ($data as $item) {
            DB::table('access_controls')->insert([
                'access_id' => $item[0],
                'access_name' => $item[1],
                'access_view' => $item[2],
                'access_root' => $item[3]
            ]);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->char('access_id');
            $table->dateTime('last_access')->nullable()->default(now());;
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('access_id')->references('access_id')->on('access_controls')->onUpdate('cascade');
        });

        $data = [
            ['admin', 'adminP@ssw0rd', '0'],
            ['develop', 'developP@ssw0rd', '1'],
            ['guest', 'guest', '2']
        ];

        foreach ($data as $item) {
            DB::table('users')->insert([
                'name' => $item[0],
                'password' => $item[1],
                'access_id' => $item[2]
            ]);
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('access_controls');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

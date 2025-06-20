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
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_transportation_departure')->nullable()->after('password')->comment('デフォルト交通費: 出発地');
            $table->string('default_transportation_destination')->nullable()->after('default_transportation_departure')->comment('デフォルト交通費: 到着地');
            $table->unsignedInteger('default_transportation_amount')->nullable()->after('default_transportation_destination')->comment('デフォルト交通費: 金額');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'default_transportation_departure',
                'default_transportation_destination',
                'default_transportation_amount',
            ]);
        });
    }
};

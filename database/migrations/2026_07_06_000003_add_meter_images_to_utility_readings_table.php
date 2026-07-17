<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            $table->string('electricity_image')->nullable()->after('electricity_new');
            $table->string('water_image')->nullable()->after('water_new');
        });
    }

    public function down()
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            $table->dropColumn(['electricity_image', 'water_image']);
        });
    }
};

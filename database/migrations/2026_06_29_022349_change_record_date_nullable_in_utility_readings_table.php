<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            // Cho phép cột record_date được rỗng (NULL)
            $table->date('record_date')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            $table->date('record_date')->nullable(false)->change();
        });
    }
};

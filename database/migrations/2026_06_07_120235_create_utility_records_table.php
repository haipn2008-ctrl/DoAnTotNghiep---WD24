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
        Schema::create('utility_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms');

            $table->integer('electric_old');

            $table->integer('electric_new');

            $table->integer('water_old');

            $table->integer('water_new');

            $table->date('record_month');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_records');
    }
};

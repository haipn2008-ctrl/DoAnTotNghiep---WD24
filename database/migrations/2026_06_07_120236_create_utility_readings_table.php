<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('utility_readings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('month');

            $table->integer('year');

            $table->date('record_date');

            $table->integer('electricity_old')
                ->default(0);

            $table->integer('electricity_new');

            $table->integer('water_old')
                ->default(0);

            $table->integer('water_new');

            $table->enum('status', [
                'draft',
                'confirmed'
            ])->default('draft');

            $table->text('note')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'room_id',
                'month',
                'year'
            ]);
        });
    }

    public function down()
    {
        Schema::dropIfExists('utility_readings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {

            $table->id();

            $table->string('room_code')->unique();

            $table->integer('floor');

            $table->enum('room_type', [
                'standard',
                'vip'
            ])->default('standard');

            $table->decimal('price', 12, 2);

            $table->decimal('area', 8, 2);

            // Số người tối đa
            $table->integer('max_people')
                ->default(4);

            // Số người hiện tại
            $table->integer('current_people')
                ->default(0);

            $table->string('thumbnail')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->enum('status', [
                'available',
                'occupied',
                'maintenance'
            ])->default('available');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

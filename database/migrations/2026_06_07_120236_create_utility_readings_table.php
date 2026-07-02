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
        Schema::create('utility_readings', function (Blueprint $table) {

            $table->id();

            // Hợp đồng đang sử dụng
            $table->foreignId('contract_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Phòng
            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Kỳ ghi điện nước
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('month');

            $table->unsignedSmallInteger('year');

            $table->date('record_date');

            /*
            |--------------------------------------------------------------------------
            | Điện
            |--------------------------------------------------------------------------
            */

            $table->integer('electric_old')
                ->default(0);

            $table->integer('electric_new');

            $table->integer('electric_usage')
                ->default(0);

            $table->decimal('electric_unit_price', 12, 2);

            $table->string('electric_photo')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Nước
            |--------------------------------------------------------------------------
            */

            $table->integer('water_old')
                ->default(0);

            $table->integer('water_new');

            $table->integer('water_usage')
                ->default(0);

            $table->decimal('water_unit_price', 12, 2);

            $table->string('water_photo')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Trạng thái
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'draft',
                'confirmed'
            ])->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Ghi chú
            |--------------------------------------------------------------------------
            */

            $table->text('note')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Người nhập
            |--------------------------------------------------------------------------
            */

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Mỗi phòng chỉ có 1 lần ghi điện nước trong tháng
            $table->unique([
                'room_id',
                'month',
                'year'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_readings');
    }
};

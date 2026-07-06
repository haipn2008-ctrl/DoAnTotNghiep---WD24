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

            /*
            |--------------------------------------------------------------------------
            | Hợp đồng & Phòng
            |--------------------------------------------------------------------------
            */

            $table->foreignId('contract_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

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

            $table->decimal('electric_unit_price', 12, 2);

            // Ảnh công tơ điện
            $table->string('electricity_image')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Nước
            |--------------------------------------------------------------------------
            */

            $table->integer('water_old')
                ->default(0);

            $table->integer('water_new');

            $table->decimal('water_unit_price', 12, 2);

            // Ảnh công tơ nước
            $table->string('water_image')
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

            /*
            |--------------------------------------------------------------------------
            | Ràng buộc
            |--------------------------------------------------------------------------
            | Một hợp đồng chỉ có một bản ghi điện nước trong một tháng.
            */

            $table->unique([
                'contract_id',
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

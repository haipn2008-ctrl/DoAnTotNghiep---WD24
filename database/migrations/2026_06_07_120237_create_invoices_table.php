<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Quan hệ
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

            $table->foreignId('utility_reading_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Thông tin hóa đơn
            |--------------------------------------------------------------------------
            */

            // Mã hóa đơn
            $table->string('invoice_code')->unique();

            // Loại hóa đơn
            $table->enum('type', [
                'room',
                'utility',
            ])->default('room');

            /*
            |--------------------------------------------------------------------------
            | Kỳ hóa đơn
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('month');

            $table->unsignedSmallInteger('year');

            /*
            |--------------------------------------------------------------------------
            | Ngày lập
            |--------------------------------------------------------------------------
            */

            $table->date('invoice_date');

            $table->date('due_date');

            /*
            |--------------------------------------------------------------------------
            | Các khoản phí
            |--------------------------------------------------------------------------
            */

            $table->decimal('room_fee', 12, 2)->default(0);

            $table->decimal('electricity_fee', 12, 2)->default(0);

            $table->decimal('water_fee', 12, 2)->default(0);

            $table->decimal('internet_fee', 12, 2)->default(0);

            $table->decimal('service_fee', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Trạng thái
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Constraint
            |--------------------------------------------------------------------------
            */

            // Một phòng chỉ có một hóa đơn trong một tháng
            $table->unique([
                'room_id',
                'month',
                'year',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

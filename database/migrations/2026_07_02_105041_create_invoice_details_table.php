<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {

            $table->id();

            // Hóa đơn cha
            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Loại dịch vụ
            |--------------------------------------------------------------------------
            */

            $table->enum('service_type', [
                'room',
                'electric',
                'water',
                'internet',
                'garbage',
                'parking',
                'other'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Tên hiển thị
            |--------------------------------------------------------------------------
            */

            $table->string('service_name');

            /*
            |--------------------------------------------------------------------------
            | Số lượng
            |--------------------------------------------------------------------------
            */

            $table->decimal('quantity', 10, 2)
                ->default(1);

            /*
            |--------------------------------------------------------------------------
            | Đơn giá
            |--------------------------------------------------------------------------
            */

            $table->decimal('unit_price', 12, 2);

            /*
            |--------------------------------------------------------------------------
            | Thành tiền
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount', 12, 2);

            /*
            |--------------------------------------------------------------------------
            | Nếu là điện nước thì liên kết sang Utility
            |--------------------------------------------------------------------------
            */

            $table->foreignId('utility_reading_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Ghi chú
            |--------------------------------------------------------------------------
            */

            $table->text('note')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};

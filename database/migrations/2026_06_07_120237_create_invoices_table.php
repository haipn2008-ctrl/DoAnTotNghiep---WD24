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

            // Hợp đồng
            $table->foreignId('contract_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Mã hóa đơn
            $table->string('invoice_code')
                ->unique();

            // Loại hóa đơn
            $table->enum('type', [
                'room',
                'utility'
            ]);

            // Kỳ hóa đơn
            $table->unsignedTinyInteger('month');

            $table->unsignedSmallInteger('year');

            // Ngày lập
            $table->date('invoice_date');

            // Hạn thanh toán
            $table->date('due_date');

            // Tổng tiền
            $table->decimal('total_amount', 12, 2);

            // Trạng thái
            $table->enum('status', [
                'unpaid',
                'partial',
                'paid',
                'overdue'
            ])->default('unpaid');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

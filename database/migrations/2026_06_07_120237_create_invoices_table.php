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
        Schema::create('invoices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('contract_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->foreignId('utility_reading_id')
                ->nullable()
                ->constrained('utility_readings')
                ->nullOnDelete();

            $table->integer('month');

            $table->integer('year');

            $table->date('invoice_date');

            $table->date('due_date');

            $table->decimal('room_fee', 12, 2);

            $table->decimal('electricity_fee', 12, 2)
                ->default(0);

            $table->decimal('water_fee', 12, 2)
                ->default(0);

            $table->decimal('internet_fee', 12, 2)
                ->default(0);

            $table->decimal('service_fee', 12, 2)
                ->default(0);

            $table->decimal('total_amount', 12, 2);

            $table->enum('status', [
                'unpaid',
                'partial',
                'paid'
            ])->default('unpaid');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

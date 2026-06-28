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
        Schema::create('settings', function (Blueprint $table) {

            $table->id();

            $table->decimal('electric_price', 10, 2);

            $table->decimal('water_price', 10, 2);

            $table->decimal('internet_fee', 10, 2)
                ->default(0);

            $table->decimal('service_fee', 10, 2)
                ->default(0);

            $table->decimal('parking_fee', 10, 2)
                ->default(0);

            $table->integer('invoice_day')
                ->default(5);

            $table->integer('payment_due_days')
                ->default(10);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

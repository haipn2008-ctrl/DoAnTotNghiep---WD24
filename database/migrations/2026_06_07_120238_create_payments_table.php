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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount_paid', 12, 2);

            $table->date('payment_date');

            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'qr'
            ])->default('cash');

            $table->string('transaction_code')
                ->nullable();

            $table->enum('status', [
                'pending',
                'success',
                'failed'
            ])->default('success');

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('note')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

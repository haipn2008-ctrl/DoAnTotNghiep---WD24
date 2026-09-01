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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code')->unique();
            $table->string('category', 50); // electricity, water, internet, maintenance, cleaning, asset, other
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('support_request_id')->nullable()->constrained('support_requests')->nullOnDelete();
            $table->string('payer_name')->nullable();
            $table->string('payment_method', 50)->default('bank_transfer'); // bank_transfer, cash
            $table->string('receipt_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'expense_date']);
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};


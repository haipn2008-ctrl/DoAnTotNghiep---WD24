<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_statements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_reading_id')->nullable()->constrained('utility_readings')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('final_charge_amount', 15, 2)->default(0);
            $table->decimal('previous_outstanding_amount', 15, 2)->default(0);
            $table->decimal('deposit_credit', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->dateTime('calculated_at');
            $table->timestamps();
        });

        Schema::create('settlement_statement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('settlement_statement_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_statement_items');
        Schema::dropIfExists('settlement_statements');
    }
};

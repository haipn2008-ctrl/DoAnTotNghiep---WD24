<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('parent_invoice_id')->nullable()->after('contract_id')
                ->constrained('invoices')->restrictOnDelete();
            $table->index(['parent_invoice_id', 'invoice_type']);
        });

        Schema::create('contract_credits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('credit_code')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['contract_id', 'remaining_amount']);
        });

        Schema::create('contract_credit_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_credit_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['contract_credit_id', 'invoice_id'], 'credit_invoice_application_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_credit_applications');
        Schema::dropIfExists('contract_credits');

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['parent_invoice_id', 'invoice_type']);
            $table->dropConstrainedForeignId('parent_invoice_id');
        });
    }
};

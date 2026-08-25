<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unsignedSmallInteger('revision')->default(1)->after('invoice_type');
            $table->decimal('adjustment_amount', 12, 2)->default(0)->after('total_amount');
            $table->dateTime('cancelled_at')->nullable()->index();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
        });

        $indexes = collect(Schema::getIndexes('invoices'));
        if ($indexes->contains(fn (array $index): bool => $index['name'] === 'invoices_contract_type_period_unique')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropUnique('invoices_contract_type_period_unique');
            });
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(
                ['contract_id', 'invoice_type', 'month', 'year', 'revision'],
                'invoices_contract_type_period_revision_unique'
            );
        });

        Schema::create('invoice_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('adjustment_code')->nullable()->unique();
            $table->string('direction', 10);
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['invoice_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique('invoices_contract_type_period_revision_unique');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn([
                'revision',
                'adjustment_amount',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(
                ['contract_id', 'invoice_type', 'month', 'year'],
                'invoices_contract_type_period_unique'
            );
        });
    }
};

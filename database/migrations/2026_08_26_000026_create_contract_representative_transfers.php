<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_representative_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('old_contract_tenant_id')->constrained('contract_tenants')->restrictOnDelete();
            $table->foreignId('new_contract_tenant_id')->constrained('contract_tenants')->restrictOnDelete();
            $table->foreignId('old_tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('new_tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('old_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('effective_at');
            $table->text('reason');
            $table->decimal('deposit_amount_snapshot', 12, 2)->default(0);
            $table->json('old_representative_snapshot');
            $table->json('new_representative_snapshot');
            $table->timestamps();

            $table->index(['contract_id', 'effective_at'], 'representative_transfer_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_representative_transfers');
    }
};

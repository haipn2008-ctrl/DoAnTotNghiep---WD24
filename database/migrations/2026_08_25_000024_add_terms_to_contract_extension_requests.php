<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_extension_requests', function (Blueprint $table): void {
            $table->date('approved_end_date')->nullable()->after('requested_end_date');
            $table->decimal('proposed_monthly_rent', 15, 2)->nullable()->after('approved_end_date');
            $table->decimal('proposed_deposit_amount', 15, 2)->nullable()->after('proposed_monthly_rent');
            $table->json('terms_snapshot')->nullable()->after('admin_note');
            $table->text('financial_override_reason')->nullable()->after('terms_snapshot');
            $table->foreignId('processed_by')->nullable()->after('financial_override_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('terms_offered_at')->nullable()->after('processed_at');
            $table->timestamp('tenant_confirmed_at')->nullable()->after('terms_offered_at');
            $table->timestamp('tenant_declined_at')->nullable()->after('tenant_confirmed_at');
            $table->text('tenant_decline_reason')->nullable()->after('tenant_declined_at');
        });
    }

    public function down(): void
    {
        Schema::table('contract_extension_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn([
                'approved_end_date',
                'proposed_monthly_rent',
                'proposed_deposit_amount',
                'terms_snapshot',
                'financial_override_reason',
                'terms_offered_at',
                'tenant_confirmed_at',
                'tenant_declined_at',
                'tenant_decline_reason',
            ]);
        });
    }
};

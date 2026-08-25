<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['contract_id']);
        });

        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
        });

        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->dateTime('cancelled_at')->nullable()->index()->after('signed_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')
                ->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });

        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['contract_id']);
        });

        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
        });
    }
};

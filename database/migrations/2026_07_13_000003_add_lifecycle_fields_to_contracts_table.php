<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'representative_tenant_id')) {
                $table->foreignId('representative_tenant_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('tenants')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contracts', 'deposit_status')) {
                $table->enum('deposit_status', ['pending', 'paid', 'returned'])
                    ->default('pending')
                    ->after('deposit_amount');
            }

            if (! Schema::hasColumn('contracts', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()->after('deposit_status');
            }

            if (! Schema::hasColumn('contracts', 'extended_at')) {
                $table->timestamp('extended_at')->nullable()->after('end_date');
            }

            if (! Schema::hasColumn('contracts', 'extend_start_date')) {
                $table->date('extend_start_date')->nullable()->after('extended_at');
            }

            if (! Schema::hasColumn('contracts', 'extend_end_date')) {
                $table->date('extend_end_date')->nullable()->after('extend_start_date');
            }

            if (! Schema::hasColumn('contracts', 'terminated_by')) {
                $table->enum('terminated_by', ['admin', 'tenant'])
                    ->nullable()
                    ->after('termination_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'representative_tenant_id')) {
                $table->dropForeign(['representative_tenant_id']);
            }

            $columns = [
                'representative_tenant_id',
                'deposit_status',
                'deposit_paid_at',
                'extended_at',
                'extend_start_date',
                'extend_end_date',
                'terminated_by',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn ($column) => Schema::hasColumn('contracts', $column)
            ));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_tenants', function (Blueprint $table): void {
            $table->string('vehicle_declaration_status', 30)->default('undeclared')->after('status');
            $table->timestamp('vehicle_declared_at')->nullable()->after('vehicle_declaration_status');
            $table->foreignId('vehicle_declared_by')->nullable()->after('vehicle_declared_at')->constrained('users')->nullOnDelete();
            $table->index(['status', 'vehicle_declaration_status'], 'contract_tenants_vehicle_declaration_idx');
        });

        DB::table('contract_tenants')
            ->whereIn('tenant_id', DB::table('vehicles')->whereIn('status', ['pending', 'approved'])->select('tenant_id'))
            ->update([
                'vehicle_declaration_status' => 'has_vehicle',
                'vehicle_declared_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('contract_tenants', function (Blueprint $table): void {
            $table->dropIndex('contract_tenants_vehicle_declaration_idx');
            $table->dropConstrainedForeignId('vehicle_declared_by');
            $table->dropColumn(['vehicle_declaration_status', 'vehicle_declared_at']);
        });
    }
};

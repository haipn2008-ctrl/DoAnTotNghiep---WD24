<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('contract_occupants', 'contract_tenants');
        Schema::rename('contract_occupant_histories', 'contract_tenant_histories');

        Schema::table('contract_tenants', function (Blueprint $table): void {
            $table->renameColumn('replaces_occupant_id', 'replaces_contract_tenant_id');
        });

        Schema::table('contract_tenant_histories', function (Blueprint $table): void {
            $table->renameColumn('contract_occupant_id', 'contract_tenant_id');
        });

        DB::table('contract_tenants')
            ->where('role', 'occupant')
            ->update(['role' => 'tenant']);
    }

    public function down(): void
    {
        DB::table('contract_tenants')
            ->where('role', 'tenant')
            ->update(['role' => 'occupant']);

        Schema::table('contract_tenant_histories', function (Blueprint $table): void {
            $table->renameColumn('contract_tenant_id', 'contract_occupant_id');
        });

        Schema::table('contract_tenants', function (Blueprint $table): void {
            $table->renameColumn('replaces_contract_tenant_id', 'replaces_occupant_id');
        });

        Schema::rename('contract_tenant_histories', 'contract_occupant_histories');
        Schema::rename('contract_tenants', 'contract_occupants');
    }
};

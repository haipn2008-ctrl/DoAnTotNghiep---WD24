<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_lifecycle_alerts', function (Blueprint $table): void {
            $table->foreignId('contract_id')->nullable()->change();
            $table->foreignId('tenant_id')->nullable()->after('contract_id')->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('contract_lifecycle_alerts')->whereNull('contract_id')->delete();

        Schema::table('contract_lifecycle_alerts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->foreignId('contract_id')->nullable(false)->change();
        });
    }
};

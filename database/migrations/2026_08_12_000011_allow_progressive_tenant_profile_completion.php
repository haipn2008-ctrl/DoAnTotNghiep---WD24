<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('cccd')->nullable()->change();
        });

        Schema::table('contract_occupants', function (Blueprint $table): void {
            $table->text('address')->nullable()->after('relationship');
        });

        DB::table('contract_occupants')->whereNotNull('tenant_id')->orderBy('id')
            ->each(function (object $occupant): void {
                DB::table('contract_occupants')->where('id', $occupant->id)->update([
                    'address' => DB::table('tenants')->where('id', $occupant->tenant_id)->value('address'),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('contract_occupants', function (Blueprint $table): void {
            $table->dropColumn('address');
        });

        DB::table('tenants')->whereNull('cccd')->orderBy('id')->each(function (object $tenant): void {
            DB::table('tenants')->where('id', $tenant->id)->update([
                'cccd' => 'PENDING'.str_pad((string) $tenant->id, 12, '0', STR_PAD_LEFT),
            ]);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('cccd')->nullable(false)->change();
        });
    }
};

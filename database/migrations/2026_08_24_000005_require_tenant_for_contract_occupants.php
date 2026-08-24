<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contract_occupants')
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->each(function (object $occupant): void {
                $identityNumber = filled($occupant->identity_number)
                    ? trim((string) $occupant->identity_number)
                    : 'LEGACY-OCC-'.$occupant->id;

                $tenantId = DB::table('tenants')
                    ->where('cccd', $identityNumber)
                    ->value('id');

                if (! $tenantId) {
                    $phone = filled($occupant->phone)
                        && ! DB::table('tenants')->where('phone', trim((string) $occupant->phone))->exists()
                            ? trim((string) $occupant->phone)
                            : 'LEGACY-PHONE-'.$occupant->id;

                    $tenantId = DB::table('tenants')->insertGetId([
                        'user_id' => null,
                        'full_name' => $occupant->full_name,
                        'date_of_birth' => $occupant->date_of_birth,
                        'gender' => null,
                        'cccd' => $identityNumber,
                        'cccd_issue_date' => null,
                        'cccd_issue_place' => null,
                        'phone' => $phone,
                        'email' => null,
                        'address' => $occupant->address,
                        'created_at' => $occupant->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('contract_occupants')
                    ->where('id', $occupant->id)
                    ->update(['tenant_id' => $tenantId]);
            });

        Schema::table('contract_occupants', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('contract_occupants', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
        });
    }
};

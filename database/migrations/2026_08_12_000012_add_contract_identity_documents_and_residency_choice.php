<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->boolean('representative_is_occupant')->default(false)->after('representative_tenant_id');
        });

        Schema::table('contract_occupants', function (Blueprint $table): void {
            $table->string('identity_front_path', 2048)->nullable()->after('identity_number');
            $table->string('identity_back_path', 2048)->nullable()->after('identity_front_path');
        });

        DB::table('contracts')->whereIn('id', DB::table('contract_occupants')
            ->where('role', 'representative')
            ->whereIn('status', ['approved', 'checked_in'])
            ->select('contract_id'))
            ->update(['representative_is_occupant' => true]);
    }

    public function down(): void
    {
        Schema::table('contract_occupants', function (Blueprint $table): void {
            $table->dropColumn(['identity_front_path', 'identity_back_path']);
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('representative_is_occupant');
        });
    }
};

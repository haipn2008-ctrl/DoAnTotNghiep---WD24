<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->foreignId('contract_tenant_id')->nullable()->after('contract_id')
                ->constrained('contract_tenants')->nullOnDelete();
            $table->string('reference_number')->nullable()->after('end_date');
            $table->string('evidence_path')->nullable()->after('note');
            $table->string('evidence_original_name')->nullable()->after('evidence_path');
            $table->string('evidence_mime_type', 100)->nullable()->after('evidence_original_name');
            $table->foreignId('verified_by')->nullable()->after('signed_at')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable()->after('verified_by');
            $table->index(['contract_tenant_id', 'status'], 'temporary_residences_member_status_index');
        });

        DB::table('temporary_residences')->orderBy('id')->each(function (object $residence): void {
            $memberId = DB::table('contract_tenants')
                ->where('contract_id', $residence->contract_id)
                ->where('tenant_id', $residence->tenant_id)
                ->orderByDesc('id')
                ->value('id');

            if ($memberId) {
                DB::table('temporary_residences')->where('id', $residence->id)
                    ->update(['contract_tenant_id' => $memberId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->dropIndex('temporary_residences_member_status_index');
            $table->dropConstrainedForeignId('contract_tenant_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'reference_number', 'evidence_path', 'evidence_original_name', 'evidence_mime_type', 'verified_at',
            ]);
        });
    }
};

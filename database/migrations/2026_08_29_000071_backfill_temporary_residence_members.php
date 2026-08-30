<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('temporary_residences')
            ->whereNull('contract_tenant_id')
            ->orderBy('id')
            ->each(function (object $residence): void {
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
        // Không xóa liên kết đã xác định đúng để tránh làm mất quan hệ lịch sử.
    }
};

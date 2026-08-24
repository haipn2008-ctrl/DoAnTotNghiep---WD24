<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contracts')
            ->where('status', 'pending_signature')
            ->orderBy('id')
            ->each(function (object $contract): void {
                $submittedAt = DB::table('contract_status_histories')
                    ->where('contract_id', $contract->id)
                    ->where('action', 'submit_for_signature')
                    ->latest('performed_at')
                    ->value('performed_at');

                $issuedAt = Carbon::parse($submittedAt ?? $contract->updated_at ?? now());

                DB::table('contracts')->where('id', $contract->id)->update([
                    'signature_due_at' => $issuedAt->addDays(3),
                ]);
            });
    }

    public function down(): void
    {
        // Không xóa hạn ký vì có thể đã được dùng để quản lý hợp đồng thực tế.
    }
};

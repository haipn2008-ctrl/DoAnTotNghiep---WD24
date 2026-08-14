<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyDepositIds = DB::table('invoices')
            ->where('invoice_type', 'first_month_rent')
            ->where(function ($query): void {
                $query->where('invoice_code', 'like', 'DEP-%')
                    ->orWhere('lifecycle_event_key', 'like', '%:deposit');
            })
            ->pluck('id');

        if ($legacyDepositIds->isNotEmpty()) {
            DB::table('invoices')->whereIn('id', $legacyDepositIds)->update([
                'invoice_type' => 'deposit',
                'room_fee' => 0,
            ]);
            DB::table('invoice_details')->whereIn('invoice_id', $legacyDepositIds)->update([
                'type' => 'deposit',
                'name' => 'Tiền cọc hợp đồng',
                'note' => 'Khoản cọc được giữ để quyết toán khi kết thúc hợp đồng',
            ]);
        }

        DB::table('contracts')
            ->where('deposit_status', 'paid')
            ->where('deposit_resolution', 'not_required')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->update(['deposit_resolution' => null]);
    }

    public function down(): void
    {
        // Không gộp ngược hai nghĩa vụ tài chính vì có thể làm mất dấu tiền cọc đã thu.
    }
};

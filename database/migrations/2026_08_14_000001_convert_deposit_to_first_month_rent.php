<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('invoice_type', 'deposit')
            ->update([
                'invoice_type' => 'first_month_rent',
                'room_fee' => DB::raw('total_amount'),
            ]);

        DB::table('invoice_details')
            ->where('type', 'deposit')
            ->update([
                'type' => 'first_month_rent',
                'name' => 'Tiền phòng tháng đầu trả trước',
                'note' => 'Khoản thu sau khi ký được cấn vào tháng đầu và không hoàn lại cuối hợp đồng',
            ]);

        DB::table('contracts')
            ->where('deposit_status', 'paid')
            ->update(['deposit_resolution' => 'not_required']);
    }

    public function down(): void
    {
        DB::table('invoices')
            ->where('invoice_type', 'first_month_rent')
            ->update([
                'invoice_type' => 'deposit',
                'room_fee' => 0,
            ]);

        DB::table('invoice_details')
            ->where('type', 'first_month_rent')
            ->update([
                'type' => 'deposit',
                'name' => 'Tiền cọc hợp đồng',
                'note' => 'Khoản cọc giữ lịch và nhận phòng',
            ]);
    }
};

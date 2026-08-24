<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('invoice_type', 'first_month_rent')
            ->whereIn('status', ['unpaid', 'partial'])
            ->update(['status' => 'written_off', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('invoices')
            ->where('invoice_type', 'first_month_rent')
            ->where('status', 'written_off')
            ->orderBy('id')
            ->eachById(function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $paid = (float) DB::table('payments')
                        ->where('invoice_id', $invoice->id)
                        ->where('status', 'success')
                        ->sum('amount_paid');

                    DB::table('invoices')->where('id', $invoice->id)->update([
                        'status' => $paid > 0 ? 'partial' : 'unpaid',
                        'updated_at' => now(),
                    ]);
                }
            });
    }
};

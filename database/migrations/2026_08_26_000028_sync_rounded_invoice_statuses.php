<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->whereIn('status', ['unpaid', 'partial', 'paid'])
            ->orderBy('id')
            ->chunkById(100, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $paid = (float) DB::table('payments')
                        ->where('invoice_id', $invoice->id)
                        ->where('status', 'success')
                        ->sum('amount_paid');
                    $payable = max(0, round(
                        (float) $invoice->total_amount + (float) $invoice->adjustment_amount
                    ));

                    $status = $payable <= 0 || $paid >= $payable
                        ? 'paid'
                        : ($paid > 0 ? 'partial' : 'unpaid');

                    if ($invoice->status !== $status) {
                        DB::table('invoices')->where('id', $invoice->id)->update([
                            'status' => $status,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // The previous status cannot be reconstructed reliably after payments
        // have changed, so this data-normalization migration is irreversible.
    }
};

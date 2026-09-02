<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReconciliationReport
{
    public function invoiceQuery(int $month, int $year): Builder
    {
        $paidSubquery = Payment::query()
            ->selectRaw('COALESCE(SUM(amount_paid), 0)')
            ->whereColumn('invoice_id', 'invoices.id')
            ->where('status', Payment::STATUS_SUCCESS);
        $pendingSubquery = Payment::query()
            ->selectRaw('COALESCE(SUM(amount_paid), 0)')
            ->whereColumn('invoice_id', 'invoices.id')
            ->where('status', Payment::STATUS_PENDING);

        return Invoice::query()
            ->select('invoices.*')
            ->selectSub($paidSubquery, 'paid_amount')
            ->selectSub($pendingSubquery, 'pending_amount')
            ->forBillingPeriod($month, $year)
            ->notCancelled();
    }

    public function summary(int $month, int $year): array
    {
        $summary = DB::query()
            ->fromSub($this->invoiceQuery($month, $year)->toBase(), 'cohort')
            ->selectRaw('COUNT(*) AS invoice_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount + adjustment_amount > 0 THEN total_amount + adjustment_amount ELSE 0 END), 0) AS gross_billed')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) AS cohort_collected')
            ->selectRaw('COALESCE(SUM(pending_amount), 0) AS pending_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN status != ? AND total_amount + adjustment_amount - paid_amount > 0 THEN total_amount + adjustment_amount - paid_amount ELSE 0 END), 0) AS outstanding_amount', [Invoice::STATUS_WRITTEN_OFF])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND total_amount + adjustment_amount - paid_amount > 0 THEN total_amount + adjustment_amount - paid_amount ELSE 0 END), 0) AS written_off_amount', [Invoice::STATUS_WRITTEN_OFF])
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_amount - total_amount - adjustment_amount > 0 THEN paid_amount - total_amount - adjustment_amount ELSE 0 END), 0) AS overpaid_amount')
            ->first();

        $cashReceived = (float) Payment::query()
            ->success()
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->sum('amount_paid');

        return [
            'invoice_count' => (int) ($summary->invoice_count ?? 0),
            'gross_billed' => (float) ($summary->gross_billed ?? 0),
            'cohort_collected' => (float) ($summary->cohort_collected ?? 0),
            'cash_received' => $cashReceived,
            'pending_amount' => (float) ($summary->pending_amount ?? 0),
            'outstanding_amount' => (float) ($summary->outstanding_amount ?? 0),
            'written_off_amount' => (float) ($summary->written_off_amount ?? 0),
            'overpaid_amount' => (float) ($summary->overpaid_amount ?? 0),
        ];
    }
}

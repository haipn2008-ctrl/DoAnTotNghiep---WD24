<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReconciliationReport;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function index(Request $request, ReconciliationReport $report)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);
        $summary = $report->summary($month, $year);
        $invoices = $report->invoiceQuery($month, $year)
            ->with(['contract.tenant', 'room'])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
        $years = Invoice::query()->pluck('year')
            ->merge(Payment::query()->get(['payment_date'])->pluck('payment_date')->map(fn ($date) => $date->year))
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('admin.reconciliation.index', compact(
            'summary',
            'invoices',
            'month',
            'year',
            'years'
        ));
    }
}

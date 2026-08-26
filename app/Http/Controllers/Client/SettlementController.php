<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request): View
    {
        $contracts = Contract::query()
            ->managedBy($request->user())
            ->whereIn('status', [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED])
            ->with([
                'room',
                'settlementStatement.items',
                'settlementStatement.invoice',
                'invoices' => fn ($query) => $query
                    ->where('status', '!=', Invoice::STATUS_CANCELLED)
                    ->withSum(
                        ['payments as paid_amount' => fn ($query) => $query->where('status', Payment::STATUS_SUCCESS)],
                        'amount_paid'
                    )
                    ->latest('year')
                    ->latest('month')
                    ->latest('id'),
            ])
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [Contract::STATUS_SETTLING])
            ->latest('actual_move_out_at')
            ->latest('id')
            ->get();

        return view('client.settlement.index', compact('contracts'));
    }
}

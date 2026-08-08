<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SupportRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $activeContract = $tenant?->contracts()
            ->with('room')
            ->where('status', 'active')
            ->latest('start_date')
            ->first();

        $invoices = $tenant
            ? Invoice::with(['room', 'contract'])
                ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
                ->latest('year')
                ->latest('month')
                ->latest('id')
                ->get()
            : collect();

        $recentInvoice = $invoices->first();
        $openInvoices = $invoices->whereIn('status', [
            Invoice::STATUS_UNPAID,
            Invoice::STATUS_PARTIAL,
        ]);

        $supportRequests = SupportRequest::where('user_id', $request->user()->id)
            ->whereIn('status', [SupportRequest::STATUS_NEW, SupportRequest::STATUS_IN_PROGRESS])
            ->count();

        return view('layouts.client.home', compact(
            'tenant',
            'activeContract',
            'recentInvoice',
            'openInvoices',
            'supportRequests'
        ));
    }
}

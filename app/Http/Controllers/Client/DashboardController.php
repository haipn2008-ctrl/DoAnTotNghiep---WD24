<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->status === User::STATUS_SETTLING) {
            return redirect()->route('client.settlement.index');
        }

        $tenant = $request->user()->tenant;

        $activeContract = $tenant?->contracts()
            ->with('room')
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->latest('start_date')
            ->first();

        $invoiceQuery = Invoice::with(['room', 'contract'])
            ->when(
                $tenant,
                fn ($query) => $query->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id)),
                fn ($query) => $query->whereRaw('1 = 0')
            );
        $recentInvoice = (clone $invoiceQuery)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->latest('year')->latest('month')->latest('id')->first();
        $openInvoices = (clone $invoiceQuery)
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->latest('year')->latest('month')->latest('id')
            ->limit(5)
            ->get();

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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractAppendix;
use App\Models\ContractLifecycleAlert;
use App\Models\InvoicePaymentDelayRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'resolved', 'all'])],
        ]);
        $status = $filters['status'] ?? 'open';

        $notifications = ContractLifecycleAlert::query()
            ->with(['contract.room', 'contract.tenant', 'tenant', 'vehicle'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->resolved())
            ->latest('detected_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'open' => ContractLifecycleAlert::query()->unresolved()->count(),
            'resolved' => ContractLifecycleAlert::query()->resolved()->count(),
            'all' => ContractLifecycleAlert::query()->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'counts', 'status'));
    }

    public function open(ContractLifecycleAlert $notification)
    {
        if (in_array($notification->type, ['vehicle_removed', 'extension_response', 'contract_appendix_response'], true) && ! $notification->resolved_at) {
            $notification->update(['resolved_at' => now()]);
        }

        $referenceId = (int) ($notification->metadata['reference_id'] ?? 0);
        if (in_array($notification->type, ['vehicle_review', 'vehicle_removed'], true) && $notification->vehicle_id) {
            return redirect()->to(route('admin.vehicles.index', [
                'status' => $notification->type === 'vehicle_review' ? 'pending' : 'removed',
            ]).'#vehicle-'.$notification->vehicle_id);
        }

        $target = match ($notification->type) {
            'extension_request' => route('admin.extension-requests.index'),
            'termination_request' => route('admin.termination-requests.index'),
            'deposit_refund_request' => route('admin.deposit-refunds.index'),
            'payment_review' => route('admin.invoices.payments', ['status' => 'pending']),
            'payment_delay_request' => ($delayRequest = InvoicePaymentDelayRequest::query()->find($referenceId))
                ? route('admin.debts.show', $delayRequest->invoice_id)
                : null,
            'support_request' => route('admin.support.index'),
            'contract_appendix_response' => ContractAppendix::query()->find($referenceId)
                ? route('admin.contract-appendices.show', $referenceId)
                : null,
            'member_review', 'move_in_confirmation' => $notification->contract_id
                ? route('admin.contracts.show', $notification->contract_id)
                : null,
            default => null,
        };

        if ($target) {
            return redirect()->to($target.($referenceId && in_array($notification->type, ['extension_request', 'termination_request', 'support_request'], true) ? "#request-{$referenceId}" : ''));
        }

        if ($notification->contract_id && $notification->contract()->exists()) {
            return redirect()->route('admin.contracts.show', $notification->contract_id);
        }

        return redirect()->route('admin.notifications.index');
    }
}

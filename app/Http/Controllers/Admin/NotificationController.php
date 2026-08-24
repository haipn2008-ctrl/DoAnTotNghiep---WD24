<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractLifecycleAlert;
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
        if ($notification->type === 'vehicle_removed' && ! $notification->resolved_at) {
            $notification->update(['resolved_at' => now()]);
        }

        if ($notification->tenant_id && $notification->tenant()->exists()) {
            return redirect()->route('admin.tenants.show', $notification->tenant_id);
        }

        if ($notification->contract_id && $notification->contract()->exists()) {
            return redirect()->route('admin.contracts.show', $notification->contract_id);
        }

        return redirect()->route('admin.notifications.index');
    }
}

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
            ->with(['contract.room', 'contract.tenant'])
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
}

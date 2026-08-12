<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function __construct(private readonly ContractLifecycleService $lifecycle) {}

    public function index(Request $request): View
    {
        $tenantId = $request->user()->tenant?->id;
        $contracts = Contract::with('room')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId), fn ($query) => $query->whereRaw('1 = 0'))
            ->latest('start_date')
            ->paginate(10);

        return view('client.contracts.index', compact('contracts'));
    }

    public function show(Request $request, int $contract): View
    {
        $tenantId = $request->user()->tenant?->id;
        $contract = Contract::with(['room', 'tenant', 'occupants.histories', 'handoverItems', 'moveInDetailsConfirmer'])
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId), fn ($query) => $query->whereRaw('1 = 0'))
            ->findOrFail($contract);

        return view('client.contracts.show', compact('contract'));
    }

    public function confirmMoveInDetails(Request $request, int $contract)
    {
        $request->validate([
            'confirmation' => ['accepted'],
        ], [
            'confirmation.accepted' => 'Bạn cần xác nhận đã kiểm tra dịch vụ và toàn bộ tài sản bàn giao.',
        ]);
        $tenantId = $request->user()->tenant?->id;
        $contract = Contract::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId), fn ($query) => $query->whereRaw('1 = 0'))
            ->findOrFail($contract);

        $this->lifecycle->confirmMoveInDetails($contract, $request->user());

        return back()->with('success', 'Đã xác nhận tiện nghi, dịch vụ tính phí và tài sản bàn giao của phòng.');
    }

    public function file(Request $request, int $contract): StreamedResponse
    {
        $tenantId = $request->user()->tenant?->id;
        $contract = Contract::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId), fn ($query) => $query->whereRaw('1 = 0'))
            ->findOrFail($contract);
        abort_unless($contract->contract_file && Storage::disk('local')->exists($contract->contract_file), 404);

        return Storage::disk('local')->response($contract->contract_file);
    }
}

<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Setting;
use App\Services\ContractLifecycleService;
use App\Services\AdminNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function __construct(private readonly ContractLifecycleService $lifecycle) {}

    public function index(Request $request): View
    {
        $contracts = Contract::with('room')
            ->managedBy($request->user())
            ->latest('start_date')
            ->paginate(10);

        return view('client.contracts.index', compact('contracts'));
    }

    public function show(Request $request, int $contract): View
    {
        $contract = Contract::with(['room', 'tenant', 'currentMembers.histories', 'currentMembers.tenant.vehicles.tenant', 'handoverItems', 'moveInDetailsConfirmer'])
            ->managedBy($request->user())
            ->findOrFail($contract);
        $setting = Setting::currentOrCreate();

        return view('client.contracts.show', compact('contract', 'setting'));
    }

    public function confirmMoveInDetails(Request $request, int $contract)
    {
        $request->validate([
            'confirmation' => ['accepted'],
        ], [
            'confirmation.accepted' => 'Bạn cần xác nhận đã kiểm tra thông tin dịch vụ và tài sản trong phòng.',
        ]);
        $contract = Contract::query()
            ->managedBy($request->user())
            ->findOrFail($contract);

        $this->lifecycle->confirmMoveInDetails($contract, $request->user());
        app(AdminNotificationService::class)->moveInDetailsConfirmed($contract->fresh());

        return back()->with('success', 'Đã xác nhận thông tin nhận phòng. Vui lòng chờ quản trị viên bàn giao phòng thực tế.');
    }

    public function file(Request $request, int $contract): StreamedResponse
    {
        $contract = Contract::query()
            ->managedBy($request->user())
            ->findOrFail($contract);
        abort_unless($contract->contract_file && Storage::disk('local')->exists($contract->contract_file), 404);

        return Storage::disk('local')->response($contract->contract_file);
    }
}

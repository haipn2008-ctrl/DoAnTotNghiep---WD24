<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\Setting;
use App\Services\AdminNotificationService;
use App\Services\ContractLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
        $contract = Contract::with([
            'room', 'tenant', 'currentMembers.histories', 'currentMembers.tenant.vehicles.tenant',
            'handoverItems', 'moveInDetailsConfirmer', 'settlementStatement.items',
            'settlementStatement.invoice', 'approvedTerminationRequest',
            'extensionRequests' => fn ($query) => $query->latest('id'),
            'terminationRequests' => fn ($query) => $query->latest('id'),
        ])
            ->managedBy($request->user())
            ->findOrFail($contract);
        $handoverReading = $contract->utilityReadings()
            ->where('reading_type', 'handover')
            ->first();
        $setting = Setting::currentOrCreate();

        return view('client.contracts.show', compact('contract', 'setting', 'handoverReading'));
    }

    public function appendices(Request $request, int $contract): View
    {
        $contract = Contract::query()
            ->managedBy($request->user())
            ->with(['appendices' => fn ($query) => $query->where('status', '!=', ContractAppendix::STATUS_DRAFT)])
            ->findOrFail($contract);

        return view('client.contracts.appendices.index', compact('contract'));
    }

    public function confirmMoveInDetails(Request $request, int $contract)
    {
        $request->validate([
            'confirmation' => ['accepted'],
        ], [
            'confirmation.accepted' => 'Bạn cần xác nhận đã đối chiếu ảnh, chỉ số điện nước, dịch vụ và tài sản trong phòng.',
        ]);
        $contract = Contract::query()
            ->managedBy($request->user())
            ->findOrFail($contract);

        $handoverReading = $contract->utilityReadings()
            ->where('reading_type', 'handover')
            ->first();
        if (! $handoverReading?->meterImageExists('electricity') || ! $handoverReading?->meterImageExists('water')) {
            throw ValidationException::withMessages([
                'confirmation' => 'Ban quản lý phải cung cấp đủ ảnh đồng hồ điện và nước trước khi bạn xác nhận.',
            ]);
        }

        $this->lifecycle->confirmMoveInDetails($contract, $request->user());
        app(AdminNotificationService::class)->moveInDetailsConfirmed($contract->fresh());

        return back()->with('success', 'Đã xác nhận thông tin nhận phòng. Vui lòng chờ quản trị viên bàn giao phòng thực tế.');
    }

    public function handoverMeterImage(Request $request, int $contract, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['electricity', 'water'], true), 404);

        $contract = Contract::query()
            ->managedBy($request->user())
            ->findOrFail($contract);
        $reading = $contract->utilityReadings()
            ->where('reading_type', 'handover')
            ->firstOrFail();
        $path = $reading->{$type.'_image'};

        abort_unless(
            filled($path)
            && str_starts_with($path, "utility-readings/{$type}/")
            && Storage::disk('local')->exists($path),
            404
        );

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function file(Request $request, int $contract): StreamedResponse
    {
        $contract = Contract::query()
            ->managedBy($request->user())
            ->findOrFail($contract);
        abort_unless($contract->contract_file && Storage::disk('local')->exists($contract->contract_file), 404);

        return Storage::disk('local')->response($contract->contract_file);
    }

    public function checkoutPhoto(Request $request, int $contract, int $index): StreamedResponse
    {
        $contract = Contract::query()->managedBy($request->user())->findOrFail($contract);
        $path = $contract->checkout_photo_paths[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}

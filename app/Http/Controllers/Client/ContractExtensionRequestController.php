<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractHistoryService;
use App\Services\ContractLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractExtensionRequestController extends Controller
{
    public function __construct(private readonly ContractLifecycleService $lifecycle) {}

    public function index()
    {
        $user = Auth::user();
        $contracts = Contract::with('room')
            ->managedBy($user)
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->orderByDesc('id')
            ->get();
        $extensionRequests = ContractExtensionRequest::with(['contract.room'])
            ->whereHas('contract', fn ($query) => $query->managedBy($user))
            ->latest()
            ->get();

        return view('client.contracts.extension.index', compact('contracts', 'extensionRequests'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'requested_end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $contract = Contract::query()
            ->managedBy($user)
            ->whereKey($validated['contract_id'])
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->firstOrFail();
        if (! $contract->end_date || $validated['requested_end_date'] <= $contract->end_date->toDateString()) {
            return back()->withInput()->withErrors([
                'requested_end_date' => 'Ngày gia hạn mới phải sau ngày kết thúc hiện tại của hợp đồng.',
            ]);
        }

        DB::transaction(function () use ($contract, $validated, $user): void {
            $contract = Contract::query()->managedBy($user)->whereKey($contract->id)
                ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)->lockForUpdate()->firstOrFail();
            if ($contract->extensionRequests()->whereIn('status', [
                ContractExtensionRequest::STATUS_PENDING,
                ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
            ])->exists()) {
                throw ValidationException::withMessages([
                    'contract_id' => 'Hợp đồng này đang có yêu cầu hoặc điều khoản gia hạn chờ xác nhận.',
                ]);
            }
            if ($contract->terminationRequests()->whereIn('status', [
                ContractTerminationRequest::STATUS_PENDING,
                ContractTerminationRequest::STATUS_APPROVED,
            ])->exists()) {
                throw ValidationException::withMessages([
                    'contract_id' => 'Hợp đồng đang có yêu cầu hoặc lịch trả phòng. Hãy xử lý yêu cầu đó trước khi gia hạn.',
                ]);
            }

            $extensionRequest = ContractExtensionRequest::create([
                'contract_id' => $contract->id,
                'current_end_date' => $contract->end_date,
                'requested_end_date' => $validated['requested_end_date'],
                'reason' => $validated['reason'] ?? null,
                'status' => ContractExtensionRequest::STATUS_PENDING,
            ]);
            ContractHistoryService::log(
                $contract,
                ContractHistoryService::EXTENSION_REQUESTED,
                'Người thuê đại diện đã gửi yêu cầu gia hạn hợp đồng.',
                $validated['reason'] ?? 'Không có lý do.',
                ['end_date' => $contract->end_date?->toDateString(), 'request_status' => null],
                ['end_date' => $validated['requested_end_date'], 'request_status' => ContractExtensionRequest::STATUS_PENDING]
            );
            app(AdminNotificationService::class)->extensionRequested($extensionRequest);
        }, 3);

        return redirect()->route('client.extension-requests.index')
            ->with('success', 'Đã gửi yêu cầu gia hạn. Ban quản lý sẽ xem xét và thông báo kết quả cho bạn.');
    }

    public function accept(Request $request, ContractExtensionRequest $extensionRequest)
    {
        $user = $request->user();
        $contract = DB::transaction(function () use ($user, $extensionRequest) {
            $lockedRequest = ContractExtensionRequest::query()
                ->whereKey($extensionRequest->id)
                ->whereHas('contract', fn ($query) => $query->managedBy($user))
                ->lockForUpdate()->firstOrFail();
            if (! $lockedRequest->isAwaitingConfirmation()) {
                throw ValidationException::withMessages(['request' => 'Điều khoản gia hạn này không còn chờ xác nhận.']);
            }
            $contract = Contract::query()->managedBy($user)->whereKey($lockedRequest->contract_id)->lockForUpdate()->firstOrFail();
            if ($contract->end_date?->toDateString() !== $lockedRequest->current_end_date?->toDateString()) {
                throw ValidationException::withMessages([
                    'request' => 'Hợp đồng đã thay đổi sau khi điều khoản được gửi. Vui lòng yêu cầu ban quản lý lập lại điều khoản.',
                ]);
            }

            $contract = $this->lifecycle->extendContract(
                $contract,
                $user,
                $lockedRequest->approved_end_date,
                'Người thuê đại diện đã xác nhận phụ lục gia hạn trên hệ thống.',
                [
                    'monthly_rent' => (float) $lockedRequest->proposed_monthly_rent,
                    'extension_request_id' => $lockedRequest->id,
                ]
            );
            $lockedRequest->forceFill([
                'status' => ContractExtensionRequest::STATUS_APPROVED,
                'tenant_confirmed_at' => now(),
            ])->save();
            app(AdminNotificationService::class)->extensionResponded($lockedRequest, true);

            return $contract;
        }, 3);

        app(ClientNotificationService::class)->contract(
            $contract,
            'extension_request_approved',
            'Gia hạn hợp đồng đã có hiệu lực',
            'Bạn đã xác nhận phụ lục. Hợp đồng '.$contract->contract_code.' được gia hạn đến '.$contract->end_date?->format('d/m/Y').'.'
        );

        return back()->with('success', 'Đã xác nhận phụ lục và gia hạn hợp đồng thành công.');
    }

    public function decline(Request $request, ContractExtensionRequest $extensionRequest)
    {
        $data = $request->validate([
            'decline_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $extensionRequest, $data): void {
            $lockedRequest = ContractExtensionRequest::query()
                ->whereKey($extensionRequest->id)
                ->whereHas('contract', fn ($query) => $query->managedBy($request->user()))
                ->lockForUpdate()->firstOrFail();
            if (! $lockedRequest->isAwaitingConfirmation()) {
                throw ValidationException::withMessages(['request' => 'Điều khoản gia hạn này không còn chờ xác nhận.']);
            }
            $lockedRequest->forceFill([
                'status' => ContractExtensionRequest::STATUS_DECLINED_BY_TENANT,
                'tenant_declined_at' => now(),
                'tenant_decline_reason' => $data['decline_reason'],
            ])->save();
            app(AdminNotificationService::class)->extensionResponded($lockedRequest, false);
            ContractHistoryService::log(
                $lockedRequest->contract,
                ContractHistoryService::EXTENSION_REJECTED,
                'Người thuê đại diện không đồng ý điều khoản gia hạn.',
                $data['decline_reason'],
                ['request_status' => ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION],
                ['request_status' => ContractExtensionRequest::STATUS_DECLINED_BY_TENANT]
            );
        }, 3);

        return back()->with('success', 'Đã ghi nhận việc không đồng ý điều khoản gia hạn.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractHistoryService;
use App\Services\ContractLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractExtensionRequestController extends Controller
{
    public function __construct(private readonly ContractLifecycleService $lifecycle) {}

    public function index()
    {
        $extensionRequests = ContractExtensionRequest::with([
            'contract.room',
            'contract.tenant',
            'contract.currentMembers',
            'contract.invoices.payments',
            'contract.invoices.adjustments',
        ])->latest()->get();

        return view('admin.contracts.extension-requests.index', compact('extensionRequests'));
    }

    /** Admin xác nhận hai bên đã thỏa thuận và áp dụng gia hạn ngay. */
    public function approve(Request $request, ContractExtensionRequest $extensionRequest)
    {
        $data = $request->validate([
            'approved_end_date' => ['required', 'date'],
            'proposed_monthly_rent' => ['required', 'numeric', 'min:0'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'financial_override_reason' => ['nullable', 'string', 'min:3', 'max:1000'],
            'extension_agreed' => ['required', 'accepted'],
        ], [
            'extension_agreed.required' => 'Bạn phải xác nhận 2 bên đã thỏa thuận gia hạn.',
            'extension_agreed.accepted' => 'Bạn phải xác nhận 2 bên đã thỏa thuận gia hạn.',
        ]);

        $contract = DB::transaction(function () use ($request, $extensionRequest, $data) {
            $lockedRequest = ContractExtensionRequest::query()
                ->with(['contract.currentMembers', 'contract.invoices.payments', 'contract.invoices.adjustments'])
                ->lockForUpdate()
                ->findOrFail($extensionRequest->id);

            if (! $lockedRequest->isPending()) {
                throw ValidationException::withMessages(['request' => 'Yêu cầu gia hạn này đã được xử lý.']);
            }

            $contract = $lockedRequest->contract;
            $approvedEndDate = Carbon::parse($data['approved_end_date'])->startOfDay();
            if (! $contract->end_date || ! $approvedEndDate->gt($contract->end_date->copy()->startOfDay())) {
                throw ValidationException::withMessages([
                    'approved_end_date' => 'Ngày kết thúc được đề nghị phải sau ngày kết thúc hiện tại.',
                ]);
            }
            if ($contract->terminationRequests()->whereIn('status', [
                ContractTerminationRequest::STATUS_PENDING,
                ContractTerminationRequest::STATUS_APPROVED,
            ])->exists()) {
                throw ValidationException::withMessages([
                    'request' => 'Hợp đồng đang có yêu cầu hoặc lịch trả phòng. Hãy xử lý yêu cầu trả phòng trước khi gia hạn.',
                ]);
            }

            $outstanding = $contract->invoices
                ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount);
            if ($outstanding > 0 && blank($data['financial_override_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    'financial_override_reason' => 'Hợp đồng còn '.number_format($outstanding, 0, ',', '.').' VNĐ công nợ. Phải thu hết hoặc nhập lý do chấp nhận ngoại lệ.',
                ]);
            }

            $setting = Setting::currentOrCreate();
            $oldEndDate = $contract->end_date->copy();
            $oldMonthlyRent = (float) $contract->monthly_rent;
            $reason = filled($data['admin_note'] ?? null)
                ? 'Hai bên đã thỏa thuận gia hạn. '.$data['admin_note']
                : 'Hai bên đã thỏa thuận gia hạn.';
            $contract = $this->lifecycle->extendContract(
                $contract,
                $request->user(),
                $approvedEndDate,
                $reason,
                [
                    'monthly_rent' => (float) $data['proposed_monthly_rent'],
                    'extension_request_id' => $lockedRequest->id,
                ],
            );

            $lockedRequest->forceFill([
                'status' => ContractExtensionRequest::STATUS_APPROVED,
                'approved_end_date' => $approvedEndDate,
                'proposed_monthly_rent' => $data['proposed_monthly_rent'],
                'proposed_deposit_amount' => $contract->deposit_amount,
                'admin_note' => $data['admin_note'] ?? null,
                'financial_override_reason' => $data['financial_override_reason'] ?? null,
                'terms_snapshot' => [
                    'old_end_date' => $oldEndDate->toDateString(),
                    'new_end_date' => $approvedEndDate->toDateString(),
                    'old_monthly_rent' => $oldMonthlyRent,
                    'new_monthly_rent' => (float) $data['proposed_monthly_rent'],
                    'deposit_amount' => (float) $contract->deposit_amount,
                    'outstanding_at_offer' => round($outstanding, 2),
                    'fees' => [
                        'electric_price' => (float) $setting->electric_price,
                        'water_price' => (float) $setting->water_price,
                        'internet_fee' => (float) $setting->internet_fee,
                        'service_fee' => (float) $setting->service_fee,
                        'parking_fee' => (float) $setting->parking_fee,
                    ],
                    'tenants' => $contract->currentMembers->map(fn ($member) => [
                        'id' => $member->id,
                        'full_name' => $member->full_name,
                        'role' => $member->role,
                    ])->values()->all(),
                ],
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ])->save();
            app(AdminNotificationService::class)->resolve('extension_request', $lockedRequest);

            return $contract;
        }, 3);

        app(ClientNotificationService::class)->contract(
            $contract,
            'extension_request_approved',
            'Hợp đồng đã được gia hạn',
            'Theo thỏa thuận giữa hai bên, hợp đồng '.$contract->contract_code.' đã được gia hạn đến '.$contract->end_date?->format('d/m/Y').'.'
        );

        return back()->with('success', 'Đã gia hạn hợp đồng theo thỏa thuận của hai bên.');
    }

    public function reject(Request $request, ContractExtensionRequest $extensionRequest)
    {
        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $contract = DB::transaction(function () use ($request, $extensionRequest, $data) {
            $lockedRequest = ContractExtensionRequest::query()->with('contract')->lockForUpdate()->findOrFail($extensionRequest->id);
            if (! in_array($lockedRequest->status, [
                ContractExtensionRequest::STATUS_PENDING,
                ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
            ], true)) {
                throw ValidationException::withMessages(['request' => 'Yêu cầu gia hạn này đã được xử lý.']);
            }
            $contract = $lockedRequest->contract;
            $oldStatus = $lockedRequest->status;
            $lockedRequest->forceFill([
                'status' => ContractExtensionRequest::STATUS_REJECTED,
                'admin_note' => $data['reject_reason'],
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ])->save();

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::EXTENSION_REJECTED,
                'Admin đã từ chối yêu cầu gia hạn hợp đồng.',
                $data['reject_reason'],
                ['request_status' => $oldStatus],
                ['request_status' => ContractExtensionRequest::STATUS_REJECTED]
            );
            app(AdminNotificationService::class)->resolve('extension_request', $lockedRequest);

            return $contract;
        }, 3);

        app(ClientNotificationService::class)->contract(
            $contract,
            'extension_request_rejected',
            'Yêu cầu gia hạn bị từ chối',
            'Yêu cầu gia hạn hợp đồng '.$contract->contract_code.' chưa được chấp thuận. Lý do: '.$data['reject_reason']
        );

        return back()->with('success', 'Đã từ chối yêu cầu gia hạn.');
    }
}

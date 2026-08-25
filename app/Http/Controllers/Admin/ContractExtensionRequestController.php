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

    /** Admin kiểm tra và gửi phụ lục đề nghị; chưa thay đổi hợp đồng ở bước này. */
    public function approve(Request $request, ContractExtensionRequest $extensionRequest)
    {
        $data = $request->validate([
            'approved_end_date' => ['required', 'date'],
            'proposed_monthly_rent' => ['required', 'numeric', 'min:0'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'financial_override_reason' => ['nullable', 'string', 'min:3', 'max:1000'],
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
            $lockedRequest->forceFill([
                'status' => ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
                'approved_end_date' => $approvedEndDate,
                'proposed_monthly_rent' => $data['proposed_monthly_rent'],
                'proposed_deposit_amount' => $contract->deposit_amount,
                'admin_note' => $data['admin_note'] ?? null,
                'financial_override_reason' => $data['financial_override_reason'] ?? null,
                'terms_snapshot' => [
                    'old_end_date' => $contract->end_date?->toDateString(),
                    'new_end_date' => $approvedEndDate->toDateString(),
                    'old_monthly_rent' => (float) $contract->monthly_rent,
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
                'terms_offered_at' => now(),
            ])->save();
            app(AdminNotificationService::class)->resolve('extension_request', $lockedRequest);

            return $contract;
        }, 3);

        app(ClientNotificationService::class)->contract(
            $contract,
            'extension_terms_offered',
            'Điều khoản gia hạn đang chờ bạn xác nhận',
            'Ban quản lý đã gửi điều khoản gia hạn hợp đồng '.$contract->contract_code.'. Vui lòng kiểm tra thời hạn, giá thuê và xác nhận trên cổng khách thuê.'
        );

        return back()->with('success', 'Đã gửi điều khoản gia hạn. Hợp đồng chỉ được gia hạn sau khi người thuê đại diện xác nhận.');
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

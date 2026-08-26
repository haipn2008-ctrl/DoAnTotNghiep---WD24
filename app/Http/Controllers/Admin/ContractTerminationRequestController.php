<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTerminationRequest;
use App\Services\ContractHistoryService;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractTerminationRequestController extends Controller
{
    public function __construct(private readonly ContractLifecycleService $lifecycle) {}

    /**
     * Danh sách yêu cầu trả phòng
     */
    public function index()
    {
        $terminationRequests = ContractTerminationRequest::with([
                'contract.room',
                'contract.tenant'
            ])
            ->latest()
            ->get();

        return view(
            'admin.contracts.termination-requests.index',
            compact('terminationRequests')
        );
    }

    /**
     * Duyệt yêu cầu trả phòng
     */
    public function approve(Request $request, ContractTerminationRequest $terminationRequest)
    {
        $data = $request->validate([
            'approved_end_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_checkout_at' => ['required', 'date', 'after_or_equal:now'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $terminationRequest = $this->lifecycle->scheduleDeparture(
            $terminationRequest,
            $request->user(),
            $data['approved_end_date'],
            $data['scheduled_checkout_at'],
            $data['admin_note'] ?? null,
        );
        $contract = $terminationRequest->contract;
        app(AdminNotificationService::class)->resolve('termination_request', $terminationRequest);

        app(ClientNotificationService::class)->contract(
            $contract,
            'termination_request_approved',
            'Yêu cầu trả phòng đã được duyệt',
            'Lịch bàn giao hợp đồng '.$contract->contract_code.' là '.$terminationRequest->scheduled_checkout_at?->format('H:i d/m/Y').'.'
        );

        return back()->with(
            'success',
            'Đã duyệt và xếp lịch bàn giao phòng. Hợp đồng chỉ chuyển sang quyết toán sau khi khách trả phòng thực tế.'
        );
    }
    /**
     * Từ chối yêu cầu trả phòng
     */
    public function reject(
    Request $request,
    ContractTerminationRequest $terminationRequest
    ) {
        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $contract = DB::transaction(function () use ($request, $terminationRequest, $data) {
            $terminationRequest = ContractTerminationRequest::query()
                ->with('contract')
                ->lockForUpdate()
                ->findOrFail($terminationRequest->id);
            if ($terminationRequest->status !== ContractTerminationRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['request' => 'Yêu cầu rời phòng này đã được xử lý.']);
            }
            $contract = $terminationRequest->contract;

            /*
            |--------------------------------------------------------------------------
            | Từ chối yêu cầu
            |--------------------------------------------------------------------------
            */

            $terminationRequest->update([
                'status' =>
                    ContractTerminationRequest::STATUS_REJECTED,

                'admin_note' =>
                    $data['reject_reason'],

                'processed_by' => $request->user()->id,

                'processed_at' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Ghi lịch sử
            |--------------------------------------------------------------------------
            */

            ContractHistoryService::log(
                $contract,

                ContractHistoryService::TERMINATION_REJECTED,

                'Admin đã từ chối yêu cầu trả phòng.',

                $data['reject_reason'],

                [
                    'contract_status' => $contract->status,

                    'request_status' =>
                        ContractTerminationRequest::STATUS_PENDING,
                ],

                [
                    'contract_status' => $contract->status,

                    'request_status' =>
                        ContractTerminationRequest::STATUS_REJECTED,
                ]
            );
            app(AdminNotificationService::class)->resolve('termination_request', $terminationRequest);

            return $contract;
        }, 3);

        app(ClientNotificationService::class)->contract(
            $contract,
            'termination_request_rejected',
            'Yêu cầu trả phòng bị từ chối',
            'Yêu cầu trả phòng của hợp đồng '.$contract->contract_code.' chưa được chấp thuận. Lý do: '.$data['reject_reason']
        );

        return back()->with(
            'success',
            'Đã từ chối yêu cầu trả phòng.'
        );
    }
}

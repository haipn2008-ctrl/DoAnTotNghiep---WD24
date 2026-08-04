<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTerminationRequest;
use App\Services\ContractHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractTerminationRequestController extends Controller
{
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
    public function approve(
    ContractTerminationRequest $terminationRequest
    ) {
        if (
            $terminationRequest->status
            !== ContractTerminationRequest::STATUS_PENDING
        ) {
            return back()->with(
                'error',
                'Yêu cầu này đã được xử lý.'
            );
        }

        $contract = $terminationRequest->contract;

        if (!$contract) {
            return back()->with(
                'error',
                'Không tìm thấy hợp đồng.'
            );
        }

        if ($contract->status !== Contract::STATUS_ACTIVE) {
            return back()->with(
                'error',
                'Chỉ có thể duyệt yêu cầu trả phòng của hợp đồng đang hoạt động.'
            );
        }

        DB::transaction(function () use (
            $terminationRequest,
            $contract
        ) {

            /*
            |--------------------------------------------------------------------------
            | Duyệt yêu cầu
            |--------------------------------------------------------------------------
            | Chỉ duyệt yêu cầu trả phòng.
            | CHƯA chấm dứt hợp đồng tại đây.
            */

            $terminationRequest->update([
                'status' => ContractTerminationRequest::STATUS_APPROVED,
                'processed_at' => now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Ghi lịch sử
            |--------------------------------------------------------------------------
            */

            ContractHistoryService::log(
                $contract,

                ContractHistoryService::TERMINATION_APPROVED,

                'Admin đã duyệt yêu cầu trả phòng của khách thuê.',

                $terminationRequest->reason
                    ?? 'Khách thuê yêu cầu trả phòng',

                [
                    'contract_status' => $contract->status,
                    'request_status' =>
                        ContractTerminationRequest::STATUS_PENDING,
                ],

                [
                    // Hợp đồng vẫn ACTIVE
                    'contract_status' => $contract->status,

                    'request_status' =>
                        ContractTerminationRequest::STATUS_APPROVED,

                    'requested_end_date' =>
                        $terminationRequest
                            ->requested_end_date
                            ?->format('Y-m-d'),
                ]
            );
        });

        return back()->with(
            'success',
            'Đã duyệt yêu cầu trả phòng. Hợp đồng chưa bị chấm dứt.'
        );
    }
    /**
     * Từ chối yêu cầu trả phòng
     */
    public function reject(
    Request $request,
    ContractTerminationRequest $terminationRequest
    ) {
        if (
            $terminationRequest->status
            !== ContractTerminationRequest::STATUS_PENDING
        ) {
            return back()->with(
                'error',
                'Yêu cầu này đã được xử lý.'
            );
        }

        $request->validate([
            'reject_reason' => [
                'nullable',
                'string',
                'max:1000'
            ],
        ]);

        $contract = $terminationRequest->contract;

        if (!$contract) {
            return back()->with(
                'error',
                'Không tìm thấy hợp đồng.'
            );
        }

        DB::transaction(function () use (
            $request,
            $terminationRequest,
            $contract
        ) {

            /*
            |--------------------------------------------------------------------------
            | Từ chối yêu cầu
            |--------------------------------------------------------------------------
            */

            $terminationRequest->update([
                'status' =>
                    ContractTerminationRequest::STATUS_REJECTED,

                'admin_note' =>
                    $request->reject_reason,

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

                $request->reject_reason
                    ?: 'Không có lý do từ chối.',

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
        });

        return back()->with(
            'success',
            'Đã từ chối yêu cầu trả phòng.'
        );
    }
}
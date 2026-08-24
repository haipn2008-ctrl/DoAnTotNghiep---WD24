<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use Illuminate\Http\Request;
use App\Services\ContractHistoryService;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\DB;

class ContractExtensionRequestController extends Controller
{
    /**
     * Danh sách yêu cầu gia hạn
     */
    public function index()
    {
        $extensionRequests = ContractExtensionRequest::with([
                'contract.room',
                'contract.tenant'
            ])
            ->latest()
            ->get();

        return view(
            'admin.contracts.extension-requests.index',
            compact('extensionRequests')
        );
    }

    /**
     * Duyệt yêu cầu gia hạn
     */
    public function approve(ContractExtensionRequest $extensionRequest)
    {
        if ($extensionRequest->status !== ContractExtensionRequest::STATUS_PENDING) {
            return back()->with(
                'error',
                'Yêu cầu này đã được xử lý.'
            );
        }

        $contract = $extensionRequest->contract;

        if (!$contract) {
            return back()->with(
                'error',
                'Không tìm thấy hợp đồng.'
            );
        }

        if ($contract->status !== Contract::STATUS_ACTIVE) {
            return back()->with(
                'error',
                'Chỉ có thể gia hạn hợp đồng đang hoạt động.'
            );
        }

        if (
            !$contract->end_date ||
            $extensionRequest->requested_end_date <= $contract->end_date
        ) {
            return back()->with(
                'error',
                'Ngày gia hạn phải sau ngày kết thúc hiện tại.'
            );
        }

        DB::transaction(function () use ($extensionRequest, $contract) {

            // Ngày kết thúc cũ
            $oldEndDate = $contract->end_date;

            /*
            |--------------------------------------------------------------------------
            | Gia hạn hợp đồng
            |--------------------------------------------------------------------------
            */

            $contract->update([
                'end_date' => $extensionRequest->requested_end_date,

                'extend_start_date' => $oldEndDate,

                'extend_end_date' => $extensionRequest->requested_end_date,

                'extend_reason' => $extensionRequest->reason,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Cập nhật yêu cầu
            |--------------------------------------------------------------------------
            */

            $extensionRequest->update([
                'status' => ContractExtensionRequest::STATUS_APPROVED,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Ghi lịch sử hợp đồng
            |--------------------------------------------------------------------------
            */

            ContractHistoryService::log(
                $contract,

                ContractHistoryService::EXTENDED,

                'Admin đã duyệt yêu cầu gia hạn hợp đồng của khách thuê.',

                $extensionRequest->reason ?? 'Khách thuê yêu cầu gia hạn',

                [
                    'end_date' => $oldEndDate?->format('Y-m-d'),
                ],

                [
                    'end_date' => $extensionRequest
                        ->requested_end_date
                        ?->format('Y-m-d'),
                ]
            );
            app(AdminNotificationService::class)->resolve('extension_request', $extensionRequest);
        });

        return back()->with(
            'success',
            'Đã duyệt yêu cầu và gia hạn hợp đồng thành công.'
        );
    }

    /**
     * Từ chối yêu cầu gia hạn
     */
    public function reject(
    Request $request,
    ContractExtensionRequest $extensionRequest
    ) {
        if ($extensionRequest->status !== ContractExtensionRequest::STATUS_PENDING) {
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

        $contract = $extensionRequest->contract;

        if (!$contract) {
            return back()->with(
                'error',
                'Không tìm thấy hợp đồng.'
            );
        }


        DB::transaction(function () use (
            $request,
            $extensionRequest,
            $contract
        ) {

            /*
            |--------------------------------------------------------------------------
            | Cập nhật yêu cầu
            |--------------------------------------------------------------------------
            */

            $extensionRequest->update([
                'status' => ContractExtensionRequest::STATUS_REJECTED,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Ghi lịch sử hợp đồng
            |--------------------------------------------------------------------------
            */

            ContractHistoryService::log(
                $contract,

                ContractHistoryService::EXTENSION_REJECTED,

                'Admin đã từ chối yêu cầu gia hạn hợp đồng.',

                $request->reject_reason
                    ?: 'Không có lý do từ chối.',

                [
                    'request_status' => ContractExtensionRequest::STATUS_PENDING,
                ],

                [
                    'request_status' => ContractExtensionRequest::STATUS_REJECTED,
                    'requested_end_date' => $extensionRequest
                        ->requested_end_date
                        ?->format('Y-m-d'),
                ]
            );
            app(AdminNotificationService::class)->resolve('extension_request', $extensionRequest);
        });


        return back()->with(
            'success',
            'Đã từ chối yêu cầu gia hạn.'
        );
    }
}

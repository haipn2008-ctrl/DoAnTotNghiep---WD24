<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\SettlementStatement;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractHistoryService;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepositRefundController extends Controller
{
    /**
     * Danh sách yêu cầu hoàn cọc.
     */
    public function index()
    {
        $contracts = Contract::with(['room', 'tenant', 'settlementStatement'])
            ->whereIn('deposit_status', [
                Contract::DEPOSIT_REFUND_REQUESTED,
                Contract::DEPOSIT_REFUND_APPROVED,
                Contract::DEPOSIT_REFUND_PROCESSING,
                Contract::DEPOSIT_REFUND_REJECTED,
                Contract::DEPOSIT_RETURNED,
                Contract::DEPOSIT_PARTIAL,
                Contract::DEPOSIT_REFUNDED,
                Contract::DEPOSIT_DEDUCTED,
            ])
            ->latest('deposit_refund_requested_at')
            ->get();

        return view('admin.contracts.deposit-refunds.index', compact('contracts'));
    }

    /**
     * Admin duyệt và xác định số tiền được hoàn.
     * Chưa chuyển hợp đồng sang completed ở bước này.
     */
    public function approve(Request $request, Contract $contract)
    {
        if (! $contract->isRefundRequested()) {
            return back()->with('error', 'Yêu cầu hoàn cọc này không còn chờ duyệt.');
        }

        $request->validate([
            'confirm_refund_amount' => ['accepted'],
            'return_note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            // Bước 2 chỉ xác nhận số tiền đã khóa ở quyết toán, không được khấu trừ lại.
            'deposit_process_type' => ['prohibited'],
            'deduction_amount' => ['prohibited'],
            'damage_proof' => ['prohibited'],
            'return_reason' => ['prohibited'],
        ], [
            'confirm_refund_amount.accepted' => 'Vui lòng xác nhận đúng số tiền hoàn đã được chốt ở bước 1.',
            'deposit_process_type.prohibited' => 'Không thể thay đổi phương án hoàn cọc ở bước 2.',
            'deduction_amount.prohibited' => 'Không thể khấu trừ thêm tiền cọc ở bước 2.',
            'damage_proof.prohibited' => 'Ảnh hư hỏng phải được ghi nhận tại bước bàn giao và quyết toán.',
            'return_reason.prohibited' => 'Lý do khấu trừ phải được ghi nhận tại bước bàn giao và quyết toán.',
        ]);

        $statement = $contract->settlementStatement()->first();
        if (! $statement) {
            return back()->with('error', 'Hợp đồng chưa có bảng quyết toán cuối kỳ.');
        }
        $statement = app(SettlementService::class)->refreshFinancials($statement);
        $eligibleRefund = max(0, -(float) $statement->net_amount);
        if ($eligibleRefund <= 0) {
            return back()->with('error', 'Sau khi bù trừ công nợ và hóa đơn cuối kỳ, hợp đồng không còn số dư cọc để hoàn.');
        }

        $deposit = (float) $contract->deposit_amount;
        $refund = $eligibleRefund;
        $deduction = max(0, $deposit - $refund);
        $description = 'Admin xác nhận hoàn đúng số dư tiền cọc sau quyết toán.';

        DB::transaction(function () use (
            $contract,
            $request,
            $deduction,
            $refund,
            $description,
            $statement,
        ) {
            $oldStatus = $contract->status;
            $oldDepositStatus = $contract->deposit_status;

            $contract->update([
                'deposit_status' => Contract::DEPOSIT_REFUND_APPROVED,
                // Giữ giá trị legacy để tương thích dữ liệu cũ; số tiền luôn lấy từ quyết toán.
                'deposit_process_type' => 'full_refund',
                'deposit_refund_amount' => $refund,
                'deposit_deduction_amount' => $deduction,
                'deposit_refund_approved_at' => now(),
                'deposit_process_reason' => $description,
                'deposit_process_note' => $request->return_note,
                'deposit_admin_note' => $request->return_note,
            ]);

            $statement->forceFill([
                'net_amount' => -$refund,
                'status' => SettlementStatement::STATUS_AWAITING_REFUND,
                'calculated_at' => now(),
            ])->save();

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                $description,
                $request->return_note,
                [
                    'status' => $oldStatus,
                    'deposit_status' => $oldDepositStatus,
                    'deposit_amount' => (float) $contract->deposit_amount,
                ],
                [
                    'status' => $contract->status,
                    'deposit_status' => $contract->deposit_status,
                    'refund_amount' => $refund,
                    'deduction_amount' => $deduction,
                ]
            );
        });

        app(AdminNotificationService::class)->depositRefundAwaitingTransfer($contract->fresh());

        app(ClientNotificationService::class)->contract(
            $contract->fresh(),
            'deposit_refund_approved',
            'Yêu cầu hoàn cọc đã được duyệt',
            'Số tiền hoàn cọc được duyệt là '.number_format($refund, 0, ',', '.').' VNĐ. Ban quản lý đang thực hiện chuyển khoản.'
        );

        return back()->with(
            'success',
            'Đã xác nhận đúng số tiền hoàn theo quyết toán. Chờ Admin chuyển khoản và tải minh chứng.'
        );
    }

    /**
     * Xác nhận đã chuyển khoản + upload bằng chứng.
     */
    public function complete(Request $request, Contract $contract)
    {
        $isRequested = $contract->isRefundRequested();

        if (! $isRequested && ! $contract->isRefundApproved()) {
            return back()->with('error', 'Hợp đồng chưa ở trạng thái được phép xác nhận chuyển tiền.');
        }

        $request->validate([
            'confirm_refund_amount' => $isRequested ? ['accepted'] : ['nullable'],
            'transfer_amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'transfer_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'transfer_note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'deposit_process_type' => ['prohibited'],
            'deduction_amount' => ['prohibited'],
            'damage_proof' => ['prohibited'],
            'return_reason' => ['prohibited'],
        ], [
            'confirm_refund_amount.accepted' => 'Vui lòng xác nhận đúng số tiền hoàn đã được chốt khi quyết toán.',
        ]);

        $statement = null;
        if ($isRequested) {
            $statement = $contract->settlementStatement()->first();
            if (! $statement) {
                return back()->withInput()->with('error', 'Hợp đồng chưa có bảng quyết toán cuối kỳ.');
            }

            $statement = app(SettlementService::class)->refreshFinancials($statement);
            $contract->refresh();
            $expected = max(0, -(float) $statement->net_amount);
        } else {
            $expected = (float) $contract->deposit_refund_amount;
        }

        if ($expected <= 0) {
            return back()->withInput()->with('error', 'Hợp đồng không còn số dư cọc để hoàn sau khi bù trừ công nợ.');
        }

        $actual = (float) $request->transfer_amount;

        if (abs($actual - $expected) > 0.01) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Số tiền chuyển phải đúng bằng số tiền được duyệt: '.
                    number_format($expected, 0, ',', '.').' VNĐ.'
                );
        }

        $proofPath = $request->file('transfer_proof')
            ->store('deposit-refunds/proofs', 'public');

        DB::transaction(function () use ($contract, $request, $proofPath, $actual, $expected, $isRequested, $statement) {
            $oldStatus = $contract->status;
            $oldDepositStatus = $contract->deposit_status;

            if ($isRequested) {
                $deduction = max(0, (float) $contract->deposit_amount - $expected);

                $contract->forceFill([
                    'deposit_process_type' => 'full_refund',
                    'deposit_refund_amount' => $expected,
                    'deposit_deduction_amount' => $deduction,
                    'deposit_refund_approved_at' => now(),
                    'deposit_process_reason' => 'Admin xác nhận số dư hoàn cọc và chuyển khoản trong một bước.',
                    'deposit_admin_note' => $request->transfer_note,
                ])->save();

                $statement->forceFill([
                    'net_amount' => -$expected,
                    'status' => SettlementStatement::STATUS_AWAITING_REFUND,
                    'calculated_at' => now(),
                ])->save();
            }

            $contract->forceFill([
                'deposit_status' => Contract::DEPOSIT_REFUND_PROCESSING,
                'deposit_resolution' => null,
                'deposit_transfer_amount' => $actual,
                'deposit_transferred_at' => now(),
                'deposit_transfer_proof' => $proofPath,
                'deposit_processed_at' => now(),
                'deposit_receipt_confirmation_due_at' => now()->addHours(24),
                'deposit_receipt_confirmed_at' => null,
                'deposit_receipt_confirmation_source' => null,
                'deposit_resolved_at' => null,
                'deposit_resolved_by' => null,
                'deposit_process_note' => $request->transfer_note,
            ])->save();

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                $isRequested
                    ? 'Admin đã duyệt số tiền và chuyển khoản hoàn cọc trong một bước; đang chờ khách xác nhận đã nhận đủ tiền.'
                    : 'Admin đã chuyển tiền hoàn cọc; đang chờ khách xác nhận đã nhận đủ tiền.',
                $request->transfer_note,
                [
                    'status' => $oldStatus,
                    'deposit_status' => $oldDepositStatus,
                ],
                [
                    'status' => Contract::STATUS_SETTLING,
                    'deposit_status' => Contract::DEPOSIT_REFUND_PROCESSING,
                    'transfer_amount' => $actual,
                    'transfer_proof' => $proofPath,
                    'receipt_confirmation_due_at' => $contract->deposit_receipt_confirmation_due_at?->toDateTimeString(),
                ]
            );
        });

        app(AdminNotificationService::class)->resolve('deposit_refund_request', $contract);
        app(ClientNotificationService::class)->contract(
            $contract->fresh(),
            'deposit_refund_transferred',
            'Tiền cọc đã được chuyển',
            'Ban quản lý đã chuyển '.number_format($actual, 0, ',', '.').' VNĐ tiền hoàn cọc. Vui lòng kiểm tra tài khoản và xác nhận trong 24 giờ. Nếu không có phản hồi, hệ thống sẽ tự động coi như bạn đã nhận đủ tiền.'
        );

        return back()->with(
            'success',
            'Đã ghi nhận chuyển tiền. Hệ thống đang chờ khách xác nhận nhận đủ tiền trong 24 giờ.'
        );
    }

    /**
     * Từ chối yêu cầu hoàn cọc.
     */
    public function reject(Request $request, Contract $contract)
    {
        if (! $contract->isRefundRequested()) {
            return back()->with('error', 'Yêu cầu hoàn cọc này không còn chờ xử lý.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $contract->update([
            'deposit_status' => Contract::DEPOSIT_REFUND_REJECTED,
            'deposit_admin_note' => $request->reason,
        ]);
        app(AdminNotificationService::class)->resolve('deposit_refund_request', $contract);
        app(ClientNotificationService::class)->contract(
            $contract->fresh(),
            'deposit_refund_rejected',
            'Yêu cầu hoàn cọc bị từ chối',
            'Yêu cầu hoàn cọc chưa được chấp thuận. Lý do: '.$request->reason
        );

        return back()->with('success', 'Đã từ chối yêu cầu hoàn cọc.');
    }

    /**
     * Xóa/đổi ảnh QR cũ khi cần.
     */
    public function qr(Contract $contract)
    {
        abort_unless(
            filled($contract->deposit_qr_image)
            && Storage::disk('public')->exists($contract->deposit_qr_image),
            404
        );

        return response()->file(Storage::disk('public')->path($contract->deposit_qr_image));
    }

    public function proof(Contract $contract)
    {
        abort_unless(
            filled($contract->deposit_transfer_proof)
            && Storage::disk('public')->exists($contract->deposit_transfer_proof),
            404
        );

        return response()->file(Storage::disk('public')->path($contract->deposit_transfer_proof));
    }
}

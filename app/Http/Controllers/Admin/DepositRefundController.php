<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractHistoryService;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        if (!$contract->isRefundRequested()) {
            return back()->with('error', 'Yêu cầu hoàn cọc này không còn chờ duyệt.');
        }

        $request->validate([
            'deposit_process_type' => [
                'required',
                Rule::in(['full_refund', 'partial_refund', 'no_refund']),
            ],
            'deduction_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'damage_proof' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'return_reason' => [
                'required',
                'string',
                'max:255',
            ],
            'return_note' => [
                'nullable',
                'string',
                'max:1000',
            ],
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
        $type = $request->deposit_process_type;

        if ($type === 'full_refund') {
            $additionalDeduction = 0;
            $refund = $eligibleRefund;
            $description = 'Admin duyệt hoàn toàn bộ số dư tiền cọc sau bù trừ.';
        } elseif ($type === 'partial_refund') {
            $additionalDeduction = (float) $request->deduction_amount;

            if ($additionalDeduction <= 0 || $additionalDeduction >= $eligibleRefund) {
                return back()
                    ->withInput()
                    ->with('error', 'Khoản khấu trừ phải lớn hơn 0 và nhỏ hơn số dư tiền cọc được hoàn.');
            }

            $refund = $eligibleRefund - $additionalDeduction;
            $description = 'Admin duyệt hoàn một phần tiền cọc.';
        } else {
            $additionalDeduction = $eligibleRefund;
            $refund = 0;
            $description = 'Admin xác nhận không hoàn tiền cọc.';
        }

        $deduction = max(0, $deposit - $refund);

        // Chỉ khoản giữ thêm ngoài bảng quyết toán mới bắt buộc có ảnh chứng minh.
        if ($additionalDeduction > 0 && !$request->hasFile('damage_proof')) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Vui lòng tải ảnh chứng minh hư hỏng/thiệt hại khi có khấu trừ tiền cọc.'
                );
        }

        // Lưu ảnh chứng minh hư hỏng nếu Admin cung cấp.
        $damageProofPath = null;

        if ($request->hasFile('damage_proof')) {
            $damageProofPath = $request->file('damage_proof')
                ->store('deposit-refunds/damage-proofs', 'public');
        }

        DB::transaction(function () use (
            $contract,
            $request,
            $type,
            $deduction,
            $refund,
            $description,
            $damageProofPath,
            $statement,
        ) {
            $oldStatus = $contract->status;
            $oldDepositStatus = $contract->deposit_status;

            if ($type === 'no_refund') {
                $contract->forceFill([
                    'deposit_status' => Contract::DEPOSIT_FORFEITED,
                    'deposit_resolution' => Contract::DEPOSIT_RETAINED,
                    'deposit_process_type' => $type,
                    'deposit_refund_amount' => 0,
                    'deposit_deduction_amount' => $deduction,
                    'deposit_damage_proof' => $damageProofPath,
                    'deposit_processed_at' => now(),
                    'deposit_resolved_at' => now(),
                    'deposit_resolved_by' => Auth::id(),
                    'deposit_process_reason' => $request->return_reason,
                    'deposit_process_note' => $request->return_note,
                    'deposit_admin_note' => $request->return_note,
                ])->save();
            } else {
                $contract->update([
                    'deposit_status' => Contract::DEPOSIT_REFUND_APPROVED,
                    'deposit_process_type' => $type,
                    'deposit_refund_amount' => $refund,
                    'deposit_deduction_amount' => $deduction,
                    'deposit_damage_proof' => $damageProofPath,
                    'deposit_refund_approved_at' => now(),
                    'deposit_process_reason' => $request->return_reason,
                    'deposit_process_note' => $request->return_note,
                    'deposit_admin_note' => $request->return_note,
                ]);
            }

            $statement->forceFill([
                'net_amount' => -$refund,
                'status' => $refund > 0
                    ? \App\Models\SettlementStatement::STATUS_AWAITING_REFUND
                    : \App\Models\SettlementStatement::STATUS_BALANCED,
                'calculated_at' => now(),
            ])->save();

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                $description,
                $request->return_reason,
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

        if ($type === 'no_refund') {
            app(AdminNotificationService::class)->resolve('deposit_refund_request', $contract);
        } else {
            app(AdminNotificationService::class)->depositRefundAwaitingTransfer($contract->fresh());
        }

        app(ClientNotificationService::class)->contract(
            $contract->fresh(),
            $type === 'no_refund' ? 'deposit_refund_forfeited' : 'deposit_refund_approved',
            $type === 'no_refund' ? 'Kết quả xử lý tiền cọc' : 'Yêu cầu hoàn cọc đã được duyệt',
            $type === 'no_refund'
                ? 'Ban quản lý xác nhận không hoàn tiền cọc. Lý do: '.$request->return_reason
                : 'Số tiền hoàn cọc được duyệt là '.number_format($refund, 0, ',', '.').' VNĐ. Ban quản lý đang thực hiện chuyển khoản.'
        );

        if ($type === 'no_refund') {
            return back()->with('success', 'Đã xác nhận không hoàn cọc. Hợp đồng vẫn chờ bước kiểm tra và hoàn tất quyết toán.');
        }

        return back()->with(
            'success',
            'Đã duyệt số tiền hoàn cọc. Chờ Admin chuyển khoản và upload bằng chứng.'
        );
    }

    /**
     * Xác nhận đã chuyển khoản + upload bằng chứng.
     */
    public function complete(Request $request, Contract $contract)
    {
        if (!$contract->isRefundApproved()) {
            return back()->with('error', 'Hợp đồng chưa ở trạng thái được phép xác nhận chuyển tiền.');
        }

        $expected = (float) $contract->deposit_refund_amount;

        $request->validate([
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
        ]);

        $actual = (float) $request->transfer_amount;

        if (abs($actual - $expected) > 0.01) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Số tiền chuyển phải đúng bằng số tiền được duyệt: ' .
                    number_format($expected, 0, ',', '.') . ' VNĐ.'
                );
        }

        $proofPath = $request->file('transfer_proof')
            ->store('deposit-refunds/proofs', 'public');

        DB::transaction(function () use ($contract, $request, $proofPath, $actual) {
            $oldStatus = $contract->status;
            $oldDepositStatus = $contract->deposit_status;

            $finalDepositStatus =
                (float) $contract->deposit_deduction_amount > 0
                    ? Contract::DEPOSIT_PARTIAL
                    : Contract::DEPOSIT_RETURNED;

            $contract->forceFill([
                'deposit_status' => $finalDepositStatus,
                'deposit_resolution' => $finalDepositStatus === Contract::DEPOSIT_PARTIAL
                    ? Contract::DEPOSIT_DEDUCTED
                    : Contract::DEPOSIT_REFUNDED,
                'deposit_transfer_amount' => $actual,
                'deposit_transferred_at' => now(),
                'deposit_transfer_proof' => $proofPath,
                'deposit_processed_at' => now(),
                'deposit_resolved_at' => now(),
                'deposit_resolved_by' => Auth::id(),
                'deposit_process_note' => $request->transfer_note,
            ])->save();

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                'Admin đã chuyển tiền hoàn cọc; hợp đồng tiếp tục chờ hoàn tất quyết toán.',
                $request->transfer_note,
                [
                    'status' => $oldStatus,
                    'deposit_status' => $oldDepositStatus,
                ],
                [
                    'status' => Contract::STATUS_SETTLING,
                    'deposit_status' => $finalDepositStatus,
                    'transfer_amount' => $actual,
                    'transfer_proof' => $proofPath,
                ]
            );
        });

        app(AdminNotificationService::class)->resolve('deposit_refund_request', $contract);
        app(ClientNotificationService::class)->contract(
            $contract->fresh(),
            'deposit_refund_completed',
            'Tiền cọc đã được chuyển',
            'Ban quản lý đã chuyển '.number_format($actual, 0, ',', '.').' VNĐ tiền hoàn cọc. Bạn có thể mở hợp đồng để xem bằng chứng chuyển khoản.'
        );

        return back()->with(
            'success',
            'Đã xác nhận chuyển tiền hoàn cọc. Hợp đồng vẫn ở bước quyết toán cho đến khi toàn bộ công nợ được xử lý.'
        );
    }

    /**
     * Từ chối yêu cầu hoàn cọc.
     */
    public function reject(Request $request, Contract $contract)
    {
        if (!$contract->isRefundRequested()) {
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

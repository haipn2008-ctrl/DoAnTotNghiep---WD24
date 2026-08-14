<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractHistoryService;
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
        $contracts = Contract::with(['room', 'tenant'])
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

        $deposit = (float) $contract->deposit_amount;
        $type = $request->deposit_process_type;

        if ($type === 'full_refund') {
            $deduction = 0;
            $refund = $deposit;
            $description = 'Admin duyệt hoàn toàn bộ tiền cọc.';
        } elseif ($type === 'partial_refund') {
            $deduction = (float) $request->deduction_amount;

            if ($deduction <= 0 || $deduction >= $deposit) {
                return back()
                    ->withInput()
                    ->with('error', 'Khoản khấu trừ phải lớn hơn 0 và nhỏ hơn tiền cọc.');
            }

            $refund = $deposit - $deduction;
            $description = 'Admin duyệt hoàn một phần tiền cọc.';
        } else {
            $deduction = $deposit;
            $refund = 0;
            $description = 'Admin xác nhận không hoàn tiền cọc.';
        }

        // Có khấu trừ thì bắt buộc phải có ảnh chứng minh hư hỏng/thiệt hại.
        if ($deduction > 0 && !$request->hasFile('damage_proof')) {
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
            $damageProofPath
        ) {
            $oldStatus = $contract->status;
            $oldDepositStatus = $contract->deposit_status;

            if ($type === 'no_refund') {
                $contract->update([
                    'status' => Contract::STATUS_COMPLETED,
                    'deposit_status' => Contract::DEPOSIT_FORFEITED,
                    'deposit_process_type' => $type,
                    'deposit_refund_amount' => 0,
                    'deposit_deduction_amount' => $deduction,
                    'deposit_damage_proof' => $damageProofPath,
                    'deposit_processed_at' => now(),
                    'deposit_process_reason' => $request->return_reason,
                    'deposit_process_note' => $request->return_note,
                    'deposit_admin_note' => $request->return_note,
                ]);
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
            return back()->with('success', 'Đã xác nhận không hoàn cọc. Hợp đồng đã hoàn tất.');
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
                'numeric',
                'min:0',
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

            $contract->update([
                'status' => Contract::STATUS_COMPLETED,
                'deposit_status' => $finalDepositStatus,
                'deposit_transfer_amount' => $actual,
                'deposit_transferred_at' => now(),
                'deposit_transfer_proof' => $proofPath,
                'deposit_processed_at' => now(),
                'deposit_process_note' => $request->transfer_note,
            ]);

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                'Admin đã chuyển tiền hoàn cọc và xác nhận hoàn tất.',
                $request->transfer_note,
                [
                    'status' => $oldStatus,
                    'deposit_status' => $oldDepositStatus,
                ],
                [
                    'status' => Contract::STATUS_COMPLETED,
                    'deposit_status' => $finalDepositStatus,
                    'transfer_amount' => $actual,
                    'transfer_proof' => $proofPath,
                ]
            );
        });

        return back()->with(
            'success',
            'Đã xác nhận chuyển tiền và hoàn tất hợp đồng.'
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
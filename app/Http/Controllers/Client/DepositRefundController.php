<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepositRefundController extends Controller
{
    public function index(Contract $contract)
    {
        $user = Auth::user();

        // Chỉ cho khách thuê xem hợp đồng của chính mình
        abort_unless(
            $contract->tenant
            && $contract->tenant->user_id === $user->id,
            403,
            'Bạn không có quyền xem yêu cầu hoàn cọc của hợp đồng này.'
        );

        // Khách được xem thông tin hoàn cọc khi hợp đồng đã chấm dứt
        // hoặc đã hoàn tất sau khi Admin chuyển tiền.
        abort_unless(
            $contract->status === Contract::STATUS_TERMINATED,
            404
        );

        $contract->load([
            'room',
            'tenant',
            'histories.user',
        ]);

        return view(
            'client.contracts.deposit-refunds.index',
            compact('contract')
        );
    }

    public function store(Request $request, Contract $contract)
    {
        $user = Auth::user();

        abort_unless(
            $contract->tenant
            && $contract->tenant->user_id === $user->id,
            403,
            'Bạn không có quyền yêu cầu hoàn cọc cho hợp đồng này.'
        );

        if ($contract->deposit_resolution === Contract::DEPOSIT_NOT_REQUIRED) {
            return back()->with('error', 'Khoản thu sau khi ký đã được cấn vào tiền phòng tháng đầu nên không phải tiền cọc hoàn lại.');
        }

        if (!$contract->canRequestDepositRefund()) {
            return back()->with(
                'error',
                'Hợp đồng này chưa đủ điều kiện yêu cầu hoàn cọc hoặc đã có yêu cầu đang xử lý.'
            );
        }

        if ((float) $contract->deposit_amount <= 0) {
            return back()->with('error', 'Hợp đồng này không có tiền cọc để hoàn.');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:150',
            'qr_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'note' => 'nullable|string|max:1000',
        ], [
            'bank_name.required' => 'Vui lòng nhập tên ngân hàng.',
            'bank_account_number.required' => 'Vui lòng nhập số tài khoản.',
            'bank_account_name.required' => 'Vui lòng nhập tên chủ tài khoản.',
            'qr_image.image' => 'Ảnh QR không hợp lệ.',
        ]);

        $qrPath = $contract->deposit_qr_image;

        if ($request->hasFile('qr_image')) {
            if ($qrPath) {
                Storage::disk('public')->delete($qrPath);
            }

            $qrPath = $request->file('qr_image')
                ->store('deposit-refunds/qr', 'public');
        }

        DB::transaction(function () use ($contract, $validated, $qrPath) {
            $oldStatus = $contract->deposit_status;

            $contract->update([
                'deposit_status' => Contract::DEPOSIT_REFUND_REQUESTED,
                'deposit_bank_name' => $validated['bank_name'],
                'deposit_bank_account_number' => $validated['bank_account_number'],
                'deposit_bank_account_name' => $validated['bank_account_name'],
                'deposit_qr_image' => $qrPath,
                'deposit_refund_requested_at' => now(),
                'deposit_process_note' => $validated['note'] ?? null,
                'deposit_admin_note' => null,
            ]);

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                'Khách thuê gửi yêu cầu hoàn cọc.',
                $validated['note'] ?? null,
                [
                    'deposit_status' => $oldStatus,
                ],
                [
                    'deposit_status' => Contract::DEPOSIT_REFUND_REQUESTED,
                    'bank_name' => $validated['bank_name'],
                    'bank_account_number' => $validated['bank_account_number'],
                    'bank_account_name' => $validated['bank_account_name'],
                ]
            );
        });

        return redirect()
        ->route('client.deposit-refunds.index', ['contract' => $contract->id])
        ->with('success', 'Đã gửi yêu cầu hoàn cọc. Vui lòng chờ Admin xử lý.');
    }

    /**
     * Hiển thị bằng chứng Admin đã chuyển tiền hoàn cọc.
     * Chỉ chủ hợp đồng mới được xem file này.
     */
    public function proof(Request $request, Contract $contract)
    {
        $user = $request->user();

        abort_unless(
            $contract->tenant
            && $contract->tenant->user_id === $user->id,
            403
        );

        abort_unless(
            filled($contract->deposit_transfer_proof)
            && Storage::disk('public')->exists($contract->deposit_transfer_proof),
            404
        );

        return response()->file(Storage::disk('public')->path($contract->deposit_transfer_proof));
    }

    public function qr(Request $request, Contract $contract)
    {
        $user = $request->user();

        abort_unless(
            $contract->tenant
            && $contract->tenant->user_id === $user->id,
            403
        );

        abort_unless(
            filled($contract->deposit_qr_image)
            && Storage::disk('public')->exists($contract->deposit_qr_image),
            404
        );

        return response()->file(Storage::disk('public')->path($contract->deposit_qr_image));
    }
}

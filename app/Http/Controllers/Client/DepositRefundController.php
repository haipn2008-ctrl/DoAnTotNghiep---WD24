<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractHistoryService;
use App\Services\DepositRefundReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        abort_unless(in_array($contract->status, [
            Contract::STATUS_SETTLING,
            Contract::STATUS_COMPLETED,
        ], true), 404);

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
            return back()->with('error', 'Hợp đồng này không có khoản tiền cọc phải hoàn.');
        }

        if (! $contract->canRequestDepositRefund()) {
            return back()->with(
                'error',
                'Hợp đồng này chưa đủ điều kiện yêu cầu hoàn cọc hoặc đã có yêu cầu đang xử lý.'
            );
        }

        if ((float) $contract->deposit_amount <= 0) {
            return back()->with('error', 'Hợp đồng này không có tiền cọc để hoàn.');
        }

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100', Rule::in($this->paymentProviderNames())],
            'bank_account_number' => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:150',
            'qr_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'note' => 'nullable|string|max:1000',
        ], [
            'bank_name.required' => 'Vui lòng chọn ngân hàng hoặc ví điện tử.',
            'bank_name.in' => 'Ngân hàng hoặc ví điện tử đã chọn không hợp lệ.',
            'bank_account_number.required' => 'Vui lòng nhập số tài khoản hoặc số điện thoại ví.',
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

            app(AdminNotificationService::class)->depositRefundRequested($contract->fresh());
        });

        return redirect()
            ->route('client.deposit-refunds.index', ['contract' => $contract->id])
            ->with('success', 'Đã gửi yêu cầu hoàn cọc. Vui lòng chờ Admin xử lý.');
    }

    public function update(Request $request, Contract $contract)
    {
        $user = $request->user();
        abort_unless($contract->isManagedBy($user), 403, 'Bạn không có quyền sửa thông tin hoàn cọc này.');

        if (! $contract->isRefundRequested()) {
            return back()->with('error', 'Chỉ được sửa thông tin nhận tiền khi yêu cầu còn chờ Admin duyệt.');
        }

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100', Rule::in($this->paymentProviderNames())],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_name' => ['required', 'string', 'max:150'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'bank_name.required' => 'Vui lòng chọn ngân hàng hoặc ví điện tử.',
            'bank_name.in' => 'Ngân hàng hoặc ví điện tử đã chọn không hợp lệ.',
            'bank_account_number.required' => 'Vui lòng nhập số tài khoản hoặc số điện thoại ví.',
            'bank_account_name.required' => 'Vui lòng nhập tên chủ tài khoản.',
            'qr_image.image' => 'Ảnh QR không hợp lệ.',
        ]);

        $newQrPath = $request->hasFile('qr_image')
            ? $request->file('qr_image')->store('deposit-refunds/qr', 'public')
            : null;
        $oldQrPath = null;

        try {
            $contract = DB::transaction(function () use ($contract, $validated, $newQrPath, &$oldQrPath): Contract {
                $lockedContract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
                if (! $lockedContract->isRefundRequested()) {
                    throw ValidationException::withMessages([
                        'refund' => 'Admin đã bắt đầu xử lý; thông tin nhận tiền không thể thay đổi nữa.',
                    ]);
                }

                $oldData = [
                    'bank_name' => $lockedContract->deposit_bank_name,
                    'bank_account_number' => $lockedContract->deposit_bank_account_number,
                    'bank_account_name' => $lockedContract->deposit_bank_account_name,
                    'deposit_process_note' => $lockedContract->deposit_process_note,
                    'deposit_qr_image' => $lockedContract->deposit_qr_image,
                ];
                $oldQrPath = $lockedContract->deposit_qr_image;
                $qrPath = $newQrPath ?: $oldQrPath;

                $lockedContract->forceFill([
                    'deposit_bank_name' => $validated['bank_name'],
                    'deposit_bank_account_number' => trim($validated['bank_account_number']),
                    'deposit_bank_account_name' => mb_strtoupper(trim($validated['bank_account_name'])),
                    'deposit_qr_image' => $qrPath,
                    'deposit_process_note' => $validated['note'] ?? null,
                ])->save();

                ContractHistoryService::log(
                    $lockedContract,
                    'deposit_receiving_account_updated',
                    'Khách thuê chỉnh sửa thông tin nhận tiền hoàn cọc.',
                    $validated['note'] ?? null,
                    $oldData,
                    [
                        'bank_name' => $lockedContract->deposit_bank_name,
                        'bank_account_number' => $lockedContract->deposit_bank_account_number,
                        'bank_account_name' => $lockedContract->deposit_bank_account_name,
                        'deposit_process_note' => $lockedContract->deposit_process_note,
                        'deposit_qr_image' => $lockedContract->deposit_qr_image,
                    ],
                );

                return $lockedContract;
            }, 3);
        } catch (\Throwable $exception) {
            if ($newQrPath) {
                Storage::disk('public')->delete($newQrPath);
            }

            throw $exception;
        }

        if ($newQrPath && $oldQrPath && $oldQrPath !== $newQrPath) {
            Storage::disk('public')->delete($oldQrPath);
        }

        app(AdminNotificationService::class)->depositRefundRequested($contract->fresh());

        return back()->with('success', 'Đã cập nhật thông tin nhận tiền. Admin sẽ sử dụng thông tin mới nhất.');
    }

    public function confirmReceipt(
        Request $request,
        Contract $contract,
        DepositRefundReceiptService $receiptService,
    ) {
        $user = $request->user();

        abort_unless($contract->isManagedBy($user), 403, 'Bạn không có quyền xác nhận khoản hoàn cọc này.');

        $request->validate([
            'confirm_received' => ['accepted'],
        ], [
            'confirm_received.accepted' => 'Vui lòng xác nhận bạn đã kiểm tra và nhận đủ tiền hoàn cọc.',
        ]);

        $contract = $receiptService->confirm(
            $contract,
            DepositRefundReceiptService::SOURCE_TENANT,
            $user,
        );

        if ($contract->deposit_receipt_confirmation_source === DepositRefundReceiptService::SOURCE_TENANT) {
            app(ClientNotificationService::class)->contract(
                $contract,
                'deposit_refund_receipt_confirmed',
                'Đã xác nhận nhận đủ tiền hoàn cọc',
                'Bạn đã xác nhận nhận đủ '.number_format((float) $contract->deposit_transfer_amount, 0, ',', '.').' VNĐ tiền hoàn cọc.'
            );
        }

        return back()->with('success', 'Đã xác nhận bạn nhận đủ tiền hoàn cọc.');
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

    private function paymentProviderNames(): array
    {
        return collect(config('vietnam-payment-providers', []))->flatten()->values()->all();
    }
}

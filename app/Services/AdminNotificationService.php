<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractTenant;
use App\Models\ContractTerminationRequest;
use App\Models\Payment;
use App\Models\SupportRequest;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminNotificationService
{
    public function extensionRequested(ContractExtensionRequest $request): ContractLifecycleAlert
    {
        $request->loadMissing('contract.tenant', 'contract.room');
        $contract = $request->contract;

        return $this->actionAlert($contract, 'extension_request', "extension-request:{$request->id}",
            'Khách thuê vừa yêu cầu gia hạn hợp đồng',
            sprintf('%s muốn gia hạn hợp đồng %s (phòng %s) đến ngày %s%s', $this->tenantName($contract), $contract->contract_code, $contract->room?->room_code ?: '—', $request->requested_end_date?->format('d/m/Y'), filled($request->reason) ? '. Lý do: '.$request->reason : '.'), $request);
    }

    public function terminationRequested(ContractTerminationRequest $request): ContractLifecycleAlert
    {
        $request->loadMissing('contract.tenant', 'contract.room');
        $contract = $request->contract;

        return $this->actionAlert($contract, 'termination_request', "termination-request:{$request->id}",
            'Khách thuê vừa gửi yêu cầu trả phòng',
            sprintf('%s muốn trả phòng %s ngày %s. Lý do: %s', $this->tenantName($contract), $contract->room?->room_code ?: '—', $request->requested_end_date?->format('d/m/Y'), $request->reason), $request);
    }

    public function depositRefundRequested(Contract $contract): ContractLifecycleAlert
    {
        $contract->loadMissing('tenant', 'room');

        return $this->actionAlert($contract, 'deposit_refund_request', "deposit-refund:{$contract->id}:".now()->format('YmdHisv'),
            'Khách thuê vừa yêu cầu hoàn tiền cọc',
            sprintf('%s đã gửi tài khoản %s - %s để nhận hoàn cọc hợp đồng %s.', $this->tenantName($contract), $contract->deposit_bank_name, $contract->deposit_bank_account_number, $contract->contract_code), $contract);
    }

    public function depositRefundAwaitingTransfer(Contract $contract): void
    {
        ContractLifecycleAlert::query()
            ->where('contract_id', $contract->id)
            ->where('type', 'deposit_refund_request')
            ->whereNull('resolved_at')
            ->get()
            ->each(function (ContractLifecycleAlert $alert) use ($contract): void {
                $alert->update([
                    'title' => 'Yêu cầu hoàn cọc đã duyệt, chờ chuyển tiền',
                    'message' => sprintf('Cần chuyển %s VNĐ cho %s theo thông tin tài khoản khách đã cung cấp.', number_format((float) $contract->deposit_refund_amount, 0, ',', '.'), $this->tenantName($contract->loadMissing('tenant'))),
                ]);
            });
    }

    public function paymentSubmitted(Payment $payment): ContractLifecycleAlert
    {
        $payment->loadMissing('invoice.contract.tenant', 'invoice.contract.room');
        $invoice = $payment->invoice;
        $contract = $invoice->contract;

        return $this->actionAlert($contract, 'payment_review', "payment-review:{$payment->id}",
            'Khách thuê vừa gửi xác nhận thanh toán',
            sprintf('%s đã báo thanh toán %s VNĐ cho hóa đơn %s và có tải ảnh minh chứng.', $this->tenantName($contract), number_format((float) $payment->amount_paid, 0, ',', '.'), $invoice->invoice_code), $payment);
    }

    public function supportRequested(SupportRequest $request): ContractLifecycleAlert
    {
        $request->loadMissing('contract.tenant', 'contract.room');
        $contract = $request->contract;
        $category = match ($request->category) {
            'repair' => 'sửa chữa', 'invoice' => 'hóa đơn', 'utility' => 'điện nước',
            'contract' => 'hợp đồng', default => 'khác',
        };

        return $this->actionAlert($contract, 'support_request', "support-request:{$request->id}",
            "Khách thuê vừa gửi yêu cầu hỗ trợ {$category}",
            sprintf('%s gửi yêu cầu “%s”%s', $this->tenantName($contract), $request->subject, $request->attachment ? ' kèm hình ảnh.' : '.'), $request);
    }

    public function memberSubmitted(ContractTenant $member): ContractLifecycleAlert
    {
        $member->loadMissing('contract.tenant', 'contract.room');
        $contract = $member->contract;

        return $this->actionAlert($contract, 'member_review', "member-review:{$member->id}",
            'Khách thuê vừa khai báo thêm người ở cùng',
            sprintf('%s đã khai báo %s (CCCD %s) vào hợp đồng %s, phòng %s.', $this->tenantName($contract), $member->full_name, $member->identity_number, $contract->contract_code, $contract->room?->room_code ?: '—'), $member);
    }

    public function moveInDetailsConfirmed(Contract $contract): ContractLifecycleAlert
    {
        $contract->loadMissing('tenant', 'room');
        return $this->actionAlert($contract, 'move_in_confirmation', "move-in-confirmation:{$contract->id}",
            'Khách thuê đã xác nhận thông tin nhận phòng',
            sprintf('%s đã kiểm tra dịch vụ và tài sản của phòng %s.', $this->tenantName($contract), $contract->room?->room_code ?: '—'), $contract);
    }

    public function resolve(string $type, Model|int $reference): void
    {
        $id = $reference instanceof Model ? $reference->getKey() : $reference;
        $prefix = match ($type) {
            'extension_request' => 'extension-request', 'termination_request' => 'termination-request',
            'deposit_refund_request' => 'deposit-refund', 'payment_review' => 'payment-review',
            'support_request' => 'support-request', 'member_review' => 'member-review',
            'move_in_confirmation' => 'move-in-confirmation', default => $type,
        };

        ContractLifecycleAlert::query()
            ->when(
                $type === 'deposit_refund_request',
                fn ($query) => $query->where('dedupe_key', 'like', "{$prefix}:{$id}:%"),
                fn ($query) => $query->where('dedupe_key', "{$prefix}:{$id}")
            )
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }

    public function vehicleSubmitted(Vehicle $vehicle, bool $isChange = false): ContractLifecycleAlert
    {
        $this->resolveVehicleReviews($vehicle);
        $vehicle->loadMissing('tenant');
        $tenantName = $vehicle->tenant?->full_name ?: 'Khách thuê';
        $vehicleName = $vehicle->vehicle_name ?: $this->vehicleTypeLabel($vehicle->vehicle_type);

        return ContractLifecycleAlert::query()->create([
            'contract_id' => null, 'tenant_id' => $vehicle->tenant_id, 'vehicle_id' => $vehicle->id,
            'type' => 'vehicle_review', 'dedupe_key' => 'vehicle-review:'.Str::uuid(),
            'title' => $isChange ? 'Khách đổi phương tiện, cần duyệt lại' : 'Phương tiện mới chờ duyệt',
            'message' => "{$tenantName} đã ".($isChange ? 'thay đổi' : 'đăng ký')." {$vehicleName}. Vui lòng kiểm tra thông tin và ảnh xe.",
            'metadata' => ['event' => $isChange ? 'changed' : 'submitted'],
            'detected_at' => now(),
        ]);
    }

    public function vehicleReviewed(Vehicle $vehicle): void
    {
        $this->resolveVehicleReviews($vehicle);
    }

    public function vehicleRequestCancelled(Vehicle $vehicle): void
    {
        $this->resolveVehicleReviews($vehicle);
    }

    public function vehicleRemoved(Vehicle $vehicle): ContractLifecycleAlert
    {
        $this->resolveVehicleReviews($vehicle);
        $vehicle->loadMissing('tenant');
        $tenantName = $vehicle->tenant?->full_name ?: 'Khách thuê';
        $vehicleName = $vehicle->vehicle_name ?: $this->vehicleTypeLabel($vehicle->vehicle_type);
        $identifier = $vehicle->license_plate ? " ({$vehicle->license_plate})" : '';

        return ContractLifecycleAlert::query()->create([
            'contract_id' => null, 'tenant_id' => $vehicle->tenant_id, 'vehicle_id' => $vehicle->id,
            'type' => 'vehicle_removed', 'dedupe_key' => 'vehicle-removed:'.Str::uuid(),
            'title' => 'Khách đã gỡ phương tiện',
            'message' => "{$tenantName} đã gỡ {$vehicleName}{$identifier} khỏi danh sách phương tiện.",
            'metadata' => ['event' => 'removed'],
            'detected_at' => now(),
        ]);
    }

    private function actionAlert(Contract $contract, string $type, string $dedupeKey, string $title, string $message, Model $reference): ContractLifecycleAlert
    {
        return ContractLifecycleAlert::query()->updateOrCreate(
            ['contract_id' => $contract->id, 'type' => $type, 'dedupe_key' => $dedupeKey],
            [
                'tenant_id' => $contract->tenant_id, 'title' => $title, 'message' => $message,
                'metadata' => ['reference_type' => class_basename($reference), 'reference_id' => $reference->getKey()],
                'detected_at' => now(), 'resolved_at' => null,
            ]
        );
    }

    private function tenantName(?Contract $contract): string
    {
        return $contract?->tenant?->full_name ?: 'Khách thuê';
    }

    private function resolveVehicleReviews(Vehicle $vehicle): void
    {
        ContractLifecycleAlert::query()->where('vehicle_id', $vehicle->id)->where('type', 'vehicle_review')
            ->whereNull('resolved_at')->update(['resolved_at' => now()]);
    }

    private function vehicleTypeLabel(string $type): string
    {
        return match ($type) {
            'motorcycle' => 'xe máy', 'electric_motorcycle' => 'xe máy điện',
            'bicycle' => 'xe đạp', default => 'phương tiện',
        };
    }
}

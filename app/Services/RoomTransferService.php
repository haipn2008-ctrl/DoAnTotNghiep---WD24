<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\RoomTransferItem;
use App\Models\Setting;
use App\Models\TemporaryResidence;
use App\Models\User;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomTransferService
{
    public function __construct(private readonly ContractRateResolver $pricing) {}

    public function requestByTenant(Contract $contract, Room $targetRoom, User $tenantUser, array $data): RoomTransfer
    {
        return DB::transaction(function () use ($contract, $targetRoom, $tenantUser, $data): RoomTransfer {
            $contract = Contract::query()->with('currentMembers')->managedBy($tenantUser)
                ->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            $targetRoom = Room::query()->lockForUpdate()->findOrFail($targetRoom->id);
            $this->ensureContractCanTransfer($contract);
            $this->ensureNoPendingTransfer($contract);
            $this->ensureTargetCanReceive($contract, $targetRoom);

            return RoomTransfer::query()->create([
                'contract_id' => $contract->id,
                'old_room_id' => $contract->room_id,
                'new_room_id' => $targetRoom->id,
                'requested_by' => $tenantUser->id,
                'source' => RoomTransfer::SOURCE_TENANT,
                'requested_transfer_date' => $data['requested_transfer_date'],
                'reason' => $data['reason'],
                'status' => RoomTransfer::STATUS_PENDING,
            ]);
        }, 3);
    }

    public function createAndExecute(Contract $contract, Room $targetRoom, User $admin, array $data): RoomTransfer
    {
        return DB::transaction(function () use ($contract, $targetRoom, $admin, $data): RoomTransfer {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $this->ensureNoPendingTransfer($contract);
            $transfer = RoomTransfer::query()->create([
                'contract_id' => $contract->id,
                'old_room_id' => $contract->room_id,
                'new_room_id' => $targetRoom->id,
                'requested_by' => $admin->id,
                'source' => RoomTransfer::SOURCE_ADMIN,
                'requested_transfer_date' => today(),
                'reason' => $data['admin_reason'],
                'status' => RoomTransfer::STATUS_PENDING,
            ]);

            return $this->perform($transfer, $admin, $data);
        }, 3);
    }

    public function approveAndExecute(RoomTransfer $transfer, User $admin, array $data): RoomTransfer
    {
        return DB::transaction(fn (): RoomTransfer => $this->perform($transfer, $admin, $data), 3);
    }

    public function reject(RoomTransfer $transfer, User $admin, string $reason): RoomTransfer
    {
        return DB::transaction(function () use ($transfer, $admin, $reason): RoomTransfer {
            $transfer = RoomTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status !== RoomTransfer::STATUS_PENDING) {
                $this->fail('transfer', 'Yêu cầu đổi phòng này đã được xử lý.');
            }
            $transfer->forceFill([
                'status' => RoomTransfer::STATUS_REJECTED,
                'admin_reason' => $reason,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ])->save();

            return $transfer->fresh(['contract.tenant.user', 'oldRoom', 'newRoom']);
        }, 3);
    }

    private function perform(RoomTransfer $transfer, User $admin, array $data): RoomTransfer
    {
        $transfer = RoomTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
        if ($transfer->status !== RoomTransfer::STATUS_PENDING) {
            $this->fail('transfer', 'Yêu cầu đổi phòng này đã được xử lý.');
        }

        $effectiveDate = Carbon::parse($data['effective_date'])->startOfDay();
        if (! $effectiveDate->isToday()) {
            $this->fail('effective_date', 'Để bảo toàn chỉ số và hóa đơn, ngày chuyển thực tế phải là hôm nay.');
        }

        $contract = Contract::query()->with(['currentMembers', 'handoverItems'])->lockForUpdate()->findOrFail($transfer->contract_id);
        $oldRoom = Room::query()->lockForUpdate()->findOrFail($contract->room_id);
        $targetRoom = Room::query()->with('amenities')->lockForUpdate()->findOrFail($transfer->new_room_id);
        $this->ensureContractCanTransfer($contract);
        if ((int) $oldRoom->id !== (int) $transfer->old_room_id) {
            $this->fail('transfer', 'Phòng hiện tại của hợp đồng đã thay đổi sau khi yêu cầu được tạo.');
        }
        $this->ensureTargetCanReceive($contract, $targetRoom);
        $this->ensureTransferMonthIsOpen($contract, $effectiveDate);

        $outstandingBefore = $this->outstanding($contract);
        $oldReading = $this->latestReading($oldRoom, $contract, $effectiveDate);
        $oldElectricity = (int) $data['old_electricity'];
        $oldWater = (int) $data['old_water'];
        $oldElectricityBase = (int) ($oldReading?->electricity_new ?? $oldElectricity);
        $oldWaterBase = (int) ($oldReading?->water_new ?? $oldWater);
        if ($oldElectricity < $oldElectricityBase) {
            $this->fail('old_electricity', "Chỉ số điện phòng cũ không được nhỏ hơn {$oldElectricityBase}.");
        }
        if ($oldWater < $oldWaterBase) {
            $this->fail('old_water', "Chỉ số nước phòng cũ không được nhỏ hơn {$oldWaterBase}.");
        }

        $targetReading = UtilityReading::query()->where('room_id', $targetRoom->id)
            ->whereDate('record_date', '<=', $effectiveDate)->latest('record_date')->latest('id')->first();
        $newElectricity = (int) $data['new_electricity'];
        $newWater = (int) $data['new_water'];
        if ($targetReading && $newElectricity < (int) $targetReading->electricity_new) {
            $this->fail('new_electricity', "Chỉ số điện phòng mới không được nhỏ hơn {$targetReading->electricity_new}.");
        }
        if ($targetReading && $newWater < (int) $targetReading->water_new) {
            $this->fail('new_water', "Chỉ số nước phòng mới không được nhỏ hơn {$targetReading->water_new}.");
        }

        $checkout = UtilityReading::query()->create([
            'room_id' => $oldRoom->id,
            'contract_id' => $contract->id,
            'month' => $effectiveDate->month,
            'year' => $effectiveDate->year,
            'reading_type' => 'transfer_checkout',
            'record_date' => $effectiveDate,
            'electricity_old' => $oldElectricityBase,
            'electricity_new' => $oldElectricity,
            'water_old' => $oldWaterBase,
            'water_new' => $oldWater,
            'status' => UtilityReading::STATUS_CONFIRMED,
            'note' => 'Chốt chỉ số khi chuyển sang phòng '.$targetRoom->room_code.'.',
            'lifecycle_event_key' => "room-transfer:{$transfer->id}:checkout",
        ]);
        $handover = UtilityReading::query()->create([
            'room_id' => $targetRoom->id,
            'contract_id' => $contract->id,
            'month' => $effectiveDate->month,
            'year' => $effectiveDate->year,
            'reading_type' => 'transfer_handover',
            'record_date' => $effectiveDate,
            'electricity_old' => $newElectricity,
            'electricity_new' => $newElectricity,
            'water_old' => $newWater,
            'water_new' => $newWater,
            'status' => UtilityReading::STATUS_CONFIRMED,
            'note' => 'Chỉ số đầu khi chuyển từ phòng '.$oldRoom->room_code.'.',
            'lifecycle_event_key' => "room-transfer:{$transfer->id}:handover",
        ]);

        $oldDeposit = (float) $contract->deposit_amount;
        $newDeposit = (float) $targetRoom->price;
        $depositDifference = round($newDeposit - $oldDeposit);
        [$invoice, $remainingCredit, $financialItems] = $this->createTransferInvoice(
            $transfer,
            $contract,
            $oldRoom,
            $checkout,
            $effectiveDate,
            $depositDifference,
        );
        $depositInvoice = $depositDifference > 0
            ? $this->createAdditionalDepositInvoice($transfer, $contract, $targetRoom, $effectiveDate, $depositDifference)
            : null;

        $this->snapshotAssets($transfer, $oldRoom, RoomTransferItem::PHASE_OLD_CHECKOUT, $contract->handoverItems, $data['old_assets'] ?? []);
        $this->snapshotAssets($transfer, $targetRoom, RoomTransferItem::PHASE_NEW_HANDOVER, $targetRoom->amenities, $data['new_assets'] ?? []);
        $this->replaceContractHandover($contract, $targetRoom, $data['new_assets'] ?? []);
        TemporaryResidence::query()->where('contract_id', $contract->id)
            ->whereIn('status', ['pending', 'active'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $admin->id,
                'cancellation_reason' => "Hết hiệu lực tại phòng {$oldRoom->room_code} do chuyển sang phòng {$targetRoom->room_code}.",
            ]);

        $people = max(1, $contract->currentMembers->count(), (int) $contract->number_of_people);
        $oldRoom->forceFill(['status' => Room::STATUS_AVAILABLE, 'current_people' => 0])->save();
        $targetRoom->forceFill(['status' => Room::STATUS_OCCUPIED, 'current_people' => $people])->save();

        $oldSnapshot = ContractHistoryService::snapshot($contract);
        $contract->forceFill([
            'room_id' => $targetRoom->id,
            'monthly_rent' => $targetRoom->price,
            'deposit_amount' => $newDeposit,
            'deposit_status' => $depositDifference > 0 ? Contract::DEPOSIT_PENDING : $contract->deposit_status,
            'move_in_inventory_snapshotted_at' => now(),
            'move_in_details_confirmed_at' => now(),
            'move_in_details_confirmed_by' => $admin->id,
        ])->save();

        $transfer->forceFill([
            'status' => RoomTransfer::STATUS_COMPLETED,
            'effective_date' => $effectiveDate,
            'admin_reason' => $data['admin_reason'],
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'completed_at' => now(),
            'old_checkout_reading_id' => $checkout->id,
            'new_handover_reading_id' => $handover->id,
            'transfer_invoice_id' => $invoice?->id,
            'deposit_invoice_id' => $depositInvoice?->id,
            'outstanding_amount' => $outstandingBefore,
            'old_monthly_rent' => $oldSnapshot['monthly_rent'],
            'new_monthly_rent' => $targetRoom->price,
            'old_deposit_amount' => $oldDeposit,
            'new_deposit_amount' => $newDeposit,
            'deposit_difference' => $depositDifference,
            'remaining_deposit_credit' => $remainingCredit,
            'financial_snapshot' => [
                'outstanding_before_transfer' => $outstandingBefore,
                'old_room_charge' => $invoice ? (float) $invoice->total_amount : 0,
                'additional_deposit_invoice_id' => $depositInvoice?->id,
                'outstanding_after_transfer' => $this->outstanding($contract),
                'items' => $financialItems,
            ],
        ])->save();

        ContractHistoryService::log(
            $contract,
            ContractHistoryService::ROOM_TRANSFERRED,
            "Đã chuyển phòng từ {$oldRoom->room_code} sang {$targetRoom->room_code}.",
            $data['admin_reason'],
            $oldSnapshot,
            ContractHistoryService::snapshot($contract),
            $admin->id,
        );

        return $transfer->fresh([
            'contract.tenant.user', 'oldRoom', 'newRoom', 'oldCheckoutReading',
            'newHandoverReading', 'transferInvoice', 'depositInvoice', 'items',
        ]);
    }

    private function createTransferInvoice(
        RoomTransfer $transfer,
        Contract $contract,
        Room $oldRoom,
        UtilityReading $checkout,
        Carbon $date,
        float $depositDifference,
    ): array {
        $periodStart = $date->copy()->startOfMonth();
        if ($contract->actual_move_in_at && Carbon::parse($contract->actual_move_in_at)->gt($periodStart)) {
            $periodStart = Carbon::parse($contract->actual_move_in_at)->startOfDay();
        }
        $previousTransfer = RoomTransfer::query()->where('contract_id', $contract->id)
            ->where('status', RoomTransfer::STATUS_COMPLETED)->whereDate('effective_date', '<', $date)
            ->latest('effective_date')->latest('id')->first();
        if ($previousTransfer && $previousTransfer->effective_date->gt($periodStart)) {
            $periodStart = $previousTransfer->effective_date->copy()->startOfDay();
        }

        $lastOldRoomDay = $date->copy()->subDay();
        $days = $lastOldRoomDay->gte($periodStart) ? $periodStart->diffInDays($lastOldRoomDay) + 1 : 0;
        $rates = $this->pricing->forPeriod($contract, $date);
        $items = [];
        $sort = 1;
        if ($days > 0) {
            $dailyRent = (float) $contract->monthly_rent / $date->daysInMonth;
            $items[] = $this->invoiceItem('room', 'Tiền phòng cũ đến ngày chuyển phòng', $days, 'ngày', $dailyRent, $sort++, "Phòng {$oldRoom->room_code}, từ {$periodStart->format('d/m/Y')} đến {$lastOldRoomDay->format('d/m/Y')}");
        }
        $electricity = $checkout->electricity_new - $checkout->electricity_old;
        $water = $checkout->water_new - $checkout->water_old;
        if ($electricity > 0) {
            $items[] = $this->invoiceItem('electricity', 'Điện phòng cũ đến lúc chuyển phòng', $electricity, 'kWh', (float) $rates->electric_price, $sort++, "Chỉ số {$checkout->electricity_old} → {$checkout->electricity_new}");
        }
        if ($water > 0) {
            $items[] = $this->invoiceItem('water', 'Nước phòng cũ đến lúc chuyển phòng', $water, 'm³', (float) $rates->water_price, $sort++, "Chỉ số {$checkout->water_old} → {$checkout->water_new}");
        }
        foreach ([['internet', 'Internet phòng cũ', (float) ($rates->internet_fee ?? 0)], ['service', 'Dịch vụ phòng cũ', (float) ($rates->service_fee ?? 0)]] as [$type, $name, $monthlyFee]) {
            if ($days > 0 && $monthlyFee > 0) {
                $items[] = $this->invoiceItem($type, $name, $days, 'ngày', $monthlyFee / $date->daysInMonth, $sort++, 'Tính theo số ngày sử dụng phòng cũ.');
            }
        }
        $charges = round((float) collect($items)->sum('amount'));
        $remainingCredit = max(0, -$depositDifference);
        if ($remainingCredit > 0 && $charges > 0) {
            $appliedCredit = min($charges, $remainingCredit);
            $items[] = $this->invoiceItem('deposit_credit', 'Khấu trừ chênh lệch cọc phòng cũ', 1, 'lần', -$appliedCredit, $sort++, 'Phần cọc giảm được bù trừ vào chi phí chuyển phòng.');
            $charges -= $appliedCredit;
            $remainingCredit -= $appliedCredit;
        }

        if ($charges <= 0) {
            return [null, round($remainingCredit), $items];
        }

        $invoice = Invoice::query()->forceCreate([
            'contract_id' => $contract->id,
            'room_id' => $oldRoom->id,
            'utility_reading_id' => $checkout->id,
            'invoice_type' => Invoice::TYPE_SETTLEMENT,
            'revision' => $this->nextInvoiceRevision($contract, Invoice::TYPE_SETTLEMENT, $date),
            'lifecycle_event_key' => "room-transfer:{$transfer->id}:financials",
            'invoice_code' => null,
            'month' => $date->month,
            'year' => $date->year,
            'invoice_date' => $date,
            'due_date' => $date->copy()->addDays((int) Setting::currentOrCreate()->payment_due_days),
            'room_fee' => collect($items)->where('type', 'room')->sum('amount'),
            'electricity_fee' => collect($items)->where('type', 'electricity')->sum('amount'),
            'water_fee' => collect($items)->where('type', 'water')->sum('amount'),
            'internet_fee' => collect($items)->where('type', 'internet')->sum('amount'),
            'service_fee' => collect($items)->where('type', 'service')->sum('amount'),
            'total_amount' => $charges,
            'status' => Invoice::STATUS_UNPAID,
        ]);
        $invoice->forceFill(['invoice_code' => sprintf('MOV-%04d%02d-%06d', $date->year, $date->month, $invoice->id)])->save();
        foreach ($items as $item) {
            $invoice->details()->create($item);
        }

        return [$invoice, round($remainingCredit), $items];
    }

    private function createAdditionalDepositInvoice(
        RoomTransfer $transfer,
        Contract $contract,
        Room $targetRoom,
        Carbon $date,
        float $difference,
    ): Invoice {
        $invoice = Invoice::query()->forceCreate([
            'contract_id' => $contract->id,
            'room_id' => $targetRoom->id,
            'invoice_type' => Invoice::TYPE_DEPOSIT,
            'revision' => $this->nextInvoiceRevision($contract, Invoice::TYPE_DEPOSIT, $date),
            'lifecycle_event_key' => "room-transfer:{$transfer->id}:deposit",
            'invoice_code' => null,
            'month' => $date->month,
            'year' => $date->year,
            'invoice_date' => $date,
            'due_date' => $date->copy()->addDays((int) Setting::currentOrCreate()->payment_due_days),
            'room_fee' => 0,
            'total_amount' => $difference,
            'status' => Invoice::STATUS_UNPAID,
        ]);
        $invoice->forceFill(['invoice_code' => sprintf('DPM-%04d%02d-%06d', $date->year, $date->month, $invoice->id)])->save();
        $invoice->details()->create($this->invoiceItem(
            'deposit_adjustment',
            'Bổ sung chênh lệch tiền cọc phòng '.$targetRoom->room_code,
            1,
            'lần',
            $difference,
            1,
            'Khoản cọc bổ sung do chuyển sang phòng có mức cọc cao hơn.',
        ));

        return $invoice;
    }

    private function nextInvoiceRevision(Contract $contract, string $invoiceType, Carbon $date): int
    {
        return (int) Invoice::query()
            ->where('contract_id', $contract->id)
            ->where('invoice_type', $invoiceType)
            ->where('month', $date->month)
            ->where('year', $date->year)
            ->max('revision') + 1;
    }

    private function invoiceItem(string $type, string $name, float $quantity, string $unit, float $unitPrice, int $sort, string $note): array
    {
        return [
            'type' => $type, 'name' => $name, 'quantity' => $quantity, 'unit' => $unit,
            'unit_price' => round($unitPrice, 2), 'amount' => round($quantity * $unitPrice),
            'old_index' => null, 'new_index' => null, 'note' => $note, 'sort_order' => $sort,
        ];
    }

    private function snapshotAssets(RoomTransfer $transfer, Room $room, string $phase, iterable $assets, array $submitted): void
    {
        foreach ($assets as $asset) {
            $amenityId = (int) ($asset->amenity_id ?? $asset->id);
            $input = $submitted[$amenityId] ?? [];
            $transfer->items()->create([
                'room_id' => $room->id,
                'amenity_id' => $amenityId ?: null,
                'phase' => $phase,
                'name' => $asset->name,
                'is_quantifiable' => (bool) $asset->is_quantifiable,
                'quantity' => max(0, (int) ($input['quantity'] ?? $asset->quantity ?? $asset->pivot?->quantity ?? 1)),
                'condition' => $input['condition'] ?? $asset->condition ?? $asset->pivot?->condition ?? 'normal',
                'note' => $input['note'] ?? $asset->note ?? $asset->pivot?->note,
            ]);
        }
    }

    private function replaceContractHandover(Contract $contract, Room $room, array $submitted): void
    {
        $contract->handoverItems()->delete();
        foreach ($room->amenities as $asset) {
            $input = $submitted[$asset->id] ?? [];
            $contract->handoverItems()->create([
                'amenity_id' => $asset->id,
                'name' => $asset->name,
                'description' => $asset->description,
                'is_quantifiable' => $asset->is_quantifiable,
                'quantity' => max(0, (int) ($input['quantity'] ?? $asset->pivot->quantity ?? 1)),
                'condition' => $input['condition'] ?? $asset->pivot->condition ?? 'normal',
                'note' => $input['note'] ?? $asset->pivot->note,
            ]);
        }
    }

    private function latestReading(Room $room, Contract $contract, Carbon $date): ?UtilityReading
    {
        return UtilityReading::query()->where('room_id', $room->id)
            ->where(fn ($query) => $query->where('contract_id', $contract->id)->orWhereNull('contract_id'))
            ->whereDate('record_date', '<=', $date)->latest('record_date')->latest('id')->first();
    }

    private function outstanding(Contract $contract): float
    {
        return round((float) Invoice::query()->where('contract_id', $contract->id)
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->with(['payments', 'adjustments'])->get()
            ->sum(fn (Invoice $invoice): float => (float) $invoice->remaining_amount));
    }

    private function ensureContractCanTransfer(Contract $contract): void
    {
        if (! in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true) || ! $contract->actual_move_in_at) {
            $this->fail('contract', 'Chỉ hợp đồng có khách đang ở mới được đổi phòng.');
        }
    }

    private function ensureNoPendingTransfer(Contract $contract): void
    {
        if ($contract->roomTransfers()->where('status', RoomTransfer::STATUS_PENDING)->exists()) {
            $this->fail('contract', 'Hợp đồng đang có một yêu cầu đổi phòng chờ xử lý.');
        }
    }

    private function ensureTargetCanReceive(Contract $contract, Room $targetRoom): void
    {
        if ((int) $targetRoom->id === (int) $contract->room_id) {
            $this->fail('new_room_id', 'Phòng mới phải khác phòng hiện tại.');
        }
        if ($targetRoom->status !== Room::STATUS_AVAILABLE || $targetRoom->reservingContract()->whereKeyNot($contract->id)->exists()) {
            $this->fail('new_room_id', 'Phòng được chọn không còn trống.');
        }
        $people = max(1, $contract->currentMembers->count(), (int) $contract->number_of_people);
        if ($people > (int) $targetRoom->max_people) {
            $this->fail('new_room_id', "Phòng {$targetRoom->room_code} chỉ chứa tối đa {$targetRoom->max_people} người.");
        }
    }

    private function ensureTransferMonthIsOpen(Contract $contract, Carbon $date): void
    {
        if (RoomTransfer::query()->where('contract_id', $contract->id)->where('status', RoomTransfer::STATUS_COMPLETED)
            ->whereYear('effective_date', $date->year)->whereMonth('effective_date', $date->month)->exists()) {
            $this->fail('effective_date', 'Mỗi hợp đồng chỉ được đổi phòng một lần trong cùng tháng để bảo toàn kỳ tính phí.');
        }
        $billingPeriod = $date->copy()->addMonthNoOverflow()->startOfMonth();
        if (Invoice::query()->where('contract_id', $contract->id)->where('invoice_type', Invoice::TYPE_RENTAL)
            ->where('month', $billingPeriod->month)->where('year', $billingPeriod->year)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)->exists()) {
            $this->fail('effective_date', 'Kỳ phí chứa ngày chuyển đã phát hành hóa đơn; cần hủy/điều chỉnh hóa đơn trước khi đổi phòng.');
        }
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

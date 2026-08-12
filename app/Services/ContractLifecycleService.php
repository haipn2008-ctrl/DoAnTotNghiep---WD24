<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractOccupant;
use App\Models\ContractStatusHistory;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContractLifecycleService
{
    public function __construct(private readonly ContractOccupantService $occupantService) {}

    public function createDraft(array $data, User $actor): Contract
    {
        return DB::transaction(function () use ($data, $actor): Contract {
            $this->ensureDraftDataIsValid($data);
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);
            $this->ensureRoomCanAcceptDraftSchedule($room, $data);
            $tenant = Tenant::query()->with('user')->lockForUpdate()->findOrFail($data['tenant_id']);
            $representativeIsOccupant = (bool) ($data['representative_is_occupant'] ?? false);
            $numberOfPeople = count($data['occupants'] ?? []) + (int) $representativeIsOccupant;

            if ($room->status === Room::STATUS_MAINTENANCE) {
                $this->fail('room_id', 'Phòng đang bảo trì, chưa thể lập hợp đồng cho phòng này.');
            }
            if (! $tenant->user || ! in_array($tenant->user->status, [User::STATUS_PENDING, User::STATUS_ACTIVE], true)) {
                $this->fail('tenant_id', 'Tài khoản khách thuê không ở trạng thái hợp lệ để lập hợp đồng.');
            }
            $this->updateRepresentativeProfile($tenant, $data['representative'] ?? []);
            if ($numberOfPeople > (int) $room->max_people) {
                $this->fail('number_of_people', 'Số người không được vượt quá sức chứa của phòng.');
            }

            $setting = Setting::currentOrCreate();
            $contract = Contract::query()->forceCreate([
                'contract_code' => 'TMP-'.Str::uuid(),
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'representative_tenant_id' => $tenant->id,
                'representative_is_occupant' => $representativeIsOccupant,
                'monthly_rent' => $room->price,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'deposit_status' => Contract::DEPOSIT_PENDING,
                'number_of_people' => $numberOfPeople,
                'internet_enabled' => (bool) ($data['internet_enabled'] ?? false),
                'service_enabled' => (bool) ($data['service_enabled'] ?? false),
                'parking_quantity' => $data['parking_quantity'] ?? 0,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'rental_duration_option' => $data['contract_duration'] ?? null,
                'signature_due_at' => $data['signature_due_at'] ?? null,
                'deposit_due_at' => $data['deposit_due_at'] ?? null,
                'scheduled_move_in_date' => $data['scheduled_move_in_date'] ?? $data['start_date'],
                'reservation_expires_at' => $data['reservation_expires_at'] ?? null,
                'move_in_terms_confirmed_at' => ($data['move_in_terms_confirmed'] ?? false) ? now() : null,
                'move_in_terms_confirmed_by' => ($data['move_in_terms_confirmed'] ?? false) ? $actor->id : null,
                'status' => Contract::STATUS_DRAFT,
                'note' => $data['note'] ?? null,
                'landlord_name_snapshot' => $setting->landlord_name,
                'landlord_address_snapshot' => $setting->landlord_address,
                'landlord_phone_snapshot' => $setting->landlord_phone,
                'landlord_identity_snapshot' => $setting->landlord_identity_number,
                'property_address_snapshot' => $setting->property_address,
            ]);
            $contract->forceFill([
                'contract_code' => 'HD'.str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT),
            ])->save();
            $this->occupantService->syncAdminDraftOccupants($contract, $tenant, $representativeIsOccupant, $data['occupants'] ?? [], $actor);
            $this->history($contract, null, Contract::STATUS_DRAFT, 'create_draft', null, $actor, [
                'room_status_unchanged' => true,
                'move_in_terms_confirmed' => (bool) ($data['move_in_terms_confirmed'] ?? false),
                'move_in_window_ratio' => $data['move_in_window_ratio'] ?? null,
            ]);

            return $contract->fresh();
        }, 3);
    }

    public function submitForSignature(Contract $contract, User $actor, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_PENDING_SIGNATURE) {
                return $contract;
            }
            $this->requireStatus($contract, [Contract::STATUS_DRAFT], 'Chỉ bản nháp mới được gửi chờ ký.');
            $room = $this->lockRoom($contract);
            if ($room->status === Room::STATUS_MAINTENANCE) {
                $this->fail('room_id', 'Phòng đang bảo trì. Không thể gửi hợp đồng chờ ký.');
            }
            $this->ensureScheduleIsComplete($contract);
            $this->snapshotMoveInDetails($contract, $room);
            $this->transition($contract, Contract::STATUS_PENDING_SIGNATURE, 'submit_for_signature', $reason, $actor);

            return $contract->fresh();
        }, 3);
    }

    public function updateDraft(Contract $contract, User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $data): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_DRAFT], 'Chỉ bản nháp mới được sửa nội dung hợp đồng.');
            $this->ensureDraftDataIsValid($data);
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);
            $this->ensureRoomCanAcceptDraftSchedule($room, $data);
            $tenant = Tenant::query()->with('user')->lockForUpdate()->findOrFail($data['tenant_id']);
            $representativeIsOccupant = (bool) ($data['representative_is_occupant'] ?? false);
            $numberOfPeople = count($data['occupants'] ?? []) + (int) $representativeIsOccupant;
            if ($room->status === Room::STATUS_MAINTENANCE) {
                $this->fail('room_id', 'Phòng đang bảo trì, không thể chọn cho hợp đồng.');
            }
            if (! $tenant->user || ! in_array($tenant->user->status, [User::STATUS_PENDING, User::STATUS_ACTIVE], true)) {
                $this->fail('tenant_id', 'Tài khoản khách thuê không hợp lệ.');
            }
            $this->updateRepresentativeProfile($tenant, $data['representative'] ?? []);
            if ($numberOfPeople > (int) $room->max_people) {
                $this->fail('number_of_people', 'Số người không được vượt quá sức chứa của phòng.');
            }
            $contract->fill([
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'representative_tenant_id' => $tenant->id,
                'representative_is_occupant' => $representativeIsOccupant,
                'monthly_rent' => $room->price,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'number_of_people' => $numberOfPeople,
                'internet_enabled' => (bool) ($data['internet_enabled'] ?? false),
                'service_enabled' => (bool) ($data['service_enabled'] ?? false),
                'parking_quantity' => $data['parking_quantity'] ?? 0,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'rental_duration_option' => $data['contract_duration'] ?? null,
                'signature_due_at' => $data['signature_due_at'] ?? null,
                'deposit_due_at' => $data['deposit_due_at'] ?? null,
                'scheduled_move_in_date' => $data['scheduled_move_in_date'],
                'reservation_expires_at' => $data['reservation_expires_at'],
                'note' => $data['note'] ?? null,
            ])->forceFill([
                'move_in_terms_confirmed_at' => ($data['move_in_terms_confirmed'] ?? false) ? now() : null,
                'move_in_terms_confirmed_by' => ($data['move_in_terms_confirmed'] ?? false) ? $actor->id : null,
            ])->save();
            $this->occupantService->syncAdminDraftOccupants($contract, $tenant, $representativeIsOccupant, $data['occupants'] ?? [], $actor);
            $this->history($contract, $contract->status, $contract->status, 'update_draft', $data['edit_reason'] ?? null, $actor, [
                'move_in_terms_confirmed' => (bool) ($data['move_in_terms_confirmed'] ?? false),
                'move_in_window_ratio' => $data['move_in_window_ratio'] ?? null,
            ]);

            return $contract->fresh();
        }, 3);
    }

    public function returnToDraft(Contract $contract, User $actor, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_DRAFT) {
                return $contract;
            }
            $this->requireStatus($contract, [Contract::STATUS_PENDING_SIGNATURE], 'Chỉ hợp đồng đang chờ ký mới có thể trả lại bản nháp.');
            if ($contract->signed_at || $contract->invoices()->exists() || $contract->payments()->exists()) {
                $this->fail('contract', 'Hợp đồng đã ký hoặc đã phát sinh chứng từ, không thể trả lại bản nháp.');
            }
            $contract->handoverItems()->delete();
            $contract->forceFill([
                'move_in_inventory_snapshotted_at' => null,
                'move_in_details_confirmed_at' => null,
                'move_in_details_confirmed_by' => null,
            ])->save();
            $this->transition($contract, Contract::STATUS_DRAFT, 'return_to_draft', $reason, $actor);

            return $contract->fresh();
        }, 3);
    }

    public function markAsSigned(Contract $contract, User $actor, Carbon|string $signedAt, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $signedAt, $reason): Contract {
            $contract = $this->lockContract($contract);
            if (in_array($contract->status, [Contract::STATUS_PENDING_DEPOSIT, Contract::STATUS_AWAITING_MOVE_IN], true)
                && $contract->signed_at) {
                return $contract;
            }
            $this->requireStatus($contract, [Contract::STATUS_PENDING_SIGNATURE], 'Hợp đồng phải ở bước chờ ký trước khi xác nhận đã ký.');
            $signedAt = Carbon::parse($signedAt);
            if ($signedAt->isFuture()) {
                $this->fail('signed_at', 'Ngày ký không được ở tương lai.');
            }
            $room = $this->lockRoom($contract);
            if ($room->status === Room::STATUS_MAINTENANCE) {
                $this->fail('room_id', 'Phòng đang bảo trì. Không thể xác nhận ký và giữ lịch.');
            }
            $this->ensureScheduleIsComplete($contract);
            $this->ensureNoReservationConflict($contract);
            $depositDueAt = $contract->deposit_due_at;
            if ((float) $contract->deposit_amount > 0 && ! $depositDueAt) {
                $depositDueAt = $signedAt->copy()->addDays(
                    (int) Setting::currentOrCreate()->payment_due_days
                );
                if ($contract->reservation_expires_at && $depositDueAt->gt($contract->reservation_expires_at)) {
                    $depositDueAt = $contract->reservation_expires_at->copy();
                }
            }
            $contract->forceFill([
                'signed_at' => $signedAt,
                'signed_confirmed_by' => $actor->id,
                'deposit_due_at' => $depositDueAt,
            ])->save();

            $target = (float) $contract->deposit_amount > 0
                ? Contract::STATUS_PENDING_DEPOSIT
                : Contract::STATUS_AWAITING_MOVE_IN;
            if ($target === Contract::STATUS_AWAITING_MOVE_IN) {
                $contract->forceFill([
                    'deposit_status' => Contract::DEPOSIT_PAID,
                    'deposit_resolution' => Contract::DEPOSIT_NOT_REQUIRED,
                ])->save();
            }
            $this->transition($contract, $target, 'mark_as_signed', $reason, $actor, [
                'signed_at' => $signedAt->toIso8601String(),
                'deposit_due_at' => $depositDueAt?->toIso8601String(),
            ]);
            $this->resolveAlerts($contract, ['signature_overdue']);

            return $contract->fresh();
        }, 3);
    }

    public function issueDepositInvoice(Contract $contract, User $actor): Invoice
    {
        return DB::transaction(function () use ($contract): Invoice {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_PENDING_DEPOSIT], 'Chỉ phát hành hóa đơn cọc sau khi hợp đồng đã được xác nhận ký.');
            if ((float) $contract->deposit_amount <= 0) {
                $this->fail('deposit_amount', 'Hợp đồng không có khoản cọc phải thu.');
            }
            $existing = Invoice::query()->where('lifecycle_event_key', $this->invoiceKey($contract, 'deposit'))
                ->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $now = now();
            $setting = Setting::currentOrCreate();
            $dueAt = $contract->deposit_due_at ?: $now->copy()->addDays((int) $setting->payment_due_days);
            $invoice = Invoice::query()->forceCreate([
                'contract_id' => $contract->id,
                'room_id' => $contract->room_id,
                'invoice_type' => Invoice::TYPE_DEPOSIT,
                'invoice_code' => null,
                'lifecycle_event_key' => $this->invoiceKey($contract, 'deposit'),
                'month' => $now->month,
                'year' => $now->year,
                'invoice_date' => $now->toDateString(),
                'due_date' => Carbon::parse($dueAt)->toDateString(),
                'room_fee' => 0,
                'total_amount' => $contract->deposit_amount,
                'status' => Invoice::STATUS_UNPAID,
            ]);
            $invoice->forceFill(['invoice_code' => sprintf('DEP-%04d%02d-%06d', $now->year, $now->month, $invoice->id)])->save();
            $invoice->details()->create([
                'type' => Invoice::TYPE_DEPOSIT,
                'name' => 'Tiền cọc hợp đồng '.$contract->contract_code,
                'quantity' => 1,
                'unit' => 'lần',
                'unit_price' => $contract->deposit_amount,
                'amount' => $contract->deposit_amount,
                'note' => 'Khoản cọc giữ lịch và nhận phòng',
                'sort_order' => 1,
            ]);
            if (! $contract->deposit_due_at) {
                $contract->forceFill(['deposit_due_at' => Carbon::parse($dueAt)])->save();
            }

            return $invoice;
        }, 3);
    }

    public function syncDepositState(Contract $contract, ?User $actor = null, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason): Contract {
            $contract = $this->lockContract($contract);
            $invoices = Invoice::query()->where('contract_id', $contract->id)
                ->where('invoice_type', Invoice::TYPE_DEPOSIT)->lockForUpdate()->get();
            $paid = (float) DB::table('payments')->whereIn('invoice_id', $invoices->pluck('id'))
                ->where('status', 'success')->lockForUpdate()->sum('amount_paid');
            $required = (float) $contract->deposit_amount;
            $enough = $required <= 0 || round($paid, 2) >= round($required, 2);
            $contract->forceFill([
                'deposit_status' => $enough ? Contract::DEPOSIT_PAID : Contract::DEPOSIT_PENDING,
                'deposit_paid_at' => $enough && $required > 0 ? ($contract->deposit_paid_at ?: now()) : null,
            ])->save();

            if ($enough && $contract->status === Contract::STATUS_PENDING_DEPOSIT) {
                $this->ensureScheduleIsComplete($contract);
                $this->transition($contract, Contract::STATUS_AWAITING_MOVE_IN, 'deposit_completed', $reason, $actor, [
                    'paid' => $paid,
                    'required' => $required,
                ]);
                $this->resolveAlerts($contract, ['deposit_overdue']);
            } elseif (! $enough && $contract->status === Contract::STATUS_AWAITING_MOVE_IN) {
                $this->transition($contract, Contract::STATUS_PENDING_DEPOSIT, 'deposit_reversed', $reason ?: 'Khoản cọc không còn đủ sau đối soát.', $actor, [
                    'paid' => $paid,
                    'required' => $required,
                ]);
            } elseif (! $enough && in_array($contract->status, [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED, Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED], true)) {
                $this->alert($contract, 'deposit_exception', 'Ngoại lệ tiền cọc', 'Tiền cọc thành công thấp hơn mức yêu cầu sau khi khách đã nhận phòng.', [
                    'paid' => $paid,
                    'required' => $required,
                ]);
            }

            return $contract->fresh();
        }, 3);
    }

    public function confirmMoveInDetails(Contract $contract, User $actor): Contract
    {
        return DB::transaction(function () use ($contract, $actor): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
            ], 'Thông tin nhận phòng chỉ được xác nhận sau khi bản hợp đồng đã được gửi cho khách và trước khi check-in.');

            $tenantUserId = Tenant::query()->whereKey($contract->tenant_id)->value('user_id');
            if (! $tenantUserId || (int) $tenantUserId !== (int) $actor->id) {
                $this->fail('contract', 'Bạn không có quyền xác nhận thông tin nhận phòng của hợp đồng này.');
            }
            if (! $contract->move_in_inventory_snapshotted_at) {
                $this->fail('move_in_details', 'Phiếu tài sản nhận phòng chưa được lập. Vui lòng liên hệ ban quản lý.');
            }
            if ($contract->move_in_details_confirmed_at) {
                return $contract;
            }

            $contract->forceFill([
                'move_in_details_confirmed_at' => now(),
                'move_in_details_confirmed_by' => $actor->id,
            ])->save();
            $this->history(
                $contract,
                $contract->status,
                $contract->status,
                'confirm_move_in_details',
                null,
                $actor,
                [
                    'inventory_items' => $contract->handoverItems()->count(),
                    'internet_enabled' => $contract->internet_enabled,
                    'service_enabled' => $contract->service_enabled,
                    'parking_quantity' => $contract->parking_quantity,
                ],
            );

            return $contract->fresh();
        }, 3);
    }

    public function checkIn(Contract $contract, User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $data): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_ACTIVE && $contract->actual_move_in_at) {
                return $contract;
            }
            $this->requireStatus($contract, [Contract::STATUS_AWAITING_MOVE_IN], 'Chỉ hợp đồng đang chờ nhận phòng mới được check-in.');
            $room = $this->lockRoom($contract);
            $invoiceIds = Invoice::query()->where('contract_id', $contract->id)
                ->lockForUpdate()->pluck('id');
            if ($invoiceIds->isNotEmpty()) {
                DB::table('payments')->whereIn('invoice_id', $invoiceIds)->lockForUpdate()->get();
            }
            if (! $contract->signed_at) {
                $this->fail('contract', 'Hợp đồng chưa có ngày ký hợp lệ.');
            }
            if ((float) $contract->deposit_amount > 0 && $contract->deposit_remaining_amount > 0) {
                $this->fail('deposit', 'Tiền cọc chưa được thanh toán đủ.');
            }
            $moveInAt = Carbon::parse($data['actual_move_in_at']);
            if ($moveInAt->isFuture()) {
                $this->fail('actual_move_in_at', 'Thời điểm nhận phòng không được ở tương lai.');
            }
            if (! $contract->scheduled_move_in_date) {
                $this->fail('scheduled_move_in_date', 'Hợp đồng chưa có ngày dự kiến nhận phòng.');
            }
            if (! $moveInAt->isSameDay($contract->scheduled_move_in_date) && blank($data['schedule_variance_reason'] ?? null)) {
                $this->fail('schedule_variance_reason', 'Nhận phòng sớm hoặc muộn phải ghi rõ lý do.');
            }
            if (! ($data['handover_confirmed'] ?? false)) {
                $this->fail('handover_confirmed', 'Phải xác nhận biên bản bàn giao trước khi nhận phòng.');
            }
            if (! $contract->move_in_inventory_snapshotted_at || ! $contract->move_in_details_confirmed_at) {
                $this->fail('move_in_details_confirmed', 'Khách thuê phải xem và xác nhận dịch vụ, tài sản bàn giao trước khi check-in.');
            }
            if ($room->status === Room::STATUS_MAINTENANCE) {
                $this->fail('room_id', 'Phòng đang bảo trì, không thể nhận phòng.');
            }
            if ($room->status === Room::STATUS_OCCUPIED) {
                $this->fail('room_id', 'Phòng đang có người ở, không thể nhận phòng.');
            }
            $otherOccupant = Contract::query()->where('room_id', $room->id)->whereKeyNot($contract->id)
                ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)->lockForUpdate()->first();
            if ($otherOccupant) {
                $this->fail('room_id', 'Phòng còn khách cũ chưa trả. Hãy checkout hợp đồng '.$otherOccupant->contract_code.' trước.');
            }
            $this->ensureNoReservationConflict($contract);
            if ((int) $contract->number_of_people > (int) $room->max_people) {
                $this->fail('number_of_people', 'Số người vượt quá sức chứa của phòng.');
            }

            $lastReading = UtilityReading::query()->where('room_id', $room->id)
                ->orderByDesc('record_date')->orderByDesc('id')->lockForUpdate()->first();
            $electricity = (int) $data['handover_electricity'];
            $water = (int) $data['handover_water'];
            if ($lastReading && ($electricity < $lastReading->electricity_new || $water < $lastReading->water_new)) {
                $this->fail('handover_electricity', 'Chỉ số bàn giao không được nhỏ hơn chỉ số gần nhất của phòng.');
            }

            UtilityReading::query()->forceCreate([
                'room_id' => $room->id,
                'contract_id' => $contract->id,
                'month' => $moveInAt->month,
                'year' => $moveInAt->year,
                'record_date' => $moveInAt->toDateString(),
                'reading_type' => 'handover',
                'lifecycle_event_key' => $this->readingKey($contract, 'handover'),
                'electricity_old' => $electricity,
                'electricity_new' => $electricity,
                'water_old' => $water,
                'water_new' => $water,
                'status' => 'confirmed',
                'note' => 'Chỉ số bàn giao khi admin xác nhận nhận phòng.',
            ]);
            $occupantCount = $this->occupantService->checkInApproved($contract, $actor, $moveInAt);
            $contract->forceFill([
                'actual_move_in_at' => $moveInAt,
                'checked_in_by' => $actor->id,
                'number_of_people' => $occupantCount,
            ])->save();
            $room->forceFill([
                'status' => Room::STATUS_OCCUPIED,
                'current_people' => $occupantCount,
            ])->save();
            $this->transition($contract, Contract::STATUS_ACTIVE, 'check_in', $data['schedule_variance_reason'] ?? null, $actor, [
                'handover_confirmed' => true,
                'electricity' => $electricity,
                'water' => $water,
            ]);
            $this->resolveAlerts($contract, ['move_in_overdue']);
            app(TenantAccountLifecycle::class)->sync($contract->tenant()->with('user')->firstOrFail());

            return $contract->fresh();
        }, 3);
    }

    public function extendMoveInDeadline(Contract $contract, User $actor, Carbon|string $deadline, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $deadline, $reason): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_AWAITING_MOVE_IN], 'Chỉ hợp đồng đang chờ nhận phòng mới được gia hạn giữ chỗ.');
            $deadline = Carbon::parse($deadline);
            if ($deadline->lte(now()) || ($contract->reservation_expires_at && $deadline->lte($contract->reservation_expires_at))) {
                $this->fail('reservation_expires_at', 'Hạn giữ phòng mới phải ở tương lai và sau hạn hiện tại.');
            }
            $contract->forceFill(['reservation_expires_at' => $deadline])->save();
            $this->history($contract, $contract->status, $contract->status, 'extend_move_in_deadline', $reason, $actor, [
                'new_deadline' => $deadline->toIso8601String(),
            ]);
            $this->resolveAlerts($contract, ['move_in_overdue']);

            return $contract->fresh();
        }, 3);
    }

    public function cancel(Contract $contract, User $actor, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_CANCELLED) {
                return $contract;
            }
            $this->requireStatus($contract, [
                Contract::STATUS_DRAFT,
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
            ], 'Hợp đồng ở trạng thái này không thể hủy; hợp đồng đang ở phải thực hiện checkout.');
            if (blank($reason)) {
                $this->fail('cancel_reason', 'Lý do hủy hợp đồng là bắt buộc.');
            }
            $this->lockRoom($contract);
            $paid = (float) $contract->payments()->where('payments.status', 'success')->lockForUpdate()->sum('amount_paid');
            $contract->forceFill([
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
                'deposit_resolution' => $paid > 0 ? 'pending_resolution' : Contract::DEPOSIT_NOT_REQUIRED,
            ])->save();
            $this->occupantService->withdrawForCancellation($contract, $actor, $reason);
            $this->transition($contract, Contract::STATUS_CANCELLED, 'cancel', $reason, $actor, [
                'deposit_received' => $paid,
                'deposit_requires_resolution' => $paid > 0,
            ]);
            if ($paid > 0) {
                $this->alert($contract, 'cancelled_deposit_resolution', 'Cọc hợp đồng hủy cần xử lý', 'Hợp đồng đã hủy nhưng có tiền cọc đã thu; cần hoàn, giữ hoặc khấu trừ có chứng từ.', ['paid' => $paid]);
            }
            $this->resolveAlerts($contract, ['signature_overdue', 'deposit_overdue', 'move_in_overdue']);
            app(TenantAccountLifecycle::class)->sync($contract->tenant()->with('user')->firstOrFail());

            return $contract->fresh();
        }, 3);
    }

    public function markExpiredContracts(?User $actor = null): int
    {
        $ids = Contract::query()->where('status', Contract::STATUS_ACTIVE)
            ->whereDate('end_date', '<', today())->pluck('id');
        $changed = 0;
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $actor, &$changed): void {
                $contract = Contract::query()->lockForUpdate()->find($id);
                if (! $contract || $contract->status !== Contract::STATUS_ACTIVE || ! $contract->isOverExpired()) {
                    return;
                }
                $this->lockRoom($contract);
                $this->transition($contract, Contract::STATUS_EXPIRED, 'mark_expired', 'Đã qua ngày kết thúc nhưng khách chưa trả phòng.', $actor);
                $this->alert($contract, 'contract_expired', 'Hợp đồng quá hạn trả phòng', 'Khách vẫn đang ở sau ngày kết thúc; phòng không được tự giải phóng.');
                $changed++;
            }, 3);
        }

        return $changed;
    }

    public function checkOut(Contract $contract, User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $data): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_SETTLING && $contract->actual_move_out_at) {
                return $contract;
            }
            $this->requireStatus($contract, Contract::OPEN_OCCUPANCY_STATUSES, 'Chỉ hợp đồng active hoặc expired mới được checkout.');
            $room = $this->lockRoom($contract);
            $moveOutAt = Carbon::parse($data['actual_move_out_at']);
            if ($moveOutAt->isFuture()) {
                $this->fail('actual_move_out_at', 'Thời điểm trả phòng không được ở tương lai.');
            }
            if ($contract->actual_move_in_at && $moveOutAt->lt($contract->actual_move_in_at)) {
                $this->fail('actual_move_out_at', 'Thời điểm trả phòng không được trước thời điểm nhận phòng.');
            }
            if (blank($data['checkout_reason'] ?? null)) {
                $this->fail('checkout_reason', 'Phải nhập lý do trả phòng/kết thúc.');
            }
            $lastReading = UtilityReading::query()->where('room_id', $contract->room_id)
                ->where('contract_id', $contract->id)->orderByDesc('record_date')->orderByDesc('id')->lockForUpdate()->first();
            if (! $lastReading) {
                $this->fail('checkout_electricity', 'Hợp đồng chưa có chỉ số bàn giao để đối chiếu checkout.');
            }
            $electricity = (int) $data['checkout_electricity'];
            $water = (int) $data['checkout_water'];
            if ($electricity < $lastReading->electricity_new || $water < $lastReading->water_new) {
                $this->fail('checkout_electricity', 'Chỉ số checkout không được nhỏ hơn chỉ số gần nhất của hợp đồng.');
            }
            UtilityReading::query()->forceCreate([
                'room_id' => $room->id,
                'contract_id' => $contract->id,
                'month' => $moveOutAt->month,
                'year' => $moveOutAt->year,
                'record_date' => $moveOutAt->toDateString(),
                'reading_type' => 'checkout',
                'lifecycle_event_key' => $this->readingKey($contract, 'checkout'),
                'electricity_old' => $lastReading->electricity_new,
                'electricity_new' => $electricity,
                'water_old' => $lastReading->water_new,
                'water_new' => $water,
                'status' => 'confirmed',
                'note' => 'Chỉ số cuối khi admin xác nhận trả phòng.',
            ]);
            $contract->forceFill([
                'actual_move_out_at' => $moveOutAt,
                'actual_end_date' => $moveOutAt->toDateString(),
                'terminated_at' => $moveOutAt,
                'checked_out_by' => $actor->id,
                'checkout_reason' => $data['checkout_reason'],
                'termination_note' => $data['checkout_reason'],
            ])->save();
            $this->occupantService->moveOutAll($contract, $actor, $moveOutAt, $data['checkout_reason']);
            $room->forceFill([
                'status' => $room->status === Room::STATUS_MAINTENANCE ? Room::STATUS_MAINTENANCE : Room::STATUS_AVAILABLE,
                'current_people' => 0,
            ])->save();

            $settlementAmount = (float) ($data['settlement_amount'] ?? 0);
            if ($settlementAmount > 0) {
                $this->createSettlementInvoice($contract, $settlementAmount, $data['settlement_description'] ?? 'Chi phí quyết toán/hư hỏng');
            }
            $this->transition($contract, Contract::STATUS_SETTLING, 'check_out', $data['checkout_reason'], $actor, [
                'electricity' => $electricity,
                'water' => $water,
                'settlement_amount' => $settlementAmount,
            ]);
            $this->resolveAlerts($contract, ['contract_expired']);
            app(TenantAccountLifecycle::class)->sync($contract->tenant()->with('user')->firstOrFail());

            return $contract->fresh();
        }, 3);
    }

    public function completeSettlement(Contract $contract, User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $data): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_COMPLETED) {
                return $contract;
            }
            $this->requireStatus($contract, [Contract::STATUS_SETTLING], 'Chỉ hợp đồng đang quyết toán mới được hoàn tất.');
            if (! $contract->actual_move_out_at) {
                $this->fail('contract', 'Hợp đồng chưa checkout nên không thể hoàn tất quyết toán.');
            }
            if (! ($data['confirm_complete'] ?? false)) {
                $this->fail('confirm_complete', 'Phải xác nhận đã kiểm tra toàn bộ quyết toán trước khi hoàn tất hợp đồng.');
            }
            $openInvoices = Invoice::query()->where('contract_id', $contract->id)
                ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])->lockForUpdate()->get();
            if ($openInvoices->isNotEmpty()) {
                if (! ($data['write_off_outstanding'] ?? false)) {
                    $this->fail('invoices', 'Hợp đồng vẫn còn hóa đơn chưa thanh toán.');
                }
                if (blank($data['write_off_reason'] ?? null)) {
                    $this->fail('write_off_reason', 'Xóa nợ bắt buộc có lý do phê duyệt.');
                }
                foreach ($openInvoices as $invoice) {
                    $invoice->forceFill([
                        'status' => Invoice::STATUS_WRITTEN_OFF,
                        'written_off_at' => now(),
                        'written_off_by' => $actor->id,
                        'write_off_reason' => $data['write_off_reason'],
                    ])->save();
                }
            }

            $resolution = (float) $contract->deposit_amount <= 0
                ? Contract::DEPOSIT_NOT_REQUIRED
                : ($data['deposit_resolution'] ?? null);
            if ((float) $contract->deposit_amount > 0
                && ! in_array($resolution, [Contract::DEPOSIT_REFUNDED, Contract::DEPOSIT_DEDUCTED, Contract::DEPOSIT_RETAINED], true)) {
                $this->fail('deposit_resolution', 'Phải ghi rõ tiền cọc đã hoàn, khấu trừ hay giữ lại.');
            }
            if (in_array($resolution, [Contract::DEPOSIT_DEDUCTED, Contract::DEPOSIT_RETAINED], true)
                && blank($data['settlement_note'] ?? null)) {
                $this->fail('settlement_note', 'Khấu trừ hoặc giữ cọc bắt buộc có lý do/chứng từ.');
            }

            $contract->forceFill([
                'deposit_resolution' => $resolution,
                'deposit_status' => $resolution,
                'deposit_resolved_at' => now(),
                'deposit_resolved_by' => $actor->id,
                'settlement_note' => $data['settlement_note'] ?? null,
                'completed_at' => now(),
                'completed_by' => $actor->id,
            ])->save();
            $this->transition($contract, Contract::STATUS_COMPLETED, 'complete_settlement', $data['settlement_note'] ?? null, $actor, [
                'deposit_resolution' => $resolution,
                'written_off_invoice_ids' => $openInvoices->pluck('id')->all(),
            ]);
            app(TenantAccountLifecycle::class)->sync($contract->tenant()->with('user')->firstOrFail());

            return $contract->fresh();
        }, 3);
    }

    public function extendContract(Contract $contract, User $actor, Carbon|string $newEndDate, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $newEndDate, $reason): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, Contract::OPEN_OCCUPANCY_STATUSES, 'Chỉ hợp đồng active hoặc expired mới được gia hạn.');
            $newEndDate = Carbon::parse($newEndDate)->startOfDay();
            if (! $newEndDate->gt($contract->end_date->startOfDay())) {
                $this->fail('new_end_date', 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.');
            }
            $this->lockRoom($contract);
            $oldEndDate = $contract->end_date->copy();
            $contract->forceFill(['end_date' => $newEndDate])->save();
            try {
                $this->ensureNoReservationConflict($contract);
            } catch (\Throwable $exception) {
                $contract->forceFill(['end_date' => $oldEndDate])->save();
                throw $exception;
            }
            $contract->forceFill([
                'extended_at' => now(),
                'extend_start_date' => $oldEndDate->copy()->addDay(),
                'extend_end_date' => $newEndDate,
                'extend_reason' => 'lifecycle_extension',
                'extend_note' => $reason,
            ])->save();
            $target = $newEndDate->gte(today()) ? Contract::STATUS_ACTIVE : Contract::STATUS_EXPIRED;
            $this->transition($contract, $target, 'extend_contract', $reason, $actor, [
                'old_end_date' => $oldEndDate->toDateString(),
                'new_end_date' => $newEndDate->toDateString(),
            ]);
            if ($target === Contract::STATUS_ACTIVE) {
                $this->resolveAlerts($contract, ['contract_expired']);
            }

            return $contract->fresh();
        }, 3);
    }

    public function processDailyAlerts(): array
    {
        $expired = $this->markExpiredContracts();
        $created = 0;
        $definitions = [
            [Contract::STATUS_PENDING_SIGNATURE, 'signature_due_at', 'signature_overdue', 'Quá hạn ký hợp đồng', 'Hợp đồng đã quá hạn ký và cần admin xử lý.'],
            [Contract::STATUS_PENDING_DEPOSIT, 'deposit_due_at', 'deposit_overdue', 'Quá hạn đóng cọc', 'Hợp đồng chưa đủ cọc sau hạn thanh toán.'],
            [Contract::STATUS_AWAITING_MOVE_IN, 'reservation_expires_at', 'move_in_overdue', 'Quá hạn nhận phòng', 'Đã quá hạn giữ phòng; không tự hủy hoặc xử lý tiền cọc.'],
        ];
        foreach ($definitions as [$status, $column, $type, $title, $message]) {
            Contract::query()->where('status', $status)->whereNotNull($column)->where($column, '<', now())
                ->orderBy('id')->each(function (Contract $contract) use ($type, $title, $message, &$created): void {
                    if ($this->alert($contract, $type, $title, $message)) {
                        $created++;
                    }
                });
        }
        Contract::query()->where('status', Contract::STATUS_EXPIRED)->orderBy('id')
            ->each(function (Contract $contract) use (&$created): void {
                if ($this->alert(
                    $contract,
                    'contract_expired',
                    'Hợp đồng quá hạn trả phòng',
                    'Khách vẫn đang ở sau ngày kết thúc; cần gia hạn hoặc thực hiện checkout.'
                )) {
                    $created++;
                }
            });

        return ['expired' => $expired, 'alerts_created' => $created];
    }

    private function createSettlementInvoice(Contract $contract, float $amount, string $description): Invoice
    {
        $existing = Invoice::query()->where('lifecycle_event_key', $this->invoiceKey($contract, 'settlement'))
            ->lockForUpdate()->first();
        if ($existing) {
            return $existing;
        }
        $date = Carbon::parse($contract->actual_move_out_at ?: now());
        $invoice = Invoice::query()->forceCreate([
            'contract_id' => $contract->id,
            'room_id' => $contract->room_id,
            'invoice_type' => Invoice::TYPE_SETTLEMENT,
            'lifecycle_event_key' => $this->invoiceKey($contract, 'settlement'),
            'invoice_code' => null,
            'month' => $date->month,
            'year' => $date->year,
            'invoice_date' => $date->toDateString(),
            'due_date' => $date->copy()->addDays((int) Setting::currentOrCreate()->payment_due_days)->toDateString(),
            'room_fee' => 0,
            'total_amount' => $amount,
            'status' => Invoice::STATUS_UNPAID,
        ]);
        $invoice->forceFill(['invoice_code' => sprintf('SET-%04d%02d-%06d', $date->year, $date->month, $invoice->id)])->save();
        $invoice->details()->create([
            'type' => Invoice::TYPE_SETTLEMENT,
            'name' => $description,
            'quantity' => 1,
            'unit' => 'lần',
            'unit_price' => $amount,
            'amount' => $amount,
            'note' => 'Khoản quyết toán được ghi nhận khi checkout.',
            'sort_order' => 1,
        ]);

        return $invoice;
    }

    private function ensureScheduleIsComplete(Contract $contract): void
    {
        if (! $contract->scheduled_move_in_date) {
            $this->fail('scheduled_move_in_date', 'Phải có ngày dự kiến nhận phòng.');
        }
        if (! $contract->reservation_expires_at) {
            $this->fail('reservation_expires_at', 'Phải có hạn giữ phòng.');
        }
        if ($contract->scheduled_move_in_date->lt($contract->start_date->startOfDay())
            || $contract->scheduled_move_in_date->gt($contract->end_date->endOfDay())) {
            $this->fail('scheduled_move_in_date', 'Ngày dự kiến nhận phòng phải nằm trong thời hạn hợp đồng.');
        }
        if ($contract->reservation_expires_at->lt($contract->scheduled_move_in_date->startOfDay())) {
            $this->fail('reservation_expires_at', 'Hạn giữ phòng không được trước ngày dự kiến nhận phòng.');
        }
    }

    private function ensureDraftDataIsValid(array $data): void
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $scheduled = Carbon::parse($data['scheduled_move_in_date'] ?? $data['start_date'])->startOfDay();
        $deadline = isset($data['reservation_expires_at'])
            ? Carbon::parse($data['reservation_expires_at'])
            : null;

        if (! $end->gt($start)) {
            $this->fail('end_date', 'Ngày kết thúc phải sau ngày bắt đầu.');
        }
        if ($scheduled->lt($start) || $scheduled->gt($end)) {
            $this->fail('scheduled_move_in_date', 'Ngày dự kiến nhận phòng phải nằm trong thời hạn hợp đồng.');
        }
        if (! $deadline || $deadline->lt($scheduled)) {
            $this->fail('reservation_expires_at', 'Hạn giữ phòng phải có và không được trước ngày dự kiến nhận phòng.');
        }
        if ($deadline->gt($end->endOfDay())) {
            $this->fail('reservation_expires_at', 'Hạn cuối nhận phòng không được sau ngày kết thúc hợp đồng.');
        }
    }

    private function ensureRoomCanAcceptDraftSchedule(Room $room, array $data): void
    {
        $currentOccupancy = Contract::query()
            ->where('room_id', $room->id)
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->lockForUpdate()
            ->latest('end_date')
            ->first();

        if (! $currentOccupancy) {
            if ($room->status === Room::STATUS_OCCUPIED) {
                $this->fail('room_id', 'Phòng đang có khách nhưng chưa xác định được ngày trống. Hãy kiểm tra và checkout hợp đồng hiện tại trước.');
            }

            return;
        }

        if ($currentOccupancy->status === Contract::STATUS_EXPIRED
            || $currentOccupancy->end_date->copy()->endOfDay()->isPast()) {
            $this->fail('start_date', 'Khách hiện tại đã quá hạn hợp đồng nhưng chưa checkout. Chưa thể xếp lịch thuê mới cho phòng này.');
        }

        $availableFrom = $currentOccupancy->end_date->copy()->addDay()->startOfDay();
        if (Carbon::parse($data['start_date'])->startOfDay()->lt($availableFrom)) {
            $this->fail(
                'start_date',
                'Phòng còn khách đến hết '.$currentOccupancy->end_date->format('d/m/Y').'. Ngày bắt đầu sớm nhất là '.$availableFrom->format('d/m/Y').'.'
            );
        }
    }

    private function ensureNoReservationConflict(Contract $contract): void
    {
        $conflict = Contract::query()->where('room_id', $contract->room_id)->whereKeyNot($contract->id)
            ->whereIn('status', Contract::RESERVING_STATUSES)
            ->where(function (Builder $query) use ($contract): void {
                $query->where('status', Contract::STATUS_EXPIRED)
                    ->orWhere(function (Builder $overlap) use ($contract): void {
                        $overlap->whereDate('start_date', '<=', $contract->end_date)
                            ->whereDate('end_date', '>=', $contract->start_date);
                    });
            })->lockForUpdate()->first();
        if ($conflict) {
            $this->fail('room_id', 'Khoảng thuê bị trùng với hợp đồng '.$conflict->contract_code.'.');
        }

        if ($contract->representative_is_occupant) {
            $tenantConflict = Contract::query()->whereKeyNot($contract->id)
                ->where('tenant_id', $contract->tenant_id)
                ->where('representative_is_occupant', true)
                ->whereIn('status', Contract::RESERVING_STATUSES)
                ->whereDate('start_date', '<=', $contract->end_date)->whereDate('end_date', '>=', $contract->start_date)
                ->lockForUpdate()->first();
            if ($tenantConflict) {
                $this->fail('tenant_id', 'Người đại diện có đăng ký cư trú trong hợp đồng trùng thời gian: '.$tenantConflict->contract_code.'.');
            }
        }

        $identityNumbers = $contract->occupants()->current()->whereNotNull('identity_number')->pluck('identity_number');
        if ($identityNumbers->isNotEmpty()) {
            $occupantConflict = ContractOccupant::query()
                ->where('contract_id', '!=', $contract->id)
                ->current()
                ->whereIn('identity_number', $identityNumbers)
                ->whereHas('contract', fn (Builder $query) => $query
                    ->whereIn('status', Contract::RESERVING_STATUSES)
                    ->whereDate('start_date', '<=', $contract->end_date)
                    ->whereDate('end_date', '>=', $contract->start_date))
                ->lockForUpdate()
                ->first();
            if ($occupantConflict) {
                $this->fail('occupants', 'Có người ở đã thuộc hợp đồng trùng thời gian: '.$occupantConflict->contract->contract_code.'.');
            }
        }
    }

    private function lockContract(Contract $contract): Contract
    {
        return Contract::query()->lockForUpdate()->findOrFail($contract->id);
    }

    private function lockRoom(Contract $contract): Room
    {
        return Room::query()->lockForUpdate()->findOrFail($contract->room_id);
    }

    private function requireStatus(Contract $contract, array $allowed, string $message): void
    {
        if (! in_array($contract->status, $allowed, true)) {
            $this->fail('contract', $message.' Trạng thái hiện tại: '.$contract->status_label.'.');
        }
    }

    private function transition(Contract $contract, string $to, string $action, ?string $reason, ?User $actor, array $metadata = []): void
    {
        $from = $contract->status;
        $contract->forceFill(['status' => $to])->save();
        $this->history($contract, $from, $to, $action, $reason, $actor, $metadata);
    }

    private function snapshotMoveInDetails(Contract $contract, Room $room): void
    {
        $items = $room->amenities()->orderBy('amenities.name')->get();
        $contract->handoverItems()->delete();

        foreach ($items as $item) {
            $contract->handoverItems()->create([
                'amenity_id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'is_quantifiable' => $item->is_quantifiable,
                'quantity' => $item->pivot->quantity,
                'condition' => $item->pivot->condition,
                'note' => $item->pivot->note,
            ]);
        }

        $contract->forceFill([
            'move_in_inventory_snapshotted_at' => now(),
            'move_in_details_confirmed_at' => null,
            'move_in_details_confirmed_by' => null,
        ])->save();
    }

    private function history(Contract $contract, ?string $from, string $to, string $action, ?string $reason, ?User $actor, array $metadata = []): void
    {
        ContractStatusHistory::query()->create([
            'contract_id' => $contract->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'reason' => $reason,
            'performed_by' => $actor?->id,
            'performed_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    private function alert(Contract $contract, string $type, string $title, string $message, array $metadata = []): bool
    {
        $dedupeKey = $type.':'.($metadata['period'] ?? 'current');
        $alert = ContractLifecycleAlert::query()->firstOrCreate([
            'contract_id' => $contract->id,
            'type' => $type,
            'dedupe_key' => $dedupeKey,
        ], [
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata ?: null,
            'detected_at' => now(),
        ]);

        return $alert->wasRecentlyCreated;
    }

    private function resolveAlerts(Contract $contract, array $types): void
    {
        ContractLifecycleAlert::query()->where('contract_id', $contract->id)
            ->whereIn('type', $types)->whereNull('resolved_at')->update(['resolved_at' => now()]);
    }

    private function invoiceKey(Contract $contract, string $type): string
    {
        return "contract:{$contract->id}:{$type}";
    }

    private function readingKey(Contract $contract, string $type): string
    {
        return "contract:{$contract->id}:{$type}";
    }

    private function updateRepresentativeProfile(Tenant $tenant, array $profile): void
    {
        if ($profile === []) {
            return;
        }

        $fields = [
            'full_name', 'date_of_birth', 'gender', 'cccd', 'phone', 'address',
        ];
        $profile = collect($profile)->only($fields)
            ->map(fn ($value) => is_string($value) && trim($value) === '' ? null : $value)
            ->all();
        $tenant->forceFill($profile)->save();
        $tenant->user?->forceFill([
            'name' => $tenant->full_name,
            'phone' => $tenant->phone,
        ])->save();
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

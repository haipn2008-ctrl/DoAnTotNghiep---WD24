<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractStatusHistory;
use App\Models\ContractTenant;
use App\Models\ContractTerminationRequest;
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
    public function __construct(
        private readonly ContractTenantService $memberService,
        private readonly SettlementService $settlements,
        private readonly ContractDocumentService $documents,
        private readonly ClientNotificationService $clientNotifications,
    ) {}

    public function createDraft(array $data, User $actor): Contract
    {
        return DB::transaction(function () use ($data, $actor): Contract {
            $this->ensureDraftDataIsValid($data);
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);
            $this->ensureRoomCanAcceptDraftSchedule($room, $data);
            $tenant = Tenant::query()->with('user')->lockForUpdate()->findOrFail($data['tenant_id']);
            $numberOfPeople = count($data['members'] ?? []) + 1;

            if (in_array($room->status, [Room::STATUS_MAINTENANCE, Room::STATUS_RETIRED], true)) {
                $this->fail('room_id', 'Phòng đang bảo trì hoặc đã ngừng khai thác, chưa thể lập hợp đồng.');
            }
            if (! $tenant->hasCompleteRentalProfile()) {
                $this->fail('tenant_id', 'Khách thuê phải kích hoạt tài khoản và hoàn thiện hồ sơ trước khi lập hợp đồng.');
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
                'monthly_rent' => $room->price,
                'deposit_amount' => $room->price,
                'deposit_status' => Contract::DEPOSIT_PENDING,
                'number_of_people' => $numberOfPeople,
                'internet_enabled' => true,
                'service_enabled' => true,
                'parking_vehicle_type' => null,
                'parking_quantity' => 0,
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
            $this->memberService->syncAdminDraftMembers($contract, $tenant, $data['members'] ?? [], $actor);
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
            if (in_array($room->status, [Room::STATUS_MAINTENANCE, Room::STATUS_RETIRED], true)) {
                $this->fail('room_id', 'Phòng đang bảo trì hoặc đã ngừng khai thác. Không thể gửi hợp đồng chờ ký.');
            }
            $this->ensureScheduleIsComplete($contract);
            $this->ensureNoReservationConflict($contract);
            $this->snapshotMoveInDetails($contract, $room);
            $this->documents->assignActiveTemplate($contract);
            $contract->forceFill([
                'signature_due_at' => now()->addDays(Contract::SIGNATURE_DEADLINE_DAYS),
            ])->save();
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
            $numberOfPeople = count($data['members'] ?? []) + 1;
            if (in_array($room->status, [Room::STATUS_MAINTENANCE, Room::STATUS_RETIRED], true)) {
                $this->fail('room_id', 'Phòng đang bảo trì hoặc đã ngừng khai thác, không thể chọn cho hợp đồng.');
            }
            if (! $tenant->hasCompleteRentalProfile()) {
                $this->fail('tenant_id', 'Khách thuê phải kích hoạt tài khoản và hoàn thiện hồ sơ trước khi cập nhật hợp đồng.');
            }
            $this->updateRepresentativeProfile($tenant, $data['representative'] ?? []);
            if ($numberOfPeople > (int) $room->max_people) {
                $this->fail('number_of_people', 'Số người không được vượt quá sức chứa của phòng.');
            }
            $contract->fill([
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'representative_tenant_id' => $tenant->id,
                'monthly_rent' => $room->price,
                'deposit_amount' => $room->price,
                'number_of_people' => $numberOfPeople,
                'internet_enabled' => true,
                'service_enabled' => true,
                'parking_vehicle_type' => null,
                'parking_quantity' => 0,
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
            $this->memberService->syncAdminDraftMembers($contract, $tenant, $data['members'] ?? [], $actor);
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
                'signature_due_at' => null,
                'move_in_inventory_snapshotted_at' => null,
                'move_in_details_confirmed_at' => null,
                'move_in_details_confirmed_by' => null,
                'contract_template_id' => null,
                'contract_content' => null,
                'contract_content_snapshotted_at' => null,
                'contract_content_sha256' => null,
            ])->save();
            $this->transition($contract, Contract::STATUS_DRAFT, 'return_to_draft', $reason, $actor);
            $this->resolveAlerts($contract, ['signature_overdue']);

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
            if (in_array($room->status, [Room::STATUS_MAINTENANCE, Room::STATUS_RETIRED], true)) {
                $this->fail('room_id', 'Phòng đang bảo trì hoặc đã ngừng khai thác. Không thể xác nhận ký và giữ lịch.');
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
            $this->documents->snapshotSignedDocument($contract);

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
            if ($target === Contract::STATUS_AWAITING_MOVE_IN) {
                $this->notifyIncompleteMoveInProfiles($contract);
            }

            return $contract->fresh();
        }, 3);
    }

    public function issueDepositInvoice(Contract $contract, User $actor): Invoice
    {
        return DB::transaction(function () use ($contract): Invoice {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_PENDING_DEPOSIT], 'Chỉ phát hành hóa đơn tiền cọc sau khi hợp đồng đã được xác nhận ký.');
            if ((float) $contract->deposit_amount <= 0) {
                $this->fail('deposit_amount', 'Hợp đồng không có tiền cọc phải thu.');
            }

            $now = now();
            $setting = Setting::currentOrCreate();
            $dueAt = $contract->deposit_due_at ?: $now->copy()->addDays((int) $setting->payment_due_days);

            $depositInvoice = Invoice::query()->where('contract_id', $contract->id)
                ->where('invoice_type', Invoice::TYPE_DEPOSIT)->lockForUpdate()->first();
            if (! $depositInvoice) {
                $depositInvoice = Invoice::query()->forceCreate([
                    'contract_id' => $contract->id, 'room_id' => $contract->room_id,
                    'invoice_type' => Invoice::TYPE_DEPOSIT, 'invoice_code' => null,
                    'lifecycle_event_key' => $this->invoiceKey($contract, 'deposit'),
                    'month' => $contract->start_date->month, 'year' => $contract->start_date->year,
                    'invoice_date' => $now->toDateString(), 'due_date' => Carbon::parse($dueAt)->toDateString(),
                    'room_fee' => 0, 'total_amount' => $contract->deposit_amount,
                    'status' => Invoice::STATUS_UNPAID,
                ]);
                $depositInvoice->forceFill(['invoice_code' => sprintf('DEP-%04d%02d-%06d', $contract->start_date->year, $contract->start_date->month, $depositInvoice->id)])->save();
                $depositInvoice->details()->create([
                    'type' => Invoice::TYPE_DEPOSIT, 'name' => 'Tiền cọc hợp đồng '.$contract->contract_code,
                    'quantity' => 1, 'unit' => 'lần', 'unit_price' => $contract->deposit_amount,
                    'amount' => $contract->deposit_amount, 'note' => 'Khoản cọc được giữ để quyết toán khi kết thúc hợp đồng', 'sort_order' => 1,
                ]);
            }

            if (! $contract->deposit_due_at) {
                $contract->forceFill(['deposit_due_at' => Carbon::parse($dueAt)])->save();
            }

            return $depositInvoice;
        }, 3);
    }

    public function syncDepositState(Contract $contract, ?User $actor = null, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason): Contract {
            $contract = $this->lockContract($contract);
            $invoices = Invoice::query()->where('contract_id', $contract->id)
                ->where('invoice_type', Invoice::TYPE_DEPOSIT)
                ->lockForUpdate()->get()->keyBy('invoice_type');
            $paidFor = fn (string $type): float => ($invoice = $invoices->get($type))
                ? (float) DB::table('payments')->where('invoice_id', $invoice->id)
                    ->where('status', 'success')->lockForUpdate()->sum('amount_paid')
                : 0.0;
            $depositPaid = $paidFor(Invoice::TYPE_DEPOSIT);
            $depositRequired = (float) $contract->deposit_amount;
            $depositEnough = $depositRequired <= 0 || round($depositPaid, 2) >= round($depositRequired, 2);
            $contract->forceFill([
                'deposit_status' => $depositEnough ? Contract::DEPOSIT_PAID : Contract::DEPOSIT_PENDING,
                'deposit_paid_at' => $depositEnough && $depositRequired > 0 ? ($contract->deposit_paid_at ?: now()) : null,
                'deposit_resolution' => null,
            ])->save();

            if ($depositEnough && $contract->status === Contract::STATUS_PENDING_DEPOSIT) {
                $this->ensureScheduleIsComplete($contract);
                $this->transition($contract, Contract::STATUS_AWAITING_MOVE_IN, 'deposit_completed', $reason, $actor, [
                    'deposit_paid' => $depositPaid, 'deposit_required' => $depositRequired,
                ]);
                $this->resolveAlerts($contract, ['deposit_overdue']);
                $this->notifyIncompleteMoveInProfiles($contract);
            } elseif (! $depositEnough && $contract->status === Contract::STATUS_AWAITING_MOVE_IN) {
                $this->transition($contract, Contract::STATUS_PENDING_DEPOSIT, 'deposit_reversed', $reason ?: 'Tiền cọc không còn đủ sau đối soát.', $actor, [
                    'deposit_paid' => $depositPaid,
                ]);
            } elseif (! $depositEnough && in_array($contract->status, [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED, Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED], true)) {
                $this->alert($contract, 'deposit_exception', 'Ngoại lệ tiền cọc', 'Tiền cọc thấp hơn mức yêu cầu sau khi khách đã nhận phòng.', [
                    'deposit_paid' => $depositPaid,
                ]);
            }

            return $contract->fresh();
        }, 3);
    }

    public function confirmMoveInDetails(Contract $contract, User $actor): Contract
    {
        return DB::transaction(function () use ($contract, $actor): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_AWAITING_MOVE_IN], 'Chỉ xác nhận thông tin nhận phòng sau khi hợp đồng đã ký và tiền cọc đã được xử lý đầy đủ.');

            $tenantUserId = Tenant::query()->whereKey($contract->tenant_id)->value('user_id');
            if (! $tenantUserId || (int) $tenantUserId !== (int) $actor->id) {
                $this->fail('contract', 'Bạn không có quyền xác nhận thông tin nhận phòng của hợp đồng này.');
            }
            $this->memberService->ensureReadyForMoveIn($contract);
            if (! $contract->move_in_inventory_snapshotted_at) {
                $this->fail('move_in_details', 'Phiếu tài sản nhận phòng chưa được lập. Vui lòng liên hệ ban quản lý.');
            }
            if ($contract->move_in_details_confirmed_at) {
                return $contract;
            }
            $handoverReading = UtilityReading::query()
                ->where('lifecycle_event_key', $this->readingKey($contract, 'handover'))
                ->lockForUpdate()
                ->first();
            if (! $handoverReading || ! $handoverReading->isDraft()) {
                $this->fail('handover_reading', 'Ban quản lý chưa lập chỉ số điện nước bàn giao để bạn kiểm tra.');
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
                    'service_enabled' => true,
                    'handover_reading_id' => $handoverReading->id,
                    'electricity' => $handoverReading->electricity_new,
                    'water' => $handoverReading->water_new,
                ],
            );

            return $contract->fresh();
        }, 3);
    }

    public function saveHandoverDraft(
        Contract $contract,
        User $actor,
        int $electricity,
        int $water,
        ?string $electricityImage = null,
        ?string $waterImage = null,
    ): UtilityReading {
        return DB::transaction(function () use ($contract, $actor, $electricity, $water, $electricityImage, $waterImage): UtilityReading {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_AWAITING_MOVE_IN], 'Chỉ được lập chỉ số bàn giao khi hợp đồng đang chờ nhận phòng.');
            if ($contract->move_in_details_confirmed_at) {
                $this->fail('handover_reading', 'Khách đã xác nhận thông tin nhận phòng. Hãy mở lại xác nhận và nêu lý do trước khi sửa chỉ số.');
            }

            $room = $this->lockRoom($contract);
            $key = $this->readingKey($contract, 'handover');
            $reading = UtilityReading::query()->where('lifecycle_event_key', $key)->lockForUpdate()->first();
            if ($reading && ! $reading->isDraft()) {
                $this->fail('handover_reading', 'Chỉ số bàn giao đã được chốt và không thể sửa.');
            }
            $lastReading = UtilityReading::query()
                ->where('room_id', $room->id)
                ->where(function ($query) use ($key): void {
                    $query->whereNull('lifecycle_event_key')->orWhere('lifecycle_event_key', '!=', $key);
                })
                ->orderByDesc('record_date')->orderByDesc('id')->lockForUpdate()->first();
            if ($lastReading && ($electricity < $lastReading->electricity_new || $water < $lastReading->water_new)) {
                $this->fail('handover_electricity', 'Chỉ số bàn giao không được nhỏ hơn chỉ số gần nhất của phòng.');
            }

            $isNew = ! $reading;
            $reading ??= new UtilityReading;
            $recordDate = $contract->scheduled_move_in_date ?: today();
            $reading->forceFill([
                'room_id' => $room->id,
                'contract_id' => $contract->id,
                'month' => $recordDate->month,
                'year' => $recordDate->year,
                'record_date' => $recordDate->toDateString(),
                'reading_type' => 'handover',
                'lifecycle_event_key' => $key,
                'electricity_old' => $electricity,
                'electricity_new' => $electricity,
                'electricity_image' => $electricityImage ?? $reading->electricity_image,
                'water_old' => $water,
                'water_new' => $water,
                'water_image' => $waterImage ?? $reading->water_image,
                'status' => UtilityReading::STATUS_DRAFT,
                'note' => 'Chỉ số bàn giao chờ khách thuê xác nhận.',
            ])->save();
            $this->history($contract, $contract->status, $contract->status, $isNew ? 'prepare_handover_reading' : 'update_handover_reading', null, $actor, [
                'handover_reading_id' => $reading->id,
                'electricity' => $electricity,
                'water' => $water,
            ]);

            return $reading->fresh();
        }, 3);
    }

    public function reopenMoveInDetails(Contract $contract, User $actor, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, [Contract::STATUS_AWAITING_MOVE_IN], 'Chỉ được mở lại thông tin khi hợp đồng đang chờ nhận phòng.');
            if (! $contract->move_in_details_confirmed_at) {
                $this->fail('move_in_details', 'Khách chưa xác nhận thông tin nhận phòng.');
            }

            $previousConfirmedAt = $contract->move_in_details_confirmed_at;
            $previousConfirmedBy = $contract->move_in_details_confirmed_by;
            $contract->forceFill([
                'move_in_details_confirmed_at' => null,
                'move_in_details_confirmed_by' => null,
            ])->save();
            $this->history($contract, $contract->status, $contract->status, 'reopen_move_in_details', $reason, $actor, [
                'previous_confirmed_at' => $previousConfirmedAt?->toIso8601String(),
                'previous_confirmed_by' => $previousConfirmedBy,
            ]);

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
            $this->requireStatus($contract, [Contract::STATUS_AWAITING_MOVE_IN], 'Chỉ hợp đồng đang chờ nhận phòng mới được xác nhận nhận phòng.');
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
            if ($moveInAt->gt($contract->start_date->copy()->addMonthNoOverflow()->endOfDay())) {
                $this->fail('actual_move_in_at', 'Ngày nhận phòng thực tế không được muộn quá 1 tháng kể từ ngày bắt đầu hợp đồng.');
            }
            if (! $moveInAt->isSameDay($contract->scheduled_move_in_date) && blank($data['schedule_variance_reason'] ?? null)) {
                $this->fail('schedule_variance_reason', 'Nhận phòng sớm hoặc muộn phải ghi rõ lý do.');
            }
            if (! ($data['handover_confirmed'] ?? false)) {
                $this->fail('handover_confirmed', 'Phải xác nhận biên bản bàn giao trước khi nhận phòng.');
            }
            if (! $contract->move_in_inventory_snapshotted_at || ! $contract->move_in_details_confirmed_at) {
                $this->fail('move_in_details_confirmed', 'Khách thuê phải xác nhận thông tin nhận phòng trước khi nhận phòng.');
            }
            if (in_array($room->status, [Room::STATUS_MAINTENANCE, Room::STATUS_RETIRED], true)) {
                $this->fail('room_id', 'Phòng đang bảo trì hoặc đã ngừng khai thác, không thể nhận phòng.');
            }
            if ($room->status === Room::STATUS_OCCUPIED) {
                $this->fail('room_id', 'Phòng đang có người thuê, không thể nhận phòng.');
            }
            $otherMember = Contract::query()->where('room_id', $room->id)->whereKeyNot($contract->id)
                ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)->lockForUpdate()->first();
            if ($otherMember) {
                $this->fail('room_id', 'Phòng còn khách cũ. Hãy trả phòng hợp đồng '.$otherMember->contract_code.' trước.');
            }
            $this->ensureNoReservationConflict($contract);
            if ((int) $contract->number_of_people > (int) $room->max_people) {
                $this->fail('number_of_people', 'Số người vượt quá sức chứa của phòng.');
            }

            $handoverReading = UtilityReading::query()
                ->where('lifecycle_event_key', $this->readingKey($contract, 'handover'))
                ->lockForUpdate()->first();
            if (! $handoverReading || ! $handoverReading->isDraft()) {
                $this->fail('handover_reading', 'Chưa có chỉ số điện nước bàn giao đã được khách kiểm tra.');
            }
            $lastReading = UtilityReading::query()->where('room_id', $room->id)
                ->whereKeyNot($handoverReading->id)
                ->orderByDesc('record_date')->orderByDesc('id')->lockForUpdate()->first();
            $electricity = (int) $handoverReading->electricity_new;
            $water = (int) $handoverReading->water_new;
            if ($lastReading && ($electricity < $lastReading->electricity_new || $water < $lastReading->water_new)) {
                $this->fail('handover_reading', 'Chỉ số bàn giao đã cũ so với chỉ số mới nhất của phòng. Hãy mở lại, cập nhật và để khách xác nhận lại.');
            }

            $handoverReading->forceFill([
                'month' => $moveInAt->month,
                'year' => $moveInAt->year,
                'record_date' => $moveInAt->toDateString(),
                'status' => UtilityReading::STATUS_CONFIRMED,
                'note' => 'Chỉ số bàn giao đã được khách thuê xác nhận và quản trị viên chốt khi nhận phòng.',
            ])->save();
            $memberCount = $this->memberService->checkInApproved($contract, $actor, $moveInAt);
            $contract->forceFill([
                'actual_move_in_at' => $moveInAt,
                'checked_in_by' => $actor->id,
                'number_of_people' => $memberCount,
            ])->save();
            $room->forceFill([
                'status' => Room::STATUS_OCCUPIED,
                'current_people' => $memberCount,
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
            if ($deadline->gt($contract->start_date->copy()->addMonthNoOverflow()->endOfDay())) {
                $this->fail('reservation_expires_at', 'Hạn giữ phòng không được muộn quá 1 tháng kể từ ngày bắt đầu hợp đồng.');
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
            ], 'Hợp đồng đang có người ở phải thực hiện trả phòng, không thể hủy trực tiếp.');
            if (blank($reason)) {
                $this->fail('cancel_reason', 'Lý do hủy hợp đồng là bắt buộc.');
            }
            $this->lockRoom($contract);
            $paid = (float) $contract->payments()->where('payments.status', 'success')->lockForUpdate()->sum('amount_paid');
            $contract->forceFill([
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
                'deposit_resolution' => $paid > 0 ? Contract::DEPOSIT_NEEDS_RESOLUTION : null,
            ])->save();
            $this->memberService->withdrawForCancellation($contract, $actor, $reason);
            $this->transition($contract, Contract::STATUS_CANCELLED, 'cancel', $reason, $actor, [
                'deposit_received' => $paid,
                'deposit_requires_resolution' => $paid > 0,
            ]);
            if ($paid > 0) {
                $this->alert($contract, 'cancelled_deposit_resolution', 'Hợp đồng hủy có khoản đã thu cần xử lý', 'Quản trị viên cần xử lý tiền cọc theo thỏa thuận.', ['paid' => $paid]);
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
                $this->transition($contract, Contract::STATUS_EXPIRED, 'mark_expired', 'Hợp đồng đã hết hạn và đang chờ quyết định gia hạn hoặc trả phòng.', $actor);
                $this->resolveAlerts($contract, ['contract_expiring']);
                if ($this->alert($contract, 'contract_expired', 'Hợp đồng hết hạn - chờ xử lý', 'Hợp đồng đã hết hạn; cần xử lý theo một trong hai hướng: gia hạn hoặc trả phòng.')) {
                    app(ClientNotificationService::class)->contract(
                        $contract,
                        'contract_expired',
                        'Hợp đồng đã hết hạn - cần xử lý',
                        'Vui lòng chọn gia hạn hợp đồng hoặc đăng ký trả phòng để ban quản lý tiếp tục xử lý.'
                    );
                }
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
            $this->requireStatus($contract, Contract::OPEN_OCCUPANCY_STATUSES, 'Chỉ hợp đồng đang thuê hoặc quá hạn mới được trả phòng.');
            $room = $this->lockRoom($contract);
            $moveOutAt = Carbon::parse($data['actual_move_out_at']);
            if ($moveOutAt->isFuture()) {
                $this->fail('actual_move_out_at', 'Thời điểm trả phòng không được ở tương lai.');
            }
            if ($contract->actual_move_in_at && $moveOutAt->lt($contract->actual_move_in_at)) {
                $this->fail('actual_move_out_at', 'Thời điểm trả phòng không được trước thời điểm nhận phòng.');
            }
            $moveOutMinute = ($moveOutAt->hour * 60) + $moveOutAt->minute;
            if ($moveOutMinute < 8 * 60 || $moveOutMinute > 17 * 60) {
                $this->fail('actual_move_out_at', 'Bàn giao phòng phải thực hiện trong giờ hành chính từ 08:00 đến 17:00.');
            }
            if (blank($data['checkout_reason'] ?? null)) {
                $this->fail('checkout_reason', 'Phải nhập lý do trả phòng/kết thúc.');
            }
            $departureRequest = $this->departureScheduleForCheckout(
                $contract,
                $actor,
                $moveOutAt,
                $data['checkout_reason'],
            );
            $scheduledCheckoutAt = $departureRequest->scheduled_checkout_at;
            $approvedDepartureDate = $departureRequest->approved_end_date
                ?? $scheduledCheckoutAt?->copy()->startOfDay();
            $scheduleDateChanged = $approvedDepartureDate
                ? ! $moveOutAt->isSameDay($approvedDepartureDate)
                : false;
            if ($scheduleDateChanged && blank($data['schedule_variance_reason'] ?? null)) {
                $this->fail(
                    'schedule_variance_reason',
                    'Ngày trả phòng thực tế khác ngày đã duyệt. Vui lòng ghi rõ lý do thay đổi.'
                );
            }
            if (! ($data['handover_confirmed'] ?? false)) {
                $this->fail('handover_confirmed', 'Phải xác nhận hai bên đã đối chiếu biên bản bàn giao.');
            }
            $handoverItems = $contract->handoverItems()->lockForUpdate()->get();
            $submittedAssets = collect($data['asset_conditions'] ?? []);
            $allowedConditions = ['good', 'worn', 'damaged', 'missing'];
            $assetReport = $handoverItems->map(function ($item) use ($submittedAssets, $allowedConditions): array {
                $submitted = $submittedAssets->get((string) $item->id, $submittedAssets->get($item->id));
                if (! is_array($submitted) || ! in_array($submitted['condition'] ?? null, $allowedConditions, true)) {
                    $this->fail('asset_conditions', 'Phải ghi nhận tình trạng của toàn bộ tài sản bàn giao.');
                }

                return [
                    'handover_item_id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'condition' => $submitted['condition'],
                    'note' => $submitted['note'] ?? null,
                ];
            })->values()->all();
            $hasDamage = collect($assetReport)->contains(
                fn (array $item) => in_array($item['condition'], ['damaged', 'missing'], true)
            );
            $declaredDamage = filter_var($data['has_damage'] ?? false, FILTER_VALIDATE_BOOL);
            $settlementAmount = (float) ($data['settlement_amount'] ?? 0);
            if ($settlementAmount > 0 && ! $declaredDamage) {
                $this->fail('has_damage', 'Có tiền bồi thường nên phải chọn “Có hư hỏng/thất lạc”.');
            }
            if ($hasDamage && ! $declaredDamage) {
                $this->fail('has_damage', 'Tài sản có trạng thái hư hỏng hoặc thất lạc nên phải chọn “Có hư hỏng/thất lạc”.');
            }
            $hasDamage = $hasDamage || $declaredDamage;
            if ($hasDamage && blank($data['checkout_damage_note'] ?? null)) {
                $this->fail('checkout_damage_note', 'Có hư hỏng, thất lạc hoặc bồi thường thì phải ghi rõ nội dung.');
            }
            if ($hasDamage && empty($data['checkout_photo_paths'] ?? [])) {
                $this->fail('checkout_photos', 'Có hư hỏng, thất lạc hoặc bồi thường thì phải có ảnh hiện trạng.');
            }
            if ($hasDamage && $settlementAmount <= 0) {
                $this->fail('settlement_amount', 'Có hư hỏng hoặc thất lạc thì phải nhập số tiền bồi thường lớn hơn 0.');
            }
            if ($hasDamage && blank($data['settlement_description'] ?? null)) {
                $this->fail('settlement_description', 'Có hư hỏng hoặc thất lạc thì phải ghi nội dung khoản bồi thường.');
            }
            $lastReading = UtilityReading::query()->where('room_id', $contract->room_id)
                ->where('contract_id', $contract->id)->orderByDesc('record_date')->orderByDesc('id')->lockForUpdate()->first();
            if (! $lastReading) {
                $this->fail('checkout_electricity', 'Hợp đồng chưa có chỉ số bàn giao để đối chiếu khi trả phòng.');
            }
            $electricity = (int) $data['checkout_electricity'];
            $water = (int) $data['checkout_water'];
            if ($electricity < $lastReading->electricity_new || $water < $lastReading->water_new) {
                $this->fail('checkout_electricity', 'Chỉ số trả phòng không được nhỏ hơn chỉ số gần nhất.');
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
                'note' => 'Chỉ số cuối khi quản trị viên xác nhận trả phòng.',
            ]);
            $contract->forceFill([
                'actual_move_out_at' => $moveOutAt,
                'actual_end_date' => $moveOutAt->toDateString(),
                'terminated_at' => $moveOutAt,
                'checked_out_by' => $actor->id,
                'checkout_reason' => $data['checkout_reason'],
                'termination_note' => $data['checkout_reason'],
                'checkout_key_count' => 0,
                'checkout_asset_report' => $assetReport,
                'checkout_has_damage' => $hasDamage,
                'checkout_damage_note' => $data['checkout_damage_note'] ?? null,
                'checkout_photo_paths' => $data['checkout_photo_paths'] ?? [],
                'checkout_handover_confirmed_at' => now(),
            ])->save();
            $this->memberService->moveOutAll($contract, $actor, $moveOutAt, $data['checkout_reason']);
            if ($contract->approved_termination_request_id) {
                ContractTerminationRequest::query()
                    ->whereKey($contract->approved_termination_request_id)
                    ->where('status', ContractTerminationRequest::STATUS_APPROVED)
                    ->lockForUpdate()
                    ->update([
                        'status' => ContractTerminationRequest::STATUS_COMPLETED,
                        'fulfilled_at' => now(),
                    ]);
            }
            $room->forceFill([
                'status' => $room->status === Room::STATUS_MAINTENANCE ? Room::STATUS_MAINTENANCE : Room::STATUS_AVAILABLE,
                'current_people' => 0,
            ])->save();

            $this->transition($contract, Contract::STATUS_SETTLING, 'check_out', $data['checkout_reason'], $actor, [
                'electricity' => $electricity,
                'water' => $water,
                'settlement_amount' => $settlementAmount,
                'departure_request_id' => $departureRequest->id,
                'approved_end_date' => $approvedDepartureDate?->toDateString(),
                'schedule_date_changed' => $scheduleDateChanged,
                'schedule_variance_reason' => $data['schedule_variance_reason'] ?? null,
            ]);
            $this->settlements->generate(
                $contract->fresh(),
                $settlementAmount,
                $data['settlement_description'] ?? null,
            );
            $this->resolveAlerts($contract, ['contract_expired', 'departure_due']);
            app(TenantAccountLifecycle::class)->sync($contract->tenant()->with('user')->firstOrFail());

            return $contract->fresh();
        }, 3);
    }

    public function completeSettlement(Contract $contract, ?User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $data): Contract {
            $contract = $this->lockContract($contract);
            if ($contract->status === Contract::STATUS_COMPLETED) {
                return $contract;
            }
            $this->requireStatus($contract, [Contract::STATUS_SETTLING], 'Chỉ hợp đồng đang quyết toán mới được hoàn tất.');
            if (! $contract->actual_move_out_at) {
                $this->fail('contract', 'Hợp đồng chưa trả phòng nên không thể hoàn tất quyết toán.');
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
                        'written_off_by' => $actor?->id,
                        'write_off_reason' => $data['write_off_reason'],
                    ])->save();
                }
            }

            $resolution = (float) $contract->deposit_amount <= 0
                ? Contract::DEPOSIT_NOT_REQUIRED
                : $contract->deposit_resolution;
            if ((float) $contract->deposit_amount > 0
                && ! in_array($resolution, [Contract::DEPOSIT_REFUNDED, Contract::DEPOSIT_DEDUCTED, Contract::DEPOSIT_RETAINED], true)) {
                $this->fail('deposit_resolution', 'Tiền cọc chưa được xử lý qua quy trình quyết toán và chứng từ hoàn/khấu trừ.');
            }
            if ($resolution === Contract::DEPOSIT_REFUNDED
                && (float) $contract->deposit_refund_amount > 0
                && (! $contract->deposit_transferred_at || blank($contract->deposit_transfer_proof))) {
                $this->fail('deposit_resolution', 'Chưa có thời điểm và chứng từ chuyển khoản hoàn cọc.');
            }
            if ($resolution === Contract::DEPOSIT_RETAINED && blank($contract->deposit_damage_proof)) {
                $this->fail('deposit_resolution', 'Giữ toàn bộ tiền cọc phải có chứng từ hoặc ảnh thiệt hại.');
            }
            if ($resolution === Contract::DEPOSIT_DEDUCTED
                && (float) $contract->deposit_refund_amount > 0
                && (! $contract->deposit_transferred_at || blank($contract->deposit_transfer_proof))) {
                $this->fail('deposit_resolution', 'Hoàn cọc một phần phải có chứng từ chuyển khoản phần còn lại.');
            }
            if (in_array($resolution, [Contract::DEPOSIT_DEDUCTED, Contract::DEPOSIT_RETAINED], true)
                && blank($data['settlement_note'] ?? null)
                && blank($contract->deposit_process_reason)) {
                $this->fail('settlement_note', 'Khấu trừ hoặc giữ cọc bắt buộc có lý do/chứng từ.');
            }

            $settlementNote = $data['settlement_note'] ?? $contract->deposit_process_reason;

            $contract->forceFill([
                'deposit_resolution' => $resolution,
                'deposit_status' => $resolution,
                'deposit_resolved_at' => now(),
                'deposit_resolved_by' => $actor?->id,
                'settlement_note' => $settlementNote,
                'completed_at' => now(),
                'completed_by' => $actor?->id,
            ])->save();
            $this->transition($contract, Contract::STATUS_COMPLETED, 'complete_settlement', $settlementNote, $actor, [
                'deposit_resolution' => $resolution,
                'written_off_invoice_ids' => $openInvoices->pluck('id')->all(),
            ]);
            $this->settlements->markSettled($contract);
            app(TenantAccountLifecycle::class)->sync($contract->tenant()->with('user')->firstOrFail());

            return $contract->fresh();
        }, 3);
    }

    public function completeSettlementAfterAutomaticRefundConfirmation(Contract $contract): Contract
    {
        $contract->refresh();

        if ($contract->deposit_receipt_confirmation_source !== DepositRefundReceiptService::SOURCE_AUTOMATIC) {
            $this->fail('refund', 'Chỉ được tự động hoàn tất hợp đồng sau khi khoản hoàn cọc đã quá hạn xác nhận 24 giờ.');
        }

        return $this->completeSettlement($contract, null, [
            'confirm_complete' => true,
            'settlement_note' => 'Hệ thống tự động hoàn tất hợp đồng do khách không phản hồi xác nhận khoản hoàn cọc trong 24 giờ.',
        ]);
    }

    private function departureScheduleForCheckout(
        Contract $contract,
        User $actor,
        Carbon $moveOutAt,
        string $reason,
    ): ContractTerminationRequest {
        if ($contract->approved_termination_request_id) {
            $approvedRequest = ContractTerminationRequest::query()
                ->whereKey($contract->approved_termination_request_id)
                ->where('contract_id', $contract->id)
                ->lockForUpdate()
                ->first();

            if (! $approvedRequest || $approvedRequest->status !== ContractTerminationRequest::STATUS_APPROVED) {
                $this->fail('departure_schedule', 'Lịch bàn giao của hợp đồng không còn hợp lệ.');
            }

            return $approvedRequest;
        }

        if (ContractExtensionRequest::query()
            ->where('contract_id', $contract->id)
            ->whereIn('status', [
                ContractExtensionRequest::STATUS_PENDING,
                ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
            ])->lockForUpdate()->exists()) {
            $this->fail('departure_schedule', 'Hợp đồng đang có yêu cầu hoặc phụ lục gia hạn chờ xử lý.');
        }

        $departureRequest = ContractTerminationRequest::query()
            ->where('contract_id', $contract->id)
            ->where('status', ContractTerminationRequest::STATUS_PENDING)
            ->lockForUpdate()
            ->latest('id')
            ->first();
        $type = $moveOutAt->copy()->startOfDay()->gt($contract->end_date->copy()->startOfDay())
            ? ContractTerminationRequest::TYPE_OVERDUE_DEPARTURE
            : ($moveOutAt->copy()->startOfDay()->lt($contract->end_date->copy()->startOfDay())
                ? ContractTerminationRequest::TYPE_EARLY_TERMINATION
                : ContractTerminationRequest::TYPE_END_OF_TERM);

        if (! $departureRequest) {
            $departureRequest = ContractTerminationRequest::query()->create([
                'contract_id' => $contract->id,
                'tenant_id' => $contract->tenant_id,
                'requested_end_date' => $moveOutAt->toDateString(),
                'reason' => $reason,
                'request_type' => $type,
                'status' => ContractTerminationRequest::STATUS_APPROVED,
                'admin_note' => 'Quản trị viên ghi nhận lịch bàn giao trực tiếp tại thời điểm trả phòng.',
                'approved_end_date' => $moveOutAt->toDateString(),
                'scheduled_checkout_at' => $moveOutAt,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);
        } else {
            $departureRequest->forceFill([
                'request_type' => $type,
                'status' => ContractTerminationRequest::STATUS_APPROVED,
                'admin_note' => 'Quản trị viên xác nhận yêu cầu và lịch bàn giao trực tiếp tại thời điểm trả phòng.',
                'approved_end_date' => $moveOutAt->toDateString(),
                'scheduled_checkout_at' => $moveOutAt,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ])->save();
        }

        $contract->forceFill([
            'scheduled_move_out_at' => $moveOutAt,
            'approved_termination_request_id' => $departureRequest->id,
        ])->save();
        $this->history(
            $contract,
            $contract->status,
            $contract->status,
            'schedule_departure',
            $reason,
            $actor,
            [
                'departure_request_id' => $departureRequest->id,
                'request_type' => $type,
                'approved_end_date' => $moveOutAt->toDateString(),
                'scheduled_checkout_at' => $moveOutAt->toIso8601String(),
                'source' => 'admin_checkout',
            ]
        );

        return $departureRequest;
    }

    public function extendContract(Contract $contract, User $actor, Carbon|string $newEndDate, string $reason, array $terms = []): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $newEndDate, $reason, $terms): Contract {
            $contract = $this->lockContract($contract);
            $this->requireStatus($contract, Contract::OPEN_OCCUPANCY_STATUSES, 'Chỉ hợp đồng đang thuê hoặc quá hạn mới được gia hạn.');
            $newEndDate = Carbon::parse($newEndDate)->startOfDay();
            if (! $newEndDate->gt($contract->end_date->startOfDay())) {
                $this->fail('new_end_date', 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.');
            }
            if (ContractTerminationRequest::query()
                ->where('contract_id', $contract->id)
                ->whereIn('status', [
                    ContractTerminationRequest::STATUS_PENDING,
                    ContractTerminationRequest::STATUS_APPROVED,
                ])->lockForUpdate()->exists()) {
                $this->fail('contract', 'Hợp đồng đang có yêu cầu hoặc lịch trả phòng nên chưa thể gia hạn.');
            }
            $this->lockRoom($contract);
            $oldEndDate = $contract->end_date->copy();
            $oldMonthlyRent = (float) $contract->monthly_rent;
            $newMonthlyRent = array_key_exists('monthly_rent', $terms)
                ? (float) $terms['monthly_rent']
                : $oldMonthlyRent;
            if ($newMonthlyRent < 0) {
                $this->fail('monthly_rent', 'Giá thuê gia hạn không hợp lệ.');
            }
            $contract->forceFill([
                'end_date' => $newEndDate,
                'monthly_rent' => $newMonthlyRent,
            ])->save();
            try {
                $this->ensureNoReservationConflict($contract);
            } catch (\Throwable $exception) {
                $contract->forceFill([
                    'end_date' => $oldEndDate,
                    'monthly_rent' => $oldMonthlyRent,
                ])->save();
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
                'old_monthly_rent' => $oldMonthlyRent,
                'new_monthly_rent' => $newMonthlyRent,
                'extension_request_id' => $terms['extension_request_id'] ?? null,
            ]);
            if ($target === Contract::STATUS_ACTIVE) {
                $this->resolveAlerts($contract, ['contract_expired', 'contract_expiring']);
            }

            return $contract->fresh();
        }, 3);
    }

    public function scheduleDeparture(
        ContractTerminationRequest $departureRequest,
        User $actor,
        Carbon|string $approvedEndDate,
        Carbon|string $scheduledCheckoutAt,
        ?string $adminNote = null,
    ): ContractTerminationRequest {
        return DB::transaction(function () use ($departureRequest, $actor, $approvedEndDate, $scheduledCheckoutAt, $adminNote) {
            $departureRequest = ContractTerminationRequest::query()->lockForUpdate()->findOrFail($departureRequest->id);
            if ($departureRequest->status !== ContractTerminationRequest::STATUS_PENDING) {
                $this->fail('request', 'Yêu cầu rời phòng này đã được xử lý.');
            }

            $contract = $this->lockContract($departureRequest->contract);
            $this->requireStatus($contract, Contract::OPEN_OCCUPANCY_STATUSES, 'Chỉ được xếp lịch rời phòng khi khách vẫn đang ở.');
            if (ContractExtensionRequest::query()
                ->where('contract_id', $contract->id)
                ->whereIn('status', [
                    ContractExtensionRequest::STATUS_PENDING,
                    ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
                ])->lockForUpdate()->exists()) {
                $this->fail('contract', 'Hợp đồng đang có yêu cầu hoặc phụ lục gia hạn chờ xác nhận.');
            }
            $approvedEndDate = Carbon::parse($approvedEndDate)->startOfDay();
            $scheduledCheckoutAt = Carbon::parse($scheduledCheckoutAt);
            if ($approvedEndDate->lt(today())) {
                $this->fail('approved_end_date', 'Ngày rời phòng được duyệt không được ở trong quá khứ.');
            }
            if (! $scheduledCheckoutAt->isSameDay($approvedEndDate)) {
                $this->fail('scheduled_checkout_at', 'Thời gian bàn giao phải nằm trong ngày rời phòng được duyệt.');
            }
            if ($contract->status === Contract::STATUS_ACTIVE && $approvedEndDate->gt($contract->end_date)) {
                $this->fail('approved_end_date', 'Muốn ở sau ngày hết hạn cần thực hiện gia hạn hợp đồng.');
            }
            $hasOtherSchedule = ContractTerminationRequest::query()
                ->where('contract_id', $contract->id)
                ->whereKeyNot($departureRequest->id)
                ->where('status', ContractTerminationRequest::STATUS_APPROVED)
                ->lockForUpdate()
                ->exists();
            if ($hasOtherSchedule) {
                $this->fail('request', 'Hợp đồng đã có một lịch rời phòng được duyệt.');
            }

            $type = $contract->status === Contract::STATUS_EXPIRED
                ? ContractTerminationRequest::TYPE_OVERDUE_DEPARTURE
                : ($approvedEndDate->lt($contract->end_date)
                    ? ContractTerminationRequest::TYPE_EARLY_TERMINATION
                    : ContractTerminationRequest::TYPE_END_OF_TERM);
            $departureRequest->forceFill([
                'request_type' => $type,
                'status' => ContractTerminationRequest::STATUS_APPROVED,
                'admin_note' => $adminNote,
                'approved_end_date' => $approvedEndDate,
                'scheduled_checkout_at' => $scheduledCheckoutAt,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ])->save();
            $contract->forceFill([
                'scheduled_move_out_at' => $scheduledCheckoutAt,
                'approved_termination_request_id' => $departureRequest->id,
            ])->save();
            $this->history($contract, $contract->status, $contract->status, 'schedule_departure', $adminNote ?: $departureRequest->reason, $actor, [
                'departure_request_id' => $departureRequest->id,
                'request_type' => $type,
                'approved_end_date' => $approvedEndDate->toDateString(),
                'scheduled_checkout_at' => $scheduledCheckoutAt->toIso8601String(),
            ]);
            $this->resolveAlerts($contract, ['contract_expiring', 'contract_expired']);

            return $departureRequest->fresh('contract');
        }, 3);
    }

    public function processDailyAlerts(): array
    {
        $expired = $this->markExpiredContracts();
        $created = 0;
        $definitions = [
            [Contract::STATUS_PENDING_SIGNATURE, 'signature_due_at', 'signature_overdue', 'Quá hạn ký hợp đồng', 'Hợp đồng đã quá hạn ký và cần quản trị viên xử lý.'],
            [Contract::STATUS_PENDING_DEPOSIT, 'deposit_due_at', 'deposit_overdue', 'Quá hạn tiền cọc', 'Hợp đồng chưa thanh toán đủ tiền cọc sau hạn thanh toán.'],
            [Contract::STATUS_AWAITING_MOVE_IN, 'reservation_expires_at', 'move_in_overdue', 'Quá hạn nhận phòng', 'Đã quá hạn giữ phòng; hệ thống không tự hủy hợp đồng.'],
        ];
        foreach ($definitions as [$status, $column, $type, $title, $message]) {
            Contract::query()->where('status', $status)->whereNotNull($column)->where($column, '<', now())
                ->orderBy('id')->each(function (Contract $contract) use ($type, $title, $message, &$created): void {
                    if ($this->alert($contract, $type, $title, $message)) {
                        $created++;
                    }
                });
        }
        foreach ([30, 15, 7] as $days) {
            $endDate = today()->addDays($days)->toDateString();
            Contract::query()
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereDate('end_date', $endDate)
                ->whereNull('scheduled_move_out_at')
                ->orderBy('id')
                ->each(function (Contract $contract) use ($days, $endDate, &$created): void {
                    $message = "Hợp đồng {$contract->contract_code} còn {$days} ngày sẽ hết hạn ({$contract->end_date->format('d/m/Y')}). Cần thống nhất gia hạn hoặc lịch bàn giao phòng.";
                    if ($this->alert(
                        $contract,
                        'contract_expiring',
                        "Hợp đồng sắp hết hạn trong {$days} ngày",
                        $message,
                        ['period' => $endDate.':'.$days, 'days_remaining' => $days]
                    )) {
                        app(ClientNotificationService::class)->contract(
                            $contract,
                            'contract_expiring',
                            "Hợp đồng còn {$days} ngày",
                            'Vui lòng chọn gia hạn hợp đồng hoặc đăng ký lịch rời phòng để ban quản lý chuẩn bị bàn giao.'
                        );
                        $created++;
                    }
                });
        }
        Contract::query()->where('status', Contract::STATUS_EXPIRED)->orderBy('id')
            ->each(function (Contract $contract) use (&$created): void {
                if ($this->alert(
                    $contract,
                    'contract_expired',
                    'Hợp đồng hết hạn - chờ xử lý',
                    'Cần xử lý theo một trong hai hướng: gia hạn hoặc trả phòng.'
                )) {
                    $created++;
                }
            });
        Contract::query()
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->whereNotNull('scheduled_move_out_at')
            ->whereDate('scheduled_move_out_at', '<=', today())
            ->orderBy('id')
            ->each(function (Contract $contract) use (&$created): void {
                $message = 'Đã đến ngày bàn giao phòng '.$contract->scheduled_move_out_at?->format('d/m/Y').' trong giờ hành chính. Cần chốt chỉ số, tài sản và thực hiện trả phòng.';
                if ($this->alert($contract, 'departure_due', 'Đến lịch bàn giao và trả phòng', $message)) {
                    app(ClientNotificationService::class)->contract(
                        $contract,
                        'departure_due',
                        'Đến lịch bàn giao phòng',
                        'Vui lòng phối hợp với ban quản lý để kiểm tra phòng và chốt chỉ số điện nước.'
                    );
                    $created++;
                }
            });

        return ['expired' => $expired, 'alerts_created' => $created];
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
        if ($end->lt($start->copy()->addYear())) {
            $this->fail('end_date', 'Hợp đồng phải có thời hạn tối thiểu 1 năm.');
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
        if ($scheduled->gt($start->copy()->addMonthNoOverflow()->endOfDay())
            || $deadline->gt($start->copy()->addMonthNoOverflow()->endOfDay())) {
            $this->fail('reservation_expires_at', 'Ngày dự kiến và hạn cuối nhận phòng không được muộn quá 1 tháng kể từ ngày bắt đầu hợp đồng.');
        }
    }

    private function ensureRoomCanAcceptDraftSchedule(Room $room, array $data): void
    {
        $reservedContract = Contract::query()
            ->where('room_id', $room->id)
            ->whereIn('status', [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
            ])
            ->lockForUpdate()
            ->first();
        if ($reservedContract) {
            $availableFrom = $reservedContract->end_date->copy()->addDay()->startOfDay();
            if (Carbon::parse($data['start_date'])->startOfDay()->lt($availableFrom)) {
                $this->fail(
                    'room_id',
                    'Phòng đã được giữ chỗ bởi hợp đồng '.$reservedContract->contract_code
                    .' và đang chờ nhận phòng. Ngày bắt đầu sớm nhất là '.$availableFrom->format('d/m/Y').'.'
                );
            }
        }

        $currentOccupancy = Contract::query()
            ->where('room_id', $room->id)
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->lockForUpdate()
            ->latest('end_date')
            ->first();

        if (! $currentOccupancy) {
            if ($room->status === Room::STATUS_OCCUPIED) {
                $this->fail('room_id', 'Phòng đang có khách nhưng chưa xác định ngày trống. Hãy trả phòng hợp đồng hiện tại trước.');
            }

            return;
        }

        if ($currentOccupancy->status === Contract::STATUS_EXPIRED
            || $currentOccupancy->end_date->copy()->endOfDay()->isPast()) {
            $this->fail('start_date', 'Khách hiện tại đã quá hạn nhưng chưa trả phòng. Chưa thể xếp lịch thuê mới.');
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

        // Người đại diện có thể đứng tên nhiều phòng trong cùng thời gian. Chỉ người ở cùng
        // mới bị giới hạn để tránh ghi nhận một người đang cư trú tại nhiều phòng.
        $identityNumbers = $contract->members()->current()
            ->where('role', ContractTenant::ROLE_TENANT)
            ->whereNotNull('identity_number')
            ->pluck('identity_number');
        if ($identityNumbers->isNotEmpty()) {
            $memberConflict = ContractTenant::query()
                ->where('contract_id', '!=', $contract->id)
                ->current()
                ->whereIn('identity_number', $identityNumbers)
                ->whereHas('contract', fn (Builder $query) => $query
                    ->whereIn('status', Contract::RESERVING_STATUSES)
                    ->whereDate('start_date', '<=', $contract->end_date)
                    ->whereDate('end_date', '>=', $contract->start_date))
                ->lockForUpdate()
                ->first();
            if ($memberConflict) {
                $this->fail('members', 'Có người thuê đã thuộc hợp đồng trùng thời gian: '.$memberConflict->contract->contract_code.'.');
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

    private function notifyIncompleteMoveInProfiles(Contract $contract): void
    {
        $incomplete = $this->memberService->incompleteMoveInProfiles($contract);
        if ($incomplete->isEmpty()) {
            return;
        }

        $names = $incomplete->pluck('full_name')->filter()->take(3)->implode(', ');
        $this->clientNotifications->contractOnce(
            $contract,
            'contract_members_profile_incomplete',
            'Hoàn thiện hồ sơ trước khi nhận phòng',
            'Hợp đồng '.$contract->contract_code.' còn '.$incomplete->count().' người chưa đủ thông tin'.($names !== '' ? ': '.$names : '').'. Vui lòng bổ sung để có thể xác nhận nhận phòng.',
        );
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

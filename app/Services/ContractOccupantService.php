<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractOccupant;
use App\Models\ContractOccupantHistory;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractOccupantService
{
    private const DECLARABLE_CONTRACT_STATUSES = [
        Contract::STATUS_DRAFT,
        Contract::STATUS_PENDING_SIGNATURE,
        Contract::STATUS_PENDING_DEPOSIT,
        Contract::STATUS_AWAITING_MOVE_IN,
        Contract::STATUS_ACTIVE,
        Contract::STATUS_EXPIRED,
    ];

    public function syncAdminDraftOccupants(Contract $contract, Tenant $representative, bool $representativeIsOccupant, array $occupants, User $actor): void
    {
        $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
        if ($contract->status !== Contract::STATUS_DRAFT) {
            $this->fail('occupants', 'Chỉ được chỉnh danh sách người ở trực tiếp khi hợp đồng còn là bản nháp.');
        }

        $this->ensureRepresentative($contract, $representative, $representativeIsOccupant, $actor);
        $existing = ContractOccupant::query()
            ->where('contract_id', $contract->id)
            ->where('role', ContractOccupant::ROLE_OCCUPANT)
            ->whereIn('status', [ContractOccupant::STATUS_PENDING, ContractOccupant::STATUS_APPROVED])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $retainedIds = [];

        foreach ($occupants as $payload) {
            $data = $this->profileData($payload);
            $old = filled($payload['id'] ?? null) ? $existing->get((int) $payload['id']) : null;

            if ($old && $this->profileMatches($old, $data)) {
                $retainedIds[] = $old->id;
                if ($old->status === ContractOccupant::STATUS_PENDING) {
                    $this->transition($old, ContractOccupant::STATUS_APPROVED, 'admin_approve', 'Admin xác nhận trong bản nháp hợp đồng.', $actor);
                }
                continue;
            }

            if ($old) {
                $this->transition($old, ContractOccupant::STATUS_WITHDRAWN, 'replace_profile', 'Hồ sơ được thay bằng phiên bản mới trong bản nháp.', $actor);
            }

            $created = $this->createOccupant($contract, $data + [
                'role' => ContractOccupant::ROLE_OCCUPANT,
                'status' => ContractOccupant::STATUS_APPROVED,
                'declared_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'replaces_occupant_id' => $old?->id,
                'identity_front_path' => $old?->identity_front_path,
                'identity_back_path' => $old?->identity_back_path,
            ], $actor, 'admin_declare', 'Admin khai báo và duyệt trong bản nháp.');
            $retainedIds[] = $created->id;
        }

        $existing->except($retainedIds)->each(function (ContractOccupant $occupant) use ($actor): void {
            if (in_array($occupant->status, [ContractOccupant::STATUS_PENDING, ContractOccupant::STATUS_APPROVED], true)) {
                $this->transition($occupant, ContractOccupant::STATUS_WITHDRAWN, 'remove_from_draft', 'Được gỡ khỏi danh sách dự kiến trong bản nháp.', $actor);
            }
        });

        $this->syncPlannedCount($contract);
        $this->ensureCapacity($contract);
    }

    public function declareByTenant(Contract $contract, User $actor, array $data): ContractOccupant
    {
        return DB::transaction(function () use ($contract, $actor, $data): ContractOccupant {
            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($contract->id);
            $this->ensureContractOwner($contract, $actor);
            if (! in_array($contract->status, self::DECLARABLE_CONTRACT_STATUSES, true)) {
                $this->fail('occupant', 'Hợp đồng không còn nhận khai báo người ở.');
            }

            $this->ensureCapacity($contract, 1);
            $profile = $this->profileData($data);
            if (filled($profile['identity_number']) && ContractOccupant::query()
                ->where('contract_id', $contract->id)
                ->current()
                ->where('identity_number', $profile['identity_number'])
                ->lockForUpdate()
                ->exists()) {
                $this->fail('identity_number', 'CCCD/giấy tờ này đã có trong danh sách hiện tại.');
            }

            $occupant = $this->createOccupant($contract, $profile + [
                'role' => ContractOccupant::ROLE_OCCUPANT,
                'status' => ContractOccupant::STATUS_PENDING,
                'declared_by' => $actor->id,
            ], $actor, 'tenant_declare', 'Người đại diện gửi khai báo để admin duyệt.');
            $this->syncPlannedCount($contract);

            return $occupant->fresh();
        }, 3);
    }

    public function withdrawByTenant(ContractOccupant $occupant, User $actor): ContractOccupant
    {
        return DB::transaction(function () use ($occupant, $actor): ContractOccupant {
            $occupant = ContractOccupant::query()->with('contract')->lockForUpdate()->findOrFail($occupant->id);
            $this->ensureContractOwner($occupant->contract, $actor);
            if ($occupant->role === ContractOccupant::ROLE_REPRESENTATIVE || $occupant->status !== ContractOccupant::STATUS_PENDING) {
                $this->fail('occupant', 'Chỉ có thể rút khai báo người ở đang chờ duyệt.');
            }
            $this->transition($occupant, ContractOccupant::STATUS_WITHDRAWN, 'tenant_withdraw', 'Người đại diện rút khai báo.', $actor);
            $this->syncPlannedCount($occupant->contract);

            return $occupant->fresh();
        }, 3);
    }

    public function approve(ContractOccupant $occupant, User $actor): ContractOccupant
    {
        return DB::transaction(function () use ($occupant, $actor): ContractOccupant {
            $occupant = ContractOccupant::query()->lockForUpdate()->findOrFail($occupant->id);
            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($occupant->contract_id);
            if ($occupant->status !== ContractOccupant::STATUS_PENDING) {
                $this->fail('occupant', 'Khai báo này đã được xử lý trước đó.');
            }
            $this->ensureCapacity($contract);

            $active = in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true) && $contract->actual_move_in_at;
            $target = $active ? ContractOccupant::STATUS_CHECKED_IN : ContractOccupant::STATUS_APPROVED;
            $occupant->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => null,
                'actual_move_in_at' => $active ? now() : null,
            ])->save();
            $this->transition($occupant, $target, 'admin_approve', null, $actor);
            $this->syncCounts($contract);

            return $occupant->fresh();
        }, 3);
    }

    public function reject(ContractOccupant $occupant, User $actor, string $reason): ContractOccupant
    {
        return DB::transaction(function () use ($occupant, $actor, $reason): ContractOccupant {
            $occupant = ContractOccupant::query()->lockForUpdate()->findOrFail($occupant->id);
            if ($occupant->status !== ContractOccupant::STATUS_PENDING) {
                $this->fail('occupant', 'Khai báo này đã được xử lý trước đó.');
            }
            $occupant->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();
            $this->transition($occupant, ContractOccupant::STATUS_REJECTED, 'admin_reject', $reason, $actor);
            $this->syncCounts($occupant->contract()->firstOrFail());

            return $occupant->fresh();
        }, 3);
    }

    public function checkInApproved(Contract $contract, User $actor, Carbon $moveInAt): int
    {
        $occupants = ContractOccupant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->get();
        if ($occupants->contains('status', ContractOccupant::STATUS_PENDING)) {
            $this->fail('occupants', 'Còn người ở đang chờ duyệt. Hãy duyệt hoặc từ chối trước khi check-in.');
        }

        $approved = $occupants->where('status', ContractOccupant::STATUS_APPROVED);
        if ($approved->isEmpty()) {
            $this->fail('occupants', 'Phải có ít nhất một người ở được duyệt trước khi check-in.');
        }
        foreach ($approved as $occupant) {
            $occupant->forceFill(['actual_move_in_at' => $moveInAt])->save();
            $this->transition($occupant, ContractOccupant::STATUS_CHECKED_IN, 'contract_check_in', null, $actor, [
                'actual_move_in_at' => $moveInAt->toIso8601String(),
            ]);
        }

        return ContractOccupant::query()->where('contract_id', $contract->id)
            ->where('status', ContractOccupant::STATUS_CHECKED_IN)->count();
    }

    public function moveOutAll(Contract $contract, User $actor, Carbon $moveOutAt, ?string $reason): void
    {
        $occupants = ContractOccupant::query()->where('contract_id', $contract->id)
            ->where('status', ContractOccupant::STATUS_CHECKED_IN)->lockForUpdate()->get();
        foreach ($occupants as $occupant) {
            $occupant->forceFill(['actual_move_out_at' => $moveOutAt])->save();
            $this->transition($occupant, ContractOccupant::STATUS_MOVED_OUT, 'contract_check_out', $reason, $actor, [
                'actual_move_out_at' => $moveOutAt->toIso8601String(),
            ]);
        }
    }

    public function withdrawForCancellation(Contract $contract, User $actor, string $reason): void
    {
        $occupants = ContractOccupant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->get();
        foreach ($occupants as $occupant) {
            $this->transition($occupant, ContractOccupant::STATUS_WITHDRAWN, 'contract_cancelled', $reason, $actor);
        }
    }

    public function moveOut(ContractOccupant $occupant, User $actor, Carbon|string $moveOutAt, string $reason): ContractOccupant
    {
        return DB::transaction(function () use ($occupant, $actor, $moveOutAt, $reason): ContractOccupant {
            $occupant = ContractOccupant::query()->lockForUpdate()->findOrFail($occupant->id);
            if ($occupant->status !== ContractOccupant::STATUS_CHECKED_IN) {
                $this->fail('occupant', 'Chỉ người đang ở mới có thể xác nhận rời phòng.');
            }
            $moveOutAt = Carbon::parse($moveOutAt);
            if ($moveOutAt->isFuture() || ($occupant->actual_move_in_at && $moveOutAt->lt($occupant->actual_move_in_at))) {
                $this->fail('actual_move_out_at', 'Thời điểm rời phòng không hợp lệ.');
            }
            $occupant->forceFill(['actual_move_out_at' => $moveOutAt])->save();
            $this->transition($occupant, ContractOccupant::STATUS_MOVED_OUT, 'occupant_move_out', $reason, $actor);
            $this->syncCounts($occupant->contract()->with('room')->firstOrFail());

            return $occupant->fresh();
        }, 3);
    }

    public function ensureRepresentative(Contract $contract, Tenant $tenant, bool $isOccupant, ?User $actor): ContractOccupant
    {
        $representative = ContractOccupant::query()->where('contract_id', $contract->id)
            ->where('role', ContractOccupant::ROLE_REPRESENTATIVE)->lockForUpdate()->first();
        $data = [
            'tenant_id' => $tenant->id,
            'full_name' => $tenant->full_name,
            'date_of_birth' => $tenant->date_of_birth,
            'identity_number' => $tenant->cccd,
            'phone' => $tenant->phone,
            'relationship' => 'Người đại diện hợp đồng',
            'address' => $tenant->address,
        ];

        $targetStatus = $isOccupant ? ContractOccupant::STATUS_APPROVED : ContractOccupant::STATUS_NON_RESIDENT;

        if ($representative && (int) $representative->tenant_id === (int) $tenant->id) {
            $representative->forceFill($data)->save();
            if ($representative->status !== $targetStatus) {
                $this->transition(
                    $representative,
                    $targetStatus,
                    'representative_residency_changed',
                    $isOccupant ? 'Người đại diện được thêm vào danh sách người ở.' : 'Người đại diện không cư trú tại phòng.',
                    $actor,
                );
            }

            return $representative;
        }
        if ($representative) {
            $this->transition($representative, ContractOccupant::STATUS_WITHDRAWN, 'replace_representative', 'Thay đổi người đại diện khi hợp đồng còn là bản nháp.', $actor);
        }

        return $this->createOccupant($contract, $data + [
            'role' => ContractOccupant::ROLE_REPRESENTATIVE,
            'status' => $targetStatus,
            'declared_by' => $actor?->id,
            'reviewed_by' => $actor?->id,
            'reviewed_at' => now(),
            'replaces_occupant_id' => $representative?->id,
        ], $actor, 'set_representative', 'Đồng bộ người đại diện từ hợp đồng.');
    }

    private function createOccupant(Contract $contract, array $data, ?User $actor, string $action, ?string $reason): ContractOccupant
    {
        $occupant = ContractOccupant::query()->create(['contract_id' => $contract->id] + $data);
        $this->history($occupant, null, $occupant->status, $action, $reason, $actor);

        return $occupant;
    }

    private function transition(ContractOccupant $occupant, string $to, string $action, ?string $reason, ?User $actor, array $metadata = []): void
    {
        $from = $occupant->status;
        $occupant->forceFill(['status' => $to])->save();
        $this->history($occupant, $from, $to, $action, $reason, $actor, $metadata);
    }

    private function history(ContractOccupant $occupant, ?string $from, string $to, string $action, ?string $reason, ?User $actor, array $metadata = []): void
    {
        ContractOccupantHistory::query()->create([
            'contract_occupant_id' => $occupant->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'reason' => $reason,
            'performed_by' => $actor?->id,
            'performed_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    private function profileData(array $data): array
    {
        return [
            'full_name' => trim((string) $data['full_name']),
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'identity_number' => filled($data['identity_number'] ?? null) ? trim((string) $data['identity_number']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
        ];
    }

    private function profileMatches(ContractOccupant $occupant, array $data): bool
    {
        return collect(Arr::only($occupant->getAttributes(), array_keys($data)))
            ->map(fn ($value) => $value === null ? null : (string) $value)
            ->all() === collect($data)->map(fn ($value) => $value === null ? null : (string) $value)->all();
    }

    private function ensureCapacity(Contract $contract, int $additional = 0): void
    {
        $planned = ContractOccupant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->count();
        if ($planned + $additional > (int) $contract->room->max_people) {
            $this->fail('occupants', 'Danh sách người ở vượt quá sức chứa tối đa của phòng.');
        }
    }

    private function syncPlannedCount(Contract $contract): void
    {
        $count = ContractOccupant::query()->where('contract_id', $contract->id)->current()->count();
        $contract->forceFill(['number_of_people' => $count])->save();
    }

    private function syncCounts(Contract $contract): void
    {
        if (in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
            $count = ContractOccupant::query()->where('contract_id', $contract->id)
                ->where('status', ContractOccupant::STATUS_CHECKED_IN)->count();
            $contract->forceFill(['number_of_people' => $count])->save();
            $contract->room()->lockForUpdate()->firstOrFail()->forceFill(['current_people' => $count])->save();
        } else {
            $this->syncPlannedCount($contract);
        }
    }

    private function ensureContractOwner(Contract $contract, User $actor): void
    {
        if (! $actor->tenant || (int) $contract->tenant_id !== (int) $actor->tenant->id) {
            abort(404);
        }
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

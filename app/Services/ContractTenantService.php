<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\ContractTenantHistory;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractTenantService
{
    private const DECLARABLE_CONTRACT_STATUSES = [
        Contract::STATUS_DRAFT,
        Contract::STATUS_PENDING_SIGNATURE,
        Contract::STATUS_PENDING_DEPOSIT,
        Contract::STATUS_AWAITING_MOVE_IN,
        Contract::STATUS_ACTIVE,
        Contract::STATUS_EXPIRED,
    ];

    public function syncAdminDraftMembers(Contract $contract, Tenant $representative, array $members, User $actor): void
    {
        $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
        if ($contract->status !== Contract::STATUS_DRAFT) {
            $this->fail('members', 'Chỉ được chỉnh danh sách người thuê trực tiếp khi hợp đồng còn là bản nháp.');
        }

        $this->ensureRepresentative($contract, $representative, $actor);
        $existing = ContractTenant::query()
            ->where('contract_id', $contract->id)
            ->where('role', ContractTenant::ROLE_TENANT)
            ->whereIn('status', [ContractTenant::STATUS_PENDING, ContractTenant::STATUS_APPROVED])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $retainedIds = [];

        foreach ($members as $payload) {
            $data = $this->profileData($payload);
            $data['tenant_id'] = $this->resolveTenantProfile($data)->id;
            $old = filled($payload['id'] ?? null) ? $existing->get((int) $payload['id']) : null;

            if ($old && $this->profileMatches($old, $data)) {
                $retainedIds[] = $old->id;
                if ($old->status === ContractTenant::STATUS_PENDING) {
                    $this->transition($old, ContractTenant::STATUS_APPROVED, 'admin_approve', 'Admin xác nhận trong bản nháp hợp đồng.', $actor);
                }

                continue;
            }

            if ($old) {
                $this->transition($old, ContractTenant::STATUS_WITHDRAWN, 'replace_profile', 'Hồ sơ được thay bằng phiên bản mới trong bản nháp.', $actor);
            }

            $created = $this->createMember($contract, $data + [
                'role' => ContractTenant::ROLE_TENANT,
                'status' => ContractTenant::STATUS_APPROVED,
                'declared_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'replaces_contract_tenant_id' => $old?->id,
                'identity_front_path' => $old?->identity_front_path,
                'identity_back_path' => $old?->identity_back_path,
            ], $actor, 'admin_declare', 'Admin khai báo và duyệt trong bản nháp.');
            $retainedIds[] = $created->id;
        }

        $existing->except($retainedIds)->each(function (ContractTenant $member) use ($actor): void {
            if (in_array($member->status, [ContractTenant::STATUS_PENDING, ContractTenant::STATUS_APPROVED], true)) {
                $this->transition($member, ContractTenant::STATUS_WITHDRAWN, 'remove_from_draft', 'Được gỡ khỏi danh sách dự kiến trong bản nháp.', $actor);
            }
        });

        $this->syncPlannedCount($contract);
        $this->ensureCapacity($contract);
    }

    public function declareByTenant(Contract $contract, User $actor, array $data): ContractTenant
    {
        return DB::transaction(function () use ($contract, $actor, $data): ContractTenant {
            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($contract->id);
            $this->ensureContractOwner($contract, $actor);
            if (! in_array($contract->status, self::DECLARABLE_CONTRACT_STATUSES, true)) {
                $this->fail('member', 'Hợp đồng không còn nhận khai báo người thuê.');
            }

            $this->ensureCapacity($contract, 1);
            $profile = $this->profileData($data);
            $profile['tenant_id'] = $this->resolveTenantProfile($profile)->id;
            if (filled($profile['identity_number']) && ContractTenant::query()
                ->where('contract_id', $contract->id)
                ->current()
                ->where('identity_number', $profile['identity_number'])
                ->lockForUpdate()
                ->exists()) {
                $this->fail('identity_number', 'CCCD/giấy tờ này đã có trong danh sách hiện tại.');
            }

            $member = $this->createMember($contract, $profile + [
                'role' => ContractTenant::ROLE_TENANT,
                'status' => ContractTenant::STATUS_PENDING,
                'declared_by' => $actor->id,
            ], $actor, 'tenant_declare', 'Người đại diện gửi khai báo để admin duyệt.');
            $this->syncPlannedCount($contract);

            return $member->fresh();
        }, 3);
    }

    public function withdrawByTenant(ContractTenant $member, User $actor): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor): ContractTenant {
            $member = ContractTenant::query()->with('contract')->lockForUpdate()->findOrFail($member->id);
            $this->ensureContractOwner($member->contract, $actor);
            if ($member->role === ContractTenant::ROLE_REPRESENTATIVE || $member->status !== ContractTenant::STATUS_PENDING) {
                $this->fail('member', 'Chỉ có thể rút khai báo người thuê đang chờ duyệt.');
            }
            $this->transition($member, ContractTenant::STATUS_WITHDRAWN, 'tenant_withdraw', 'Người đại diện rút khai báo.', $actor);
            $this->syncPlannedCount($member->contract);

            return $member->fresh();
        }, 3);
    }

    public function approve(ContractTenant $member, User $actor): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor): ContractTenant {
            $member = ContractTenant::query()->lockForUpdate()->findOrFail($member->id);
            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($member->contract_id);
            if ($member->status !== ContractTenant::STATUS_PENDING) {
                $this->fail('member', 'Khai báo này đã được xử lý trước đó.');
            }
            $this->ensureCapacity($contract);

            $active = in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true) && $contract->actual_move_in_at;
            $target = $active ? ContractTenant::STATUS_CHECKED_IN : ContractTenant::STATUS_APPROVED;
            $member->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => null,
                'actual_move_in_at' => $active ? now() : null,
            ])->save();
            $this->transition($member, $target, 'admin_approve', null, $actor);
            $this->syncCounts($contract);

            return $member->fresh();
        }, 3);
    }

    public function reject(ContractTenant $member, User $actor, string $reason): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor, $reason): ContractTenant {
            $member = ContractTenant::query()->lockForUpdate()->findOrFail($member->id);
            if ($member->status !== ContractTenant::STATUS_PENDING) {
                $this->fail('member', 'Khai báo này đã được xử lý trước đó.');
            }
            $member->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();
            $this->transition($member, ContractTenant::STATUS_REJECTED, 'admin_reject', $reason, $actor);
            $this->syncCounts($member->contract()->firstOrFail());

            return $member->fresh();
        }, 3);
    }

    public function checkInApproved(Contract $contract, User $actor, Carbon $moveInAt): int
    {
        $members = ContractTenant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->get();
        if ($members->contains('status', ContractTenant::STATUS_PENDING)) {
            $this->fail('members', 'Còn người thuê đang chờ duyệt. Hãy duyệt hoặc từ chối trước khi check-in.');
        }

        $approved = $members->where('status', ContractTenant::STATUS_APPROVED);
        if ($approved->isEmpty()) {
            $this->fail('members', 'Phải có ít nhất một người thuê được duyệt trước khi check-in.');
        }
        foreach ($approved as $member) {
            $member->forceFill(['actual_move_in_at' => $moveInAt])->save();
            $this->transition($member, ContractTenant::STATUS_CHECKED_IN, 'contract_check_in', null, $actor, [
                'actual_move_in_at' => $moveInAt->toIso8601String(),
            ]);
        }

        return ContractTenant::query()->where('contract_id', $contract->id)
            ->where('status', ContractTenant::STATUS_CHECKED_IN)->count();
    }

    public function moveOutAll(Contract $contract, User $actor, Carbon $moveOutAt, ?string $reason): void
    {
        $members = ContractTenant::query()->where('contract_id', $contract->id)
            ->where('status', ContractTenant::STATUS_CHECKED_IN)->lockForUpdate()->get();
        foreach ($members as $member) {
            $member->forceFill(['actual_move_out_at' => $moveOutAt])->save();
            $this->transition($member, ContractTenant::STATUS_MOVED_OUT, 'contract_check_out', $reason, $actor, [
                'actual_move_out_at' => $moveOutAt->toIso8601String(),
            ]);
        }
    }

    public function withdrawForCancellation(Contract $contract, User $actor, string $reason): void
    {
        $members = ContractTenant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->get();
        foreach ($members as $member) {
            $this->transition($member, ContractTenant::STATUS_WITHDRAWN, 'contract_cancelled', $reason, $actor);
        }
    }

    public function moveOut(ContractTenant $member, User $actor, Carbon|string $moveOutAt, string $reason): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor, $moveOutAt, $reason): ContractTenant {
            $member = ContractTenant::query()->lockForUpdate()->findOrFail($member->id);
            if ($member->status !== ContractTenant::STATUS_CHECKED_IN) {
                $this->fail('member', 'Chỉ người đang ở mới có thể xác nhận rời phòng.');
            }
            $moveOutAt = Carbon::parse($moveOutAt);
            if ($moveOutAt->isFuture() || ($member->actual_move_in_at && $moveOutAt->lt($member->actual_move_in_at))) {
                $this->fail('actual_move_out_at', 'Thời điểm rời phòng không hợp lệ.');
            }
            $member->forceFill(['actual_move_out_at' => $moveOutAt])->save();
            $this->transition($member, ContractTenant::STATUS_MOVED_OUT, 'tenant_move_out', $reason, $actor);
            $this->syncCounts($member->contract()->with('room')->firstOrFail());

            return $member->fresh();
        }, 3);
    }

    public function ensureRepresentative(Contract $contract, Tenant $tenant, ?User $actor): ContractTenant
    {
        $representative = ContractTenant::query()->where('contract_id', $contract->id)
            ->where('role', ContractTenant::ROLE_REPRESENTATIVE)->lockForUpdate()->first();
        $data = [
            'tenant_id' => $tenant->id,
            'full_name' => $tenant->full_name,
            'date_of_birth' => $tenant->date_of_birth,
            'identity_number' => $tenant->cccd,
            'phone' => $tenant->phone,
            'relationship' => 'Người đại diện hợp đồng',
            'address' => $tenant->address,
        ];

        $targetStatus = ContractTenant::STATUS_APPROVED;

        if ($representative && (int) $representative->tenant_id === (int) $tenant->id) {
            $representative->forceFill($data)->save();
            if ($representative->status !== $targetStatus) {
                $this->transition(
                    $representative,
                    $targetStatus,
                    'representative_residency_changed',
                    'Người đại diện luôn là người thuê trực tiếp của phòng.',
                    $actor,
                );
            }

            return $representative;
        }
        if ($representative) {
            $this->transition($representative, ContractTenant::STATUS_WITHDRAWN, 'replace_representative', 'Thay đổi người đại diện khi hợp đồng còn là bản nháp.', $actor);
        }

        return $this->createMember($contract, $data + [
            'role' => ContractTenant::ROLE_REPRESENTATIVE,
            'status' => $targetStatus,
            'declared_by' => $actor?->id,
            'reviewed_by' => $actor?->id,
            'reviewed_at' => now(),
            'replaces_contract_tenant_id' => $representative?->id,
        ], $actor, 'set_representative', 'Đồng bộ người đại diện từ hợp đồng.');
    }

    private function createMember(Contract $contract, array $data, ?User $actor, string $action, ?string $reason): ContractTenant
    {
        $member = ContractTenant::query()->create(['contract_id' => $contract->id] + $data);
        $this->history($member, null, $member->status, $action, $reason, $actor);

        return $member;
    }

    private function transition(ContractTenant $member, string $to, string $action, ?string $reason, ?User $actor, array $metadata = []): void
    {
        $from = $member->status;
        $member->forceFill(['status' => $to])->save();
        $this->history($member, $from, $to, $action, $reason, $actor, $metadata);
    }

    private function history(ContractTenant $member, ?string $from, string $to, string $action, ?string $reason, ?User $actor, array $metadata = []): void
    {
        ContractTenantHistory::query()->create([
            'contract_tenant_id' => $member->id,
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

    private function profileMatches(ContractTenant $member, array $data): bool
    {
        return collect(Arr::only($member->getAttributes(), array_keys($data)))
            ->map(fn ($value) => $value === null ? null : (string) $value)
            ->all() === collect($data)->map(fn ($value) => $value === null ? null : (string) $value)->all();
    }

    private function resolveTenantProfile(array $profile): Tenant
    {
        $identityNumber = $profile['identity_number'] ?? null;
        $phone = $profile['phone'] ?? null;
        if (! filled($identityNumber) || ! filled($phone)) {
            $this->fail('members', 'Mỗi người thuê phải có đầy đủ CCCD và số điện thoại.');
        }

        $tenant = Tenant::query()->where('cccd', $identityNumber)->lockForUpdate()->first();
        if ($tenant) {
            if ($tenant->date_of_birth?->toDateString() !== $profile['date_of_birth']) {
                $this->fail('identity_number', 'Ngày sinh không khớp với hồ sơ khách thuê có cùng CCCD.');
            }

            $phoneOwner = Tenant::query()
                ->where('phone', $phone)
                ->whereKeyNot($tenant->id)
                ->lockForUpdate()
                ->exists();
            if ($phoneOwner) {
                $this->fail('phone', 'Số điện thoại đã thuộc hồ sơ khách thuê khác.');
            }

            if ($tenant->user_id && $tenant->phone !== $phone) {
                $this->fail('phone', 'Số điện thoại không khớp với hồ sơ khách thuê đã có tài khoản.');
            }

            if (! $tenant->user_id) {
                $tenant->forceFill([
                    'full_name' => $profile['full_name'],
                    'phone' => $phone,
                ])->save();
            }

            return $tenant;
        }

        if (Tenant::query()->where('phone', $phone)->lockForUpdate()->exists()) {
            $this->fail('phone', 'Số điện thoại đã thuộc hồ sơ khách thuê khác.');
        }

        return Tenant::query()->create([
            'user_id' => null,
            'full_name' => $profile['full_name'],
            'date_of_birth' => $profile['date_of_birth'],
            'cccd' => $identityNumber,
            'phone' => $phone,
        ]);
    }

    private function ensureCapacity(Contract $contract, int $additional = 0): void
    {
        $planned = ContractTenant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->count();
        if ($planned + $additional > (int) $contract->room->max_people) {
            $this->fail('members', 'Danh sách người thuê vượt quá sức chứa tối đa của phòng.');
        }
    }

    private function syncPlannedCount(Contract $contract): void
    {
        $count = ContractTenant::query()->where('contract_id', $contract->id)->current()->count();
        $contract->forceFill(['number_of_people' => $count])->save();
    }

    private function syncCounts(Contract $contract): void
    {
        if (in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
            $count = ContractTenant::query()->where('contract_id', $contract->id)
                ->where('status', ContractTenant::STATUS_CHECKED_IN)->count();
            $contract->forceFill(['number_of_people' => $count])->save();
            $contract->room()->lockForUpdate()->firstOrFail()->forceFill(['current_people' => $count])->save();
        } else {
            $this->syncPlannedCount($contract);
        }
    }

    private function ensureContractOwner(Contract $contract, User $actor): void
    {
        if (! $contract->isManagedBy($actor)) {
            abort(404);
        }
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

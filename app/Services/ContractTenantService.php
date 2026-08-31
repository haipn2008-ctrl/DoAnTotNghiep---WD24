<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractRepresentativeTransfer;
use App\Models\ContractTenant;
use App\Models\ContractTenantHistory;
use App\Models\Role;
use App\Models\TemporaryResidence;
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
            $old = filled($payload['id'] ?? null) ? $existing->get((int) $payload['id']) : null;
            $selectedTenant = filled($payload['tenant_id'] ?? null)
                ? Tenant::query()->lockForUpdate()->findOrFail((int) $payload['tenant_id'])
                : null;
            if ($selectedTenant) {
                if ($selectedTenant->status !== Tenant::STATUS_ACTIVE) {
                    $this->fail('members', 'Khách thuê đã chọn đang bị lưu trữ.');
                }
                $conflictingMembership = ContractTenant::query()
                    ->where('tenant_id', $selectedTenant->id)
                    ->when($old, fn ($query) => $query->whereKeyNot($old->id))
                    ->current()
                    ->lockForUpdate()
                    ->exists();
                if ($conflictingMembership) {
                    $this->fail('members', $selectedTenant->full_name.' đang thuộc hợp đồng hoặc danh sách chờ khác.');
                }
                $profile = $this->profileFromTenant($selectedTenant);
            } else {
                $profile = $this->profileData($payload);
            }
            $data = $this->membershipData($profile);
            $data['tenant_id'] = ($selectedTenant ?? $this->resolveTenantProfile($profile, $old?->tenant))->id;

            if ($old) {
                $retainedIds[] = $old->id;
                $before = Arr::only($old->getAttributes(), array_keys($data));
                $old->forceFill($data);
                if ($old->isDirty(array_keys($data))) {
                    $old->save();
                    $this->history(
                        $old,
                        $old->status,
                        $old->status,
                        'admin_update_profile',
                        'Admin cập nhật hồ sơ người thuê trong bản nháp.',
                        $actor,
                        ['before' => $before, 'after' => $data],
                    );
                }
                if ($old->status === ContractTenant::STATUS_PENDING) {
                    $this->transition($old, ContractTenant::STATUS_APPROVED, 'admin_approve', 'Admin xác nhận trong bản nháp hợp đồng.', $actor);
                }

                continue;
            }

            $created = $this->createMember($contract, $data + [
                'role' => ContractTenant::ROLE_TENANT,
                'status' => ContractTenant::STATUS_APPROVED,
                'declared_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'replaces_contract_tenant_id' => null,
                'identity_front_path' => null,
                'identity_back_path' => null,
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
            $membership = $this->membershipData($profile);
            $membership['tenant_id'] = $this->resolveTenantProfile($profile)->id;
            if (filled($membership['identity_number']) && ContractTenant::query()
                ->where('contract_id', $contract->id)
                ->current()
                ->where('identity_number', $membership['identity_number'])
                ->lockForUpdate()
                ->exists()) {
                $this->fail('identity_number', 'CCCD/giấy tờ này đã có trong danh sách hiện tại.');
            }

            $member = $this->createMember($contract, $membership + [
                'role' => ContractTenant::ROLE_TENANT,
                'status' => ContractTenant::STATUS_PENDING,
                'declared_by' => $actor->id,
            ], $actor, 'tenant_declare', 'Người thuê đại diện gửi hồ sơ người thuê để admin duyệt.');
            $this->syncPlannedCount($contract);

            return $member->fresh();
        }, 3);
    }

    public function addByAdmin(
        Contract $contract,
        User $actor,
        Carbon|string $moveInAt,
        ?Tenant $existingTenant = null,
        array $newProfile = [],
    ): ContractTenant {
        return DB::transaction(function () use ($contract, $actor, $moveInAt, $existingTenant, $newProfile): ContractTenant {
            if (! $actor->isAdmin()) {
                $this->fail('member', 'Chỉ quản trị viên được thêm trực tiếp người thuê vào phòng.');
            }

            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($contract->id);
            if (! in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)
                || ! $contract->actual_move_in_at) {
                $this->fail('contract', 'Chỉ được thêm người vào hợp đồng đã nhận phòng và đang còn hiệu lực.');
            }

            $moveInAt = Carbon::parse($moveInAt);
            if ($moveInAt->isFuture() || $moveInAt->lt($contract->actual_move_in_at)) {
                $this->fail('actual_move_in_at', 'Thời điểm vào ở phải từ lúc hợp đồng nhận phòng đến hiện tại.');
            }

            $this->ensureCapacity($contract, 1);

            if ($existingTenant) {
                $tenant = Tenant::query()->lockForUpdate()->findOrFail($existingTenant->id);
                if ($tenant->status !== Tenant::STATUS_ACTIVE) {
                    $this->fail('tenant_id', 'Hồ sơ khách thuê đã bị lưu trữ, không thể thêm vào phòng.');
                }
                $profile = [
                    'full_name' => $tenant->full_name,
                    'date_of_birth' => $tenant->date_of_birth?->toDateString(),
                    'gender' => $tenant->gender,
                    'identity_number' => $tenant->cccd,
                    'cccd_issue_date' => $tenant->cccd_issue_date?->toDateString(),
                    'cccd_issue_place' => $tenant->cccd_issue_place,
                    'phone' => $tenant->phone,
                    'email' => $tenant->email,
                    'address' => $tenant->address,
                ];
                $this->ensureCompleteAdminProfile($profile, 'tenant_id');
            } else {
                $profile = $this->profileData($newProfile);
                $this->ensureCompleteAdminProfile($profile);

                $duplicate = Tenant::query()
                    ->where('cccd', $profile['identity_number'])
                    ->orWhere('phone', $profile['phone'])
                    ->when(filled($profile['email']), fn ($query) => $query->orWhere('email', $profile['email']))
                    ->lockForUpdate()
                    ->first();
                if ($duplicate) {
                    $this->fail('identity_number', 'Khách thuê đã có hồ sơ trên hệ thống. Hãy chọn mục “Khách có sẵn”.');
                }

                $tenant = $this->resolveTenantProfile($profile);
            }

            $hasCurrentMembership = ContractTenant::query()
                ->where('tenant_id', $tenant->id)
                ->current()
                ->lockForUpdate()
                ->exists();
            $hasOpenRepresentativeContract = Contract::query()
                ->where(fn ($query) => $query
                    ->where('tenant_id', $tenant->id)
                    ->orWhere('representative_tenant_id', $tenant->id))
                ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
                ->lockForUpdate()
                ->exists();
            if ($hasCurrentMembership || $hasOpenRepresentativeContract) {
                $this->fail('tenant_id', 'Khách này đang thuộc một phòng hoặc danh sách chờ khác. Hãy dùng quy trình chuyển phòng.');
            }

            $member = $this->createMember($contract, $this->membershipData($profile) + [
                'tenant_id' => $tenant->id,
                'role' => ContractTenant::ROLE_TENANT,
                'relationship' => 'Người ở cùng',
                'status' => ContractTenant::STATUS_CHECKED_IN,
                'declared_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'actual_move_in_at' => $moveInAt,
            ], $actor, 'admin_add_checked_in_member', 'Admin thêm trực tiếp người thuê vào phòng đang hoạt động.');

            $this->syncCounts($contract);

            return $member->fresh(['tenant']);
        }, 3);
    }

    public function withdrawByTenant(ContractTenant $member, User $actor): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor): ContractTenant {
            $member = ContractTenant::query()->with('contract')->lockForUpdate()->findOrFail($member->id);
            $this->ensureContractOwner($member->contract, $actor);
            if ($member->role === ContractTenant::ROLE_REPRESENTATIVE
                || ! in_array($member->status, [ContractTenant::STATUS_PENDING, ContractTenant::STATUS_APPROVED], true)
                || $member->actual_move_in_at) {
                $this->fail('member', 'Chỉ có thể rút người thuê đang chờ duyệt hoặc đã duyệt nhưng chưa nhận phòng.');
            }

            $wasApproved = $member->status === ContractTenant::STATUS_APPROVED;
            $this->transition(
                $member,
                ContractTenant::STATUS_WITHDRAWN,
                $wasApproved ? 'tenant_withdraw_approved' : 'tenant_withdraw',
                $wasApproved
                    ? 'Người thuê đã được duyệt đổi ý trước khi nhận phòng.'
                    : 'Người thuê đại diện rút hồ sơ đang chờ duyệt.',
                $actor,
            );
            $this->syncPlannedCount($member->contract);

            return $member->fresh();
        }, 3);
    }

    public function updateBeforeMoveIn(ContractTenant $member, User $actor, array $data): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor, $data): ContractTenant {
            $member = ContractTenant::query()->with(['contract', 'tenant'])->lockForUpdate()->findOrFail($member->id);
            $this->ensureContractOwner($member->contract, $actor);
            if ($member->role === ContractTenant::ROLE_REPRESENTATIVE
                || ! in_array($member->status, [ContractTenant::STATUS_PENDING, ContractTenant::STATUS_APPROVED], true)
                || ! in_array($member->contract->status, [
                    Contract::STATUS_PENDING_SIGNATURE,
                    Contract::STATUS_PENDING_DEPOSIT,
                    Contract::STATUS_AWAITING_MOVE_IN,
                ], true)) {
                $this->fail('member', 'Chỉ được bổ sung hồ sơ người ở cùng trước khi nhận phòng.');
            }

            $profile = $this->profileData($data);
            $duplicateMember = ContractTenant::query()
                ->where('contract_id', $member->contract_id)
                ->whereKeyNot($member->id)
                ->current()
                ->where('identity_number', $profile['identity_number'])
                ->lockForUpdate()
                ->exists();
            if ($duplicateMember) {
                $this->fail('identity_number', 'CCCD này đã có trong danh sách người thuê của hợp đồng.');
            }
            if (Tenant::query()->where('cccd', $profile['identity_number'])->whereKeyNot($member->tenant_id)->exists()) {
                $this->fail('identity_number', 'CCCD này đang thuộc một hồ sơ khách thuê khác.');
            }

            $wasComplete = $member->hasCompleteMoveInProfile();
            $member->tenant->forceFill([
                'full_name' => $profile['full_name'],
                'date_of_birth' => $profile['date_of_birth'],
                'gender' => $profile['gender'],
                'cccd' => $profile['identity_number'],
                'cccd_issue_date' => $profile['cccd_issue_date'],
                'cccd_issue_place' => $profile['cccd_issue_place'],
                'phone' => $profile['phone'],
                'email' => $profile['email'],
                'address' => $profile['address'],
            ])->save();
            $member->forceFill($this->membershipData($profile))->save();
            $this->history(
                $member,
                $member->status,
                $member->status,
                $wasComplete ? 'tenant_update_profile_before_move_in' : 'tenant_complete_profile_before_move_in',
                'Người thuê đại diện cập nhật hồ sơ trước khi nhận phòng.',
                $actor,
            );

            return $member->fresh(['tenant']);
        }, 3);
    }

    public function incompleteMoveInProfiles(Contract $contract)
    {
        return ContractTenant::query()
            ->where('contract_id', $contract->id)
            ->current()
            ->with('tenant')
            ->get()
            ->filter(fn (ContractTenant $member): bool => ! $member->hasCompleteMoveInProfile())
            ->values();
    }

    public function ensureReadyForMoveIn(Contract $contract): void
    {
        $members = ContractTenant::query()
            ->where('contract_id', $contract->id)
            ->current()
            ->with('tenant')
            ->lockForUpdate()
            ->get();
        if ($members->contains('status', ContractTenant::STATUS_PENDING)) {
            $this->fail('members', 'Còn người thuê đang chờ duyệt. Hãy duyệt hoặc từ chối trước khi nhận phòng.');
        }

        $incomplete = $members->filter(fn (ContractTenant $member): bool => ! $member->hasCompleteMoveInProfile());
        if ($incomplete->isNotEmpty()) {
            $details = $incomplete->map(function (ContractTenant $member): string {
                return $member->full_name.' (thiếu '.implode(', ', $member->missingMoveInProfileFields()).')';
            })->implode('; ');
            $this->fail('members', 'Chưa thể nhận phòng vì hồ sơ người thuê chưa đầy đủ: '.$details.'.');
        }
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
            $this->fail('members', 'Còn người thuê đang chờ duyệt. Hãy duyệt hoặc từ chối trước khi nhận phòng.');
        }

        $approved = $members->where('status', ContractTenant::STATUS_APPROVED);
        if ($approved->isEmpty()) {
            $this->fail('members', 'Phải có ít nhất một người thuê được duyệt trước khi nhận phòng.');
        }
        $this->ensureReadyForMoveIn($contract);
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
            TemporaryResidence::query()->where('contract_tenant_id', $member->id)
                ->whereIn('status', ['pending', 'active'])->update(['status' => 'expired']);
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
            if ($member->role === ContractTenant::ROLE_REPRESENTATIVE) {
                $this->fail('member', 'Người đại diện phải được chuyển giao vai trò cho một người đang thuê khác khi rời phòng.');
            }
            $moveOutAt = Carbon::parse($moveOutAt);
            if ($moveOutAt->isFuture() || ($member->actual_move_in_at && $moveOutAt->lt($member->actual_move_in_at))) {
                $this->fail('actual_move_out_at', 'Thời điểm rời phòng không hợp lệ.');
            }

            $temporaryResidences = TemporaryResidence::query()
                ->where('contract_tenant_id', $member->id)
                ->whereIn('status', ['pending', 'active'])
                ->lockForUpdate()
                ->get(['id', 'status'])
                ->map(fn (TemporaryResidence $residence): array => [
                    'id' => $residence->id,
                    'status' => $residence->status,
                ])->values()->all();

            $member->forceFill(['actual_move_out_at' => $moveOutAt])->save();
            $this->transition($member, ContractTenant::STATUS_MOVED_OUT, 'tenant_move_out', $reason, $actor, [
                'actual_move_out_at' => $moveOutAt->toIso8601String(),
                'temporary_residences' => $temporaryResidences,
            ]);
            TemporaryResidence::query()->where('contract_tenant_id', $member->id)
                ->whereIn('status', ['pending', 'active'])->update(['status' => 'expired']);
            $this->syncCounts($member->contract()->with('room')->firstOrFail());

            return $member->fresh();
        }, 3);
    }

    public function restoreMoveOut(ContractTenant $member, User $actor, string $reason): ContractTenant
    {
        return DB::transaction(function () use ($member, $actor, $reason): ContractTenant {
            $member = ContractTenant::query()->lockForUpdate()->findOrFail($member->id);
            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($member->contract_id);

            if (! in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
                $this->fail('member', 'Chỉ có thể khôi phục thành viên khi hợp đồng vẫn đang thuê hoặc quá hạn chưa trả phòng.');
            }
            if ($member->role !== ContractTenant::ROLE_TENANT
                || $member->status !== ContractTenant::STATUS_MOVED_OUT
                || ! $member->actual_move_in_at) {
                $this->fail('member', 'Hồ sơ này không phải thành viên đã rời phòng riêng lẻ.');
            }

            $moveOutHistory = ContractTenantHistory::query()
                ->where('contract_tenant_id', $member->id)
                ->where('to_status', ContractTenant::STATUS_MOVED_OUT)
                ->latest('performed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if (! $moveOutHistory || $moveOutHistory->action !== 'tenant_move_out') {
                $this->fail('member', 'Không thể hoàn tác trường hợp trả phòng toàn hợp đồng hoặc chuyển người đại diện.');
            }

            $this->ensureCapacity($contract, 1);
            $restoredResidences = [];
            foreach ((array) data_get($moveOutHistory->metadata, 'temporary_residences', []) as $snapshot) {
                $previousStatus = data_get($snapshot, 'status');
                if (! in_array($previousStatus, ['pending', 'active'], true)) {
                    continue;
                }

                $residence = TemporaryResidence::query()
                    ->whereKey(data_get($snapshot, 'id'))
                    ->where('contract_tenant_id', $member->id)
                    ->where('status', 'expired')
                    ->lockForUpdate()
                    ->first();
                if (! $residence) {
                    continue;
                }

                $restoredStatus = $previousStatus === 'active'
                    && $residence->end_date
                    && $residence->end_date->lt(today())
                        ? 'expired'
                        : $previousStatus;
                if ($restoredStatus !== 'expired') {
                    $residence->forceFill(['status' => $restoredStatus])->save();
                    $restoredResidences[] = $residence->id;
                }
            }

            $incorrectMoveOutAt = $member->actual_move_out_at?->toIso8601String();
            $member->forceFill(['actual_move_out_at' => null])->save();
            $this->transition($member, ContractTenant::STATUS_CHECKED_IN, 'tenant_move_out_reverted', $reason, $actor, [
                'reverted_history_id' => $moveOutHistory->id,
                'incorrect_actual_move_out_at' => $incorrectMoveOutAt,
                'restored_temporary_residence_ids' => $restoredResidences,
            ]);
            $this->syncCounts($contract);

            return $member->fresh();
        }, 3);
    }

    public function transferRepresentative(
        ContractTenant $representative,
        ContractTenant $successor,
        User $actor,
        Carbon|string $effectiveAt,
        string $reason,
        string $email,
        string $temporaryPassword,
    ): ContractRepresentativeTransfer {
        return DB::transaction(function () use ($representative, $successor, $actor, $effectiveAt, $reason, $email, $temporaryPassword): ContractRepresentativeTransfer {
            $representative = ContractTenant::query()->lockForUpdate()->findOrFail($representative->id);
            $contract = Contract::query()->with('room')->lockForUpdate()->findOrFail($representative->contract_id);
            if (! in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
                $this->fail('member', 'Chỉ được chuyển người đại diện khi hợp đồng đang thuê hoặc quá hạn chưa trả phòng.');
            }
            if ($representative->role !== ContractTenant::ROLE_REPRESENTATIVE
                || $representative->status !== ContractTenant::STATUS_CHECKED_IN) {
                $this->fail('member', 'Người đại diện hiện tại không còn ở trạng thái đang thuê.');
            }

            $successor = ContractTenant::query()->lockForUpdate()->findOrFail($successor->id);
            if ((int) $successor->contract_id !== (int) $contract->id
                || $successor->role !== ContractTenant::ROLE_TENANT
                || $successor->status !== ContractTenant::STATUS_CHECKED_IN
                || ! $successor->tenant_id) {
                $this->fail('successor_member_id', 'Người đại diện mới phải là người thuê đang ở trong cùng phòng.');
            }

            $effectiveAt = Carbon::parse($effectiveAt);
            if ($effectiveAt->isFuture()
                || ($representative->actual_move_in_at && $effectiveAt->lt($representative->actual_move_in_at))
                || ($successor->actual_move_in_at && $effectiveAt->lt($successor->actual_move_in_at))) {
                $this->fail('effective_at', 'Thời điểm chuyển giao không hợp lệ.');
            }

            $oldTenant = Tenant::query()->with('user')->lockForUpdate()->findOrFail($representative->tenant_id);
            $newTenant = Tenant::query()->with('user')->lockForUpdate()->findOrFail($successor->tenant_id);
            if ($newTenant->user) {
                $this->fail('successor_member_id', 'Người thuê được chọn đã có tài khoản. Hãy xử lý tài khoản hiện có trước khi chuyển giao.');
            }

            $email = mb_strtolower(trim($email));
            if (User::query()->where('email', $email)->lockForUpdate()->exists()) {
                $this->fail('email', 'Email đăng nhập đã được sử dụng.');
            }
            $clientRole = Role::query()->where('role_name', 'User')->first();
            if (! $clientRole) {
                $this->fail('email', 'Hệ thống chưa có vai trò tài khoản khách thuê.');
            }

            $newUser = User::query()->create([
                'name' => $newTenant->full_name,
                'email' => $email,
                'phone' => $newTenant->phone,
                'role_id' => $clientRole->id,
                'password' => $temporaryPassword,
                'status' => User::STATUS_PENDING,
                'must_change_password' => true,
                'activated_at' => null,
            ]);
            $newTenant->forceFill(['user_id' => $newUser->id, 'email' => $email])->save();

            $oldUser = $oldTenant->user;
            if ($oldUser) {
                $oldUser->forceFill([
                    'status' => User::STATUS_LOCKED,
                    'must_change_password' => false,
                ])->save();
            }

            $representative->forceFill(['actual_move_out_at' => $effectiveAt])->save();
            $this->transition(
                $representative,
                ContractTenant::STATUS_MOVED_OUT,
                'representative_move_out',
                $reason,
                $actor,
                ['successor_contract_tenant_id' => $successor->id],
            );

            $successor->forceFill(['role' => ContractTenant::ROLE_REPRESENTATIVE])->save();
            $this->history(
                $successor,
                ContractTenant::STATUS_CHECKED_IN,
                ContractTenant::STATUS_CHECKED_IN,
                'promote_to_representative',
                $reason,
                $actor,
                ['previous_role' => ContractTenant::ROLE_TENANT, 'new_role' => ContractTenant::ROLE_REPRESENTATIVE],
            );

            $contract->forceFill([
                'tenant_id' => $newTenant->id,
                'representative_tenant_id' => $newTenant->id,
            ])->save();
            $this->syncCounts($contract);

            $transfer = ContractRepresentativeTransfer::query()->create([
                'contract_id' => $contract->id,
                'old_contract_tenant_id' => $representative->id,
                'new_contract_tenant_id' => $successor->id,
                'old_tenant_id' => $oldTenant->id,
                'new_tenant_id' => $newTenant->id,
                'old_user_id' => $oldUser?->id,
                'new_user_id' => $newUser->id,
                'performed_by' => $actor->id,
                'effective_at' => $effectiveAt,
                'reason' => $reason,
                'deposit_amount_snapshot' => $contract->deposit_amount,
                'old_representative_snapshot' => $this->representativeSnapshot($oldTenant, $representative),
                'new_representative_snapshot' => $this->representativeSnapshot($newTenant, $successor),
            ]);

            ContractHistoryService::log(
                $contract,
                'representative_transferred',
                'Đã lập phụ lục chuyển giao người đại diện thuê phòng.',
                $reason,
                ['tenant_id' => $oldTenant->id, 'user_id' => $oldUser?->id],
                ['tenant_id' => $newTenant->id, 'user_id' => $newUser->id, 'transfer_id' => $transfer->id],
                $actor->id,
            );

            return $transfer->fresh(['contract', 'oldTenant', 'newTenant', 'newUser']);
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
            'relationship' => 'Người thuê đại diện của hợp đồng',
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
                    'Người thuê đại diện luôn thuộc danh sách người thuê trực tiếp của phòng.',
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
            'gender' => $data['gender'] ?? null,
            'identity_number' => filled($data['identity_number'] ?? null) ? trim((string) $data['identity_number']) : null,
            'cccd_issue_date' => $data['cccd_issue_date'] ?? null,
            'cccd_issue_place' => filled($data['cccd_issue_place'] ?? null) ? trim((string) $data['cccd_issue_place']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'email' => filled($data['email'] ?? null) ? mb_strtolower(trim((string) $data['email'])) : null,
            'address' => filled($data['address'] ?? null) ? trim((string) $data['address']) : null,
        ];
    }

    private function membershipData(array $profile): array
    {
        return Arr::only($profile, [
            'full_name', 'date_of_birth', 'identity_number', 'phone', 'address',
        ]);
    }

    private function profileFromTenant(Tenant $tenant): array
    {
        return [
            'full_name' => $tenant->full_name,
            'date_of_birth' => $tenant->date_of_birth?->toDateString(),
            'gender' => $tenant->gender,
            'identity_number' => $tenant->cccd,
            'cccd_issue_date' => $tenant->cccd_issue_date?->toDateString(),
            'cccd_issue_place' => $tenant->cccd_issue_place,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
            'address' => $tenant->address,
        ];
    }

    private function ensureCompleteAdminProfile(array $profile, string $errorKey = 'full_name'): void
    {
        $required = [
            'full_name', 'date_of_birth', 'gender', 'identity_number',
            'cccd_issue_date', 'cccd_issue_place', 'phone', 'address',
        ];
        if (collect($required)->contains(fn (string $field): bool => blank($profile[$field] ?? null))) {
            $this->fail($errorKey, 'Hồ sơ khách thuê chưa đầy đủ. Hãy bổ sung thông tin cá nhân và CCCD trước khi thêm vào phòng.');
        }
        if (Carbon::parse($profile['date_of_birth'])->gt(today()->subYears(18))) {
            $this->fail($errorKey, 'Người thuê phải đủ 18 tuổi.');
        }
    }

    private function representativeSnapshot(Tenant $tenant, ContractTenant $member): array
    {
        return [
            'full_name' => $member->full_name,
            'date_of_birth' => $member->date_of_birth?->toDateString(),
            'identity_number' => $member->identity_number,
            'phone' => $member->phone,
            'email' => $tenant->email,
            'address' => $member->address,
        ];
    }

    private function resolveTenantProfile(array $profile, ?Tenant $existingTenant = null): Tenant
    {
        $identityNumber = $profile['identity_number'] ?? null;
        $phone = $profile['phone'] ?? null;
        $tenant = $existingTenant;
        $identityOwner = filled($identityNumber)
            ? Tenant::query()->where('cccd', $identityNumber)->lockForUpdate()->first()
            : null;
        if ($tenant && $identityOwner && ! $identityOwner->is($tenant)) {
            $this->fail('identity_number', 'CCCD đã thuộc hồ sơ khách thuê khác.');
        }
        $tenant ??= $identityOwner;
        if (! $tenant && filled($phone)) {
            $tenant = Tenant::query()->where('phone', $phone)->lockForUpdate()->first();
        }

        if ($tenant) {
            if (filled($profile['date_of_birth']) && filled($tenant->date_of_birth)
                && $tenant->date_of_birth->toDateString() !== $profile['date_of_birth']) {
                $this->fail('identity_number', 'Ngày sinh không khớp với hồ sơ khách thuê có cùng CCCD.');
            }

            $phoneOwner = filled($phone) && Tenant::query()
                ->where('phone', $phone)
                ->whereKeyNot($tenant->id)
                ->lockForUpdate()
                ->exists();
            if ($phoneOwner) {
                $this->fail('phone', 'Số điện thoại đã thuộc hồ sơ khách thuê khác.');
            }

            $emailOwner = filled($profile['email']) && Tenant::query()
                ->where('email', $profile['email'])
                ->whereKeyNot($tenant->id)
                ->lockForUpdate()
                ->exists();
            if ($emailOwner) {
                $this->fail('email', 'Email đã thuộc hồ sơ khách thuê khác.');
            }

            if ($tenant->user_id && filled($phone) && $tenant->phone !== $phone) {
                $this->fail('phone', 'Số điện thoại không khớp với hồ sơ khách thuê đã có tài khoản.');
            }

            if (! $tenant->user_id) {
                $tenant->forceFill([
                    'full_name' => $profile['full_name'],
                    'date_of_birth' => $profile['date_of_birth'],
                    'gender' => $profile['gender'],
                    'cccd' => $identityNumber,
                    'cccd_issue_date' => $profile['cccd_issue_date'],
                    'cccd_issue_place' => $profile['cccd_issue_place'],
                    'phone' => $phone,
                    'email' => $profile['email'],
                    'address' => $profile['address'],
                ])->save();
            }

            return $tenant;
        }

        if (filled($phone) && Tenant::query()->where('phone', $phone)->lockForUpdate()->exists()) {
            $this->fail('phone', 'Số điện thoại đã thuộc hồ sơ khách thuê khác.');
        }
        if (filled($profile['email']) && Tenant::query()->where('email', $profile['email'])->lockForUpdate()->exists()) {
            $this->fail('email', 'Email đã thuộc hồ sơ khách thuê khác.');
        }

        return Tenant::query()->create([
            'user_id' => null,
            'full_name' => $profile['full_name'],
            'date_of_birth' => $profile['date_of_birth'],
            'gender' => $profile['gender'],
            'cccd' => $identityNumber,
            'cccd_issue_date' => $profile['cccd_issue_date'],
            'cccd_issue_place' => $profile['cccd_issue_place'],
            'phone' => $phone,
            'email' => $profile['email'],
            'address' => $profile['address'],
        ]);
    }

    private function ensureCapacity(Contract $contract, int $additional = 0): void
    {
        $room = $contract->room()->lockForUpdate()->firstOrFail();
        $planned = ContractTenant::query()->where('contract_id', $contract->id)->current()->lockForUpdate()->count();
        $currentOccupancy = max($planned, (int) $contract->number_of_people, (int) $room->current_people);
        if ($currentOccupancy + $additional > (int) $room->max_people) {
            $this->fail('members', "Phòng {$room->room_code} đã đủ {$room->max_people} người, không thể gửi thêm yêu cầu.");
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

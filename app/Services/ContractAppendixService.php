<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractAppendixService
{
    public function __construct(private readonly ContractRateResolver $pricing) {}

    private const ELIGIBLE_STATUSES = [
        Contract::STATUS_PENDING_DEPOSIT,
        Contract::STATUS_AWAITING_MOVE_IN,
        Contract::STATUS_ACTIVE,
        Contract::STATUS_EXPIRED,
    ];

    public function canCreateDraft(Contract $contract): bool
    {
        return (bool) $contract->signed_at
            && in_array($contract->status, self::ELIGIBLE_STATUSES, true)
            && ! $contract->appendices()->whereIn('status', [
                ContractAppendix::STATUS_DRAFT,
                ContractAppendix::STATUS_PENDING_TENANT,
                ContractAppendix::STATUS_PENDING_SIGNATURE,
            ])->exists();
    }

    public function createDraft(Contract $contract, array $data, User $actor): ContractAppendix
    {
        return DB::transaction(function () use ($contract, $data, $actor): ContractAppendix {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $this->ensureEligible($contract);
            $this->ensureNoOpenAppendix($contract);
            $number = ((int) $contract->appendices()->max('appendix_number')) + 1;
            $priceAdjustments = $this->pricing->prepareAdjustments(
                $contract,
                $data['title'],
                $data['effective_from'],
                $data['price_adjustments'] ?? []
            );

            return ContractAppendix::query()->create([
                'contract_id' => $contract->id,
                'appendix_number' => $number,
                'revision' => 1,
                'code' => $this->code($contract, $number, 1),
                'title' => $data['title'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'content' => $data['content'],
                'price_adjustments' => $priceAdjustments,
                'effective_from' => $data['effective_from'],
                'status' => ContractAppendix::STATUS_DRAFT,
                'created_by' => $actor->id,
            ]);
        }, 3);
    }

    public function updateDraft(ContractAppendix $appendix, array $data): ContractAppendix
    {
        return DB::transaction(function () use ($appendix, $data): ContractAppendix {
            $appendix = ContractAppendix::query()->lockForUpdate()->findOrFail($appendix->id);
            if ($appendix->status !== ContractAppendix::STATUS_DRAFT) {
                $this->fail('appendix', 'Chỉ phụ lục nháp mới có thể chỉnh sửa.');
            }
            $contract = Contract::query()->findOrFail($appendix->contract_id);
            $this->ensureEligible($contract);
            $priceAdjustments = $this->pricing->prepareAdjustments(
                $contract,
                $data['title'],
                $data['effective_from'],
                $data['price_adjustments'] ?? []
            );
            $appendix->fill([
                'title' => $data['title'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'content' => $data['content'],
                'price_adjustments' => $priceAdjustments,
                'effective_from' => $data['effective_from'],
            ])->save();

            return $appendix->fresh();
        }, 3);
    }

    public function send(ContractAppendix $appendix, User $actor): ContractAppendix
    {
        return DB::transaction(function () use ($appendix, $actor): ContractAppendix {
            $appendix = ContractAppendix::query()->with('contract')->lockForUpdate()->findOrFail($appendix->id);
            if ($appendix->status !== ContractAppendix::STATUS_DRAFT) {
                $this->fail('appendix', 'Phụ lục này không còn ở trạng thái bản nháp.');
            }
            $this->ensureEligible($appendix->contract);
            $appendix->forceFill([
                'status' => ContractAppendix::STATUS_PENDING_TENANT,
                'sent_at' => now(),
                'sent_by' => $actor->id,
                'content_sha256' => hash('sha256', $appendix->hashPayload()),
            ])->save();

            return $appendix->fresh('contract');
        }, 3);
    }

    public function revise(ContractAppendix $appendix, User $actor): ContractAppendix
    {
        return DB::transaction(function () use ($appendix, $actor): ContractAppendix {
            $appendix = ContractAppendix::query()->with('contract')->lockForUpdate()->findOrFail($appendix->id);
            if ($appendix->status !== ContractAppendix::STATUS_REJECTED) {
                $this->fail('appendix', 'Chỉ phụ lục bị khách từ chối mới có thể tạo bản sửa đổi.');
            }
            $contract = Contract::query()->lockForUpdate()->findOrFail($appendix->contract_id);
            $this->ensureEligible($contract);
            $this->ensureNoOpenAppendix($contract);
            $revision = ((int) ContractAppendix::query()
                ->where('contract_id', $contract->id)
                ->where('appendix_number', $appendix->appendix_number)
                ->max('revision')) + 1;

            $revised = ContractAppendix::query()->create([
                'contract_id' => $contract->id,
                'parent_appendix_id' => $appendix->id,
                'appendix_number' => $appendix->appendix_number,
                'revision' => $revision,
                'code' => $this->code($contract, $appendix->appendix_number, $revision),
                'title' => $appendix->title,
                'legal_basis' => $appendix->legal_basis,
                'content' => $appendix->content,
                'price_adjustments' => $appendix->price_adjustments,
                'effective_from' => $appendix->effective_from,
                'status' => ContractAppendix::STATUS_DRAFT,
                'created_by' => $actor->id,
            ]);
            $appendix->forceFill(['status' => ContractAppendix::STATUS_SUPERSEDED])->save();

            return $revised;
        }, 3);
    }

    public function accept(ContractAppendix $appendix, User $actor): ContractAppendix
    {
        return DB::transaction(function () use ($appendix, $actor): ContractAppendix {
            $appendix = ContractAppendix::query()->lockForUpdate()->findOrFail($appendix->id);
            if ($appendix->status !== ContractAppendix::STATUS_PENDING_TENANT) {
                $this->fail('appendix', 'Phụ lục này không còn chờ xác nhận.');
            }
            if (! $appendix->hasValidContentHash()) {
                $this->fail('appendix', 'Nội dung phụ lục không vượt qua kiểm tra toàn vẹn.');
            }
            $appendix->forceFill([
                'status' => ContractAppendix::STATUS_ACCEPTED,
                'responded_at' => now(),
                'responded_by' => $actor->id,
                'accepted_at' => now(),
            ])->save();

            return $appendix->fresh('contract');
        }, 3);
    }

    public function reject(ContractAppendix $appendix, User $actor, string $reason): ContractAppendix
    {
        return DB::transaction(function () use ($appendix, $actor, $reason): ContractAppendix {
            $appendix = ContractAppendix::query()->lockForUpdate()->findOrFail($appendix->id);
            if ($appendix->status !== ContractAppendix::STATUS_PENDING_TENANT) {
                $this->fail('appendix', 'Phụ lục này không còn chờ xác nhận.');
            }
            if (! $appendix->hasValidContentHash()) {
                $this->fail('appendix', 'Nội dung phụ lục không vượt qua kiểm tra toàn vẹn.');
            }
            $appendix->forceFill([
                'status' => ContractAppendix::STATUS_REJECTED,
                'responded_at' => now(),
                'responded_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $appendix->fresh('contract');
        }, 3);
    }

    private function ensureEligible(Contract $contract): void
    {
        if (! $contract->signed_at || ! in_array($contract->status, self::ELIGIBLE_STATUSES, true)) {
            $this->fail('contract', 'Chỉ có thể lập phụ lục cho hợp đồng đã ký và còn hiệu lực xử lý.');
        }
    }

    private function ensureNoOpenAppendix(Contract $contract): void
    {
        if ($contract->appendices()->whereIn('status', [
            ContractAppendix::STATUS_DRAFT,
            ContractAppendix::STATUS_PENDING_TENANT,
            ContractAppendix::STATUS_PENDING_SIGNATURE,
        ])->exists()) {
            $this->fail('contract', 'Hợp đồng đang có một phụ lục nháp hoặc đang chờ khách xác nhận.');
        }
    }

    private function code(Contract $contract, int $number, int $revision): string
    {
        return sprintf('PL-%s-%02d-R%d', $contract->contract_code, $number, $revision);
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

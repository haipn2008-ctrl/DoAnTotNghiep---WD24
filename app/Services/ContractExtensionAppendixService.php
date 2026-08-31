<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractExtensionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractExtensionAppendixService
{
    public function __construct(private readonly ContractLifecycleService $lifecycle) {}

    public function prepare(ContractExtensionRequest $extensionRequest, User $actor): ContractAppendix
    {
        return DB::transaction(function () use ($extensionRequest, $actor): ContractAppendix {
            $request = ContractExtensionRequest::query()->lockForUpdate()->findOrFail($extensionRequest->id);
            $contract = Contract::query()->lockForUpdate()->findOrFail($request->contract_id);

            if ($request->appendix()->exists()) {
                return $request->appendix()->firstOrFail();
            }
            if ($request->status !== ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION) {
                $this->fail('request', 'Yêu cầu chưa ở trạng thái có thể lập phụ lục gia hạn.');
            }
            if ($contract->appendices()->whereIn('status', [
                ContractAppendix::STATUS_DRAFT,
                ContractAppendix::STATUS_PENDING_TENANT,
                ContractAppendix::STATUS_PENDING_SIGNATURE,
            ])->exists()) {
                $this->fail('appendix', 'Hợp đồng đang có một phụ lục chưa hoàn tất. Hãy xử lý phụ lục đó trước.');
            }

            $oldEndDate = $request->current_end_date ?: $contract->end_date;
            $newEndDate = $request->approved_end_date ?: $request->requested_end_date;
            $oldRent = (float) data_get($request->terms_snapshot, 'old_monthly_rent', $contract->monthly_rent);
            $newRent = (float) ($request->proposed_monthly_rent ?? $contract->monthly_rent);
            $number = ((int) $contract->appendices()->max('appendix_number')) + 1;
            $code = sprintf('PL-%s-%02d-R1', $contract->contract_code, $number);
            $content = implode("\n", [
                'Điều 1. Hai bên thống nhất gia hạn thời hạn hợp đồng:',
                '- Thời hạn cũ kết thúc ngày: '.$oldEndDate?->format('d/m/Y').'.',
                '- Thời hạn mới kết thúc ngày: '.$newEndDate?->format('d/m/Y').'.',
                '',
                'Điều 2. Giá thuê phòng trong thời gian gia hạn:',
                '- Giá thuê cũ: '.number_format($oldRent, 0, ',', '.').' VNĐ/tháng.',
                '- Giá thuê áp dụng khi gia hạn: '.number_format($newRent, 0, ',', '.').' VNĐ/tháng.',
                '',
                'Điều 3. Tiền cọc và các điều khoản khác của hợp đồng không thay đổi, trừ nội dung được ghi rõ tại phụ lục này.',
                '',
                'Điều 4. Phụ lục có hiệu lực sau khi hai bên ký và ban quản lý tải minh chứng bản ký lên hệ thống.',
            ]);

            $appendix = ContractAppendix::query()->create([
                'contract_id' => $contract->id,
                'extension_request_id' => $request->id,
                'appendix_number' => $number,
                'revision' => 1,
                'code' => $code,
                'appendix_type' => ContractAppendix::TYPE_EXTENSION,
                'title' => 'Gia hạn thời hạn hợp đồng',
                'legal_basis' => 'Theo đề nghị gia hạn và sự thống nhất điều khoản giữa hai bên.',
                'content' => $content,
                'effective_from' => $oldEndDate?->copy()->addDay(),
                'status' => ContractAppendix::STATUS_PENDING_SIGNATURE,
                'created_by' => $actor->id,
            ]);
            $appendix->forceFill([
                'sent_at' => now(),
                'sent_by' => $actor->id,
                'content_sha256' => hash('sha256', $appendix->hashPayload()),
            ])->save();

            return $appendix->fresh(['contract', 'extensionRequest']);
        }, 3);
    }

    public function finalize(ContractAppendix $appendix, User $actor, array $evidencePaths): Contract
    {
        return DB::transaction(function () use ($appendix, $actor, $evidencePaths): Contract {
            $appendix = ContractAppendix::query()->with('extensionRequest')->lockForUpdate()->findOrFail($appendix->id);
            if (! $appendix->isExtension() || $appendix->status !== ContractAppendix::STATUS_PENDING_SIGNATURE) {
                $this->fail('signed_evidence', 'Phụ lục này không còn chờ tải minh chứng ký.');
            }
            if (! $appendix->hasValidContentHash()) {
                $this->fail('signed_evidence', 'Nội dung phụ lục không vượt qua kiểm tra toàn vẹn SHA-256.');
            }
            $request = ContractExtensionRequest::query()->lockForUpdate()->findOrFail($appendix->extension_request_id);
            if ($request->status !== ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION) {
                $this->fail('request', 'Yêu cầu gia hạn đã được xử lý hoặc không còn hợp lệ.');
            }
            $contract = Contract::query()->lockForUpdate()->findOrFail($appendix->contract_id);
            $newEndDate = $request->approved_end_date ?: $request->requested_end_date;
            $reason = $request->admin_note ?: $request->reason ?: 'Gia hạn theo phụ lục đã ký '.$appendix->code.'.';

            $contract = $this->lifecycle->extendContract($contract, $actor, $newEndDate, $reason, [
                'monthly_rent' => (float) ($request->proposed_monthly_rent ?? $contract->monthly_rent),
                'extension_request_id' => $request->id,
                'appendix_id' => $appendix->id,
                'appendix_code' => $appendix->code,
            ]);
            $request->forceFill([
                'status' => ContractExtensionRequest::STATUS_APPROVED,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ])->save();
            $appendix->forceFill([
                'status' => ContractAppendix::STATUS_ACCEPTED,
                'responded_at' => now(),
                'responded_by' => $actor->id,
                'accepted_at' => now(),
                'signed_evidence_paths' => array_values($evidencePaths),
                'signed_evidence_uploaded_at' => now(),
                'signed_evidence_uploaded_by' => $actor->id,
            ])->save();
            app(AdminNotificationService::class)->resolve('extension_request', $request);

            return $contract->fresh();
        }, 3);
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

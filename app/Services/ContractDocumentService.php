<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\FeeSchedule;
use App\Models\Setting;

class ContractDocumentService
{
    public function assignActiveTemplate(Contract $contract): ContractTemplate
    {
        $template = ContractTemplate::activeOrCreate();
        $contract->forceFill(['contract_template_id' => $template->id])->save();

        return $template;
    }

    public function render(Contract $contract, ?ContractTemplate $template = null): string
    {
        $contract = Contract::query()->with([
            'room.amenities', 'tenant', 'currentMembers.tenant',
            'representativeMember.tenant', 'handoverItems', 'template',
        ])->findOrFail($contract->id);
        $template ??= $contract->template ?: ContractTemplate::activeOrCreate();
        $referenceReading = $contract->utilityReadings()->latest('record_date')->latest('id')->first()
            ?? $contract->room?->utilityReadings()->latest('record_date')->latest('id')->first();

        return view('admin.contracts.contract-template-content', [
            'contract' => $contract,
            'referenceReading' => $referenceReading,
            'setting' => Setting::currentOrCreate(),
            'template' => $template,
        ])->render();
    }

    public function snapshotSignedDocument(Contract $contract): void
    {
        $this->snapshotPricing($contract);
        $template = $contract->template ?: $this->assignActiveTemplate($contract);
        $content = $this->render($contract, $template);

        $contract->forceFill([
            'contract_content' => $content,
            'contract_content_snapshotted_at' => now(),
            'contract_content_sha256' => hash('sha256', $content),
        ])->save();
    }

    private function snapshotPricing(Contract $contract): void
    {
        if (filled($contract->electric_price_snapshot)
            && filled($contract->water_price_snapshot)
            && $contract->internet_fee_snapshot !== null
            && $contract->service_fee_snapshot !== null) {
            return;
        }

        $rates = FeeSchedule::forPeriod($contract->signed_at ?? now()) ?? Setting::currentOrCreate();
        $contract->forceFill([
            'electric_price_snapshot' => $rates->electric_price,
            'water_price_snapshot' => $rates->water_price,
            'internet_fee_snapshot' => $rates->internet_fee,
            'service_fee_snapshot' => $rates->service_fee,
        ])->save();
    }
}

<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\FeeSchedule;
use App\Models\Setting;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

class ContractRateResolver
{
    public const FIELDS = [
        'electric_price',
        'water_price',
        'internet_fee',
        'service_fee',
    ];

    public function forPeriod(Contract $contract, DateTimeInterface|string $period, FeeSchedule|Setting|null $fallback = null): object
    {
        $periodStart = Carbon::parse($period)->startOfMonth();
        $rates = $this->baseRates($contract, $fallback);

        $appendices = $contract->appendices()
            ->where('status', ContractAppendix::STATUS_ACCEPTED)
            ->whereNotNull('price_adjustments')
            ->whereDate('effective_from', '<=', $periodStart)
            ->oldest('effective_from')
            ->oldest('accepted_at')
            ->oldest('id')
            ->get();

        foreach ($appendices as $appendix) {
            foreach ($appendix->price_adjustments ?? [] as $field => $change) {
                if (in_array($field, self::FIELDS, true) && is_array($change) && array_key_exists('new', $change)) {
                    $rates[$field] = (float) $change['new'];
                }
            }
        }

        return (object) $rates;
    }

    public function prepareAdjustments(Contract $contract, string $title, DateTimeInterface|string $effectiveFrom, array $submitted): ?array
    {
        $fields = ContractAppendix::priceFieldsForTitle($title);
        if ($fields === []) {
            return null;
        }

        $rates = $this->forPeriod($contract, $this->effectivePeriod($effectiveFrom));
        $adjustments = [];
        foreach ($fields as $field) {
            if (! array_key_exists($field, $submitted) || $submitted[$field] === null || $submitted[$field] === '') {
                continue;
            }
            $old = (float) ($rates->{$field} ?? 0);
            $new = (float) $submitted[$field];
            if ($old !== $new) {
                $adjustments[$field] = ['old' => $old, 'new' => $new];
            }
        }

        if ($adjustments === []) {
            throw ValidationException::withMessages([
                'price_adjustments' => 'Đơn giá mới phải khác đơn giá đang áp dụng của hợp đồng.',
            ]);
        }

        return $adjustments;
    }

    public function effectivePeriod(DateTimeInterface|string $effectiveFrom): Carbon
    {
        $date = Carbon::parse($effectiveFrom)->startOfDay();

        return $date->day === 1 ? $date->startOfMonth() : $date->addMonthNoOverflow()->startOfMonth();
    }

    private function baseRates(Contract $contract, FeeSchedule|Setting|null $fallback): array
    {
        $fallback ??= FeeSchedule::forPeriod($contract->signed_at ?? $contract->start_date ?? now())
            ?? Setting::currentOrCreate();

        return [
            'electric_price' => (float) ($contract->electric_price_snapshot ?? $fallback->electric_price ?? 0),
            'water_price' => (float) ($contract->water_price_snapshot ?? $fallback->water_price ?? 0),
            'internet_fee' => (float) ($contract->internet_fee_snapshot ?? $fallback->internet_fee ?? 0),
            'service_fee' => (float) ($contract->service_fee_snapshot ?? $fallback->service_fee ?? 0),
        ];
    }
}

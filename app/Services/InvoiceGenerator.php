<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceGenerator
{
    public function preview(Contract $contract, int $month, int $year): array
    {
        $contract->loadMissing(['room', 'tenant']);

        $this->ensureContractCanBeBilled($contract, $month, $year);

        $billingPeriod = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $utilityPeriod = $billingPeriod->copy()->subMonthNoOverflow();

        $reading = UtilityReading::where('room_id', $contract->room_id)
            ->where(fn ($query) => $query->where('contract_id', $contract->id)
                ->orWhere(fn ($legacy) => $legacy->whereNull('contract_id')->whereDate('record_date', '>=', $contract->start_date)))
            ->where('month', $utilityPeriod->month)
            ->where('year', $utilityPeriod->year)
            ->where('reading_type', 'periodic')
            ->where('status', 'confirmed')
            ->first();

        if (! $reading) {
            throw ValidationException::withMessages([
                'utility_reading' => "Phòng {$contract->room->room_code} chưa chốt điện nước tháng {$utilityPeriod->month}/{$utilityPeriod->year}.",
            ]);
        }

        $existingInvoice = Invoice::where('contract_id', $contract->id)
            ->where('invoice_type', Invoice::TYPE_RENTAL)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();

        if ($existingInvoice) {
            throw ValidationException::withMessages([
                'invoice' => "Phòng {$contract->room->room_code} đã có hóa đơn tháng {$month}/{$year}.",
            ]);
        }

        $setting = Setting::currentOrCreate();

        // Ngày 5 thu tiền phòng tháng hiện tại cùng điện, nước và dịch vụ của tháng trước.
        $invoiceDateCarbon = $billingPeriod->copy();
        $invoiceDay = 5;
        $invoiceDay = max(1, min($invoiceDay, $invoiceDateCarbon->daysInMonth));
        $invoiceDateCarbon->day($invoiceDay);

        $invoiceDate = $invoiceDateCarbon->toDateString();
        $dueDate = $invoiceDate;

        $electricityUsage = $reading->electricity_new - $reading->electricity_old;
        $waterUsage = $reading->water_new - $reading->water_old;

        if ($electricityUsage < 0 || $waterUsage < 0) {
            throw ValidationException::withMessages([
                'utility_reading' => 'Chỉ số mới không được nhỏ hơn chỉ số cũ.',
            ]);
        }

        $lines = [
            [
                'type' => 'room',
                'name' => "Tiền phòng tháng {$month}/{$year}",
                'quantity' => 1,
                'unit' => 'thang',
                'unit_price' => (float) $contract->monthly_rent,
                'amount' => (float) $contract->monthly_rent,
                'old_index' => null,
                'new_index' => null,
                'note' => "Thu trước cho tháng {$month}/{$year} · Hợp đồng {$contract->contract_code}",
                'sort_order' => 1,
            ],
            [
                'type' => 'electricity',
                'name' => "Tiền điện tháng {$utilityPeriod->month}/{$utilityPeriod->year}",
                'quantity' => $electricityUsage,
                'unit' => 'kWh',
                'unit_price' => (float) $setting->electric_price,
                'amount' => $electricityUsage * (float) $setting->electric_price,
                'old_index' => $reading->electricity_old,
                'new_index' => $reading->electricity_new,
                'note' => null,
                'sort_order' => 2,
            ],
            [
                'type' => 'water',
                'name' => "Tiền nước tháng {$utilityPeriod->month}/{$utilityPeriod->year}",
                'quantity' => $waterUsage,
                'unit' => 'm³',
                'unit_price' => (float) $setting->water_price,
                'amount' => $waterUsage * (float) $setting->water_price,
                'old_index' => $reading->water_old,
                'new_index' => $reading->water_new,
                'note' => 'Tính theo chỉ số đồng hồ thực tế',
                'sort_order' => 3,
            ],
        ];

        $parkingUnitPrice = 0;
        $parkingName = 'Gửi xe máy miễn phí';

        $serviceLines = [
            ['internet', "Phí internet tháng {$utilityPeriod->month}/{$utilityPeriod->year}", $contract->internet_enabled ? (float) ($setting->internet_fee ?? 0) : 0, 1, 4],
            ['service', "Phí dịch vụ tháng {$utilityPeriod->month}/{$utilityPeriod->year}", $contract->service_enabled ? (float) ($setting->service_fee ?? 0) : 0, 1, 5],
            ['parking', $parkingName, $parkingUnitPrice, (int) $contract->parking_quantity, 6],
        ];

        foreach ($serviceLines as [$type, $name, $unitPrice, $quantity, $sortOrder]) {
            if ($unitPrice <= 0 || $quantity <= 0) {
                continue;
            }

            $amount = $unitPrice * $quantity;

            $lines[] = [
                'type' => $type,
                'name' => $name,
                'quantity' => $quantity,
                'unit' => 'thang',
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'old_index' => null,
                'new_index' => null,
                'note' => null,
                'sort_order' => $sortOrder,
            ];
        }

        $totalAmount = collect($lines)->sum('amount');
        $internetFee = collect($lines)->where('type', 'internet')->sum('amount');
        $serviceFee = collect($lines)->whereIn('type', ['service', 'parking'])->sum('amount');

        return [
            'contract' => $contract,
            'room' => $contract->room,
            'tenant' => $contract->tenant,
            'reading' => $reading,
            'month' => $month,
            'year' => $year,
            'utility_month' => $utilityPeriod->month,
            'utility_year' => $utilityPeriod->year,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'room_fee' => (float) $contract->monthly_rent,
            'electricity_fee' => collect($lines)->where('type', 'electricity')->sum('amount'),
            'water_fee' => collect($lines)->where('type', 'water')->sum('amount'),
            'internet_fee' => $internetFee,
            'service_fee' => $serviceFee,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
            'lines' => $lines,
        ];
    }

    public function issue(Contract $contract, int $month, int $year): Invoice
    {
        return DB::transaction(function () use ($contract, $month, $year) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $contract->room()->lockForUpdate()->firstOrFail();
            $preview = $this->preview($contract, $month, $year);
            UtilityReading::query()->lockForUpdate()->findOrFail($preview['reading']->id);

            $invoice = Invoice::create([
                'contract_id' => $preview['contract']->id,
                'invoice_type' => Invoice::TYPE_RENTAL,
                'room_id' => $preview['room']->id,
                'invoice_code' => null,
                'utility_reading_id' => $preview['reading']->id,
                'month' => $month,
                'year' => $year,
                'invoice_date' => $preview['invoice_date'],
                'due_date' => $preview['due_date'],
                'room_fee' => $preview['room_fee'],
                'electricity_fee' => $preview['electricity_fee'],
                'water_fee' => $preview['water_fee'],
                'internet_fee' => $preview['internet_fee'],
                'service_fee' => $preview['service_fee'],
                'total_amount' => $preview['total_amount'],
                'status' => 'unpaid',
            ]);

            $invoice->update([
                'invoice_code' => sprintf('INV-%04d%02d-%06d', $year, $month, $invoice->id),
            ]);

            foreach ($preview['lines'] as $line) {
                $invoice->details()->create($line);
            }

            app(TenantAccountLifecycle::class)->sync(
                $preview['tenant']->loadMissing('user')
            );

            return $invoice->load(['contract.tenant', 'room', 'details']);
        });
    }

    private function ensureContractCanBeBilled(Contract $contract, int $month, int $year): void
    {
        if (! in_array($contract->status, [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED, Contract::STATUS_SETTLING], true)) {
            throw ValidationException::withMessages([
                'contract' => 'Chỉ hợp đồng đang hiệu lực hoặc đã kết thúc trong kỳ mới được sinh hóa đơn.',
            ]);
        }

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $contractStart = Carbon::parse($contract->start_date)->startOfMonth();

        if ($periodStart->lte($contractStart)) {
            throw ValidationException::withMessages([
                'invoice' => 'Tiền phòng tháng đầu đã được thu bằng hóa đơn trả trước sau khi ký hợp đồng.',
            ]);
        }

        $effectiveEnd = $this->resolveContractEffectiveEnd($contract);

        if (
            Carbon::parse($contract->start_date)->gt($periodEnd)
            || $effectiveEnd->lt($periodStart)
        ) {
            throw ValidationException::withMessages([
                'contract' => "Hợp đồng {$contract->contract_code} không nằm trong kỳ {$month}/{$year}.",
            ]);
        }
    }

    private function resolveContractEffectiveEnd(Contract $contract): Carbon
    {
        if ($contract->status === Contract::STATUS_EXPIRED && ! $contract->actual_move_out_at) {
            return now()->endOfMonth();
        }
        if ($contract->actual_end_date) {
            return Carbon::parse($contract->actual_end_date)->endOfDay();
        }

        if ($contract->extend_end_date) {
            return Carbon::parse($contract->extend_end_date)->endOfDay();
        }

        return Carbon::parse($contract->end_date)->endOfDay();
    }
}

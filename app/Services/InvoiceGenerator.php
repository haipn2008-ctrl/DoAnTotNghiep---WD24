<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\RoomTransfer;
use App\Models\Setting;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceGenerator
{
    public function __construct(private readonly ContractRateResolver $pricing) {}

    public function preview(Contract $contract, int $month, int $year, ?FeeSchedule $lockedFeeSchedule = null): array
    {
        $contract->loadMissing(['room', 'tenant']);

        $this->ensureContractCanBeBilled($contract, $month, $year);

        $billingPeriod = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $servicePeriod = $billingPeriod->copy()->subMonthNoOverflow();

        $existingInvoice = Invoice::where('contract_id', $contract->id)
            ->where('invoice_type', Invoice::TYPE_RENTAL)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->exists();

        if ($existingInvoice) {
            throw ValidationException::withMessages([
                'invoice' => "Phòng {$contract->room->room_code} đã có hóa đơn tháng {$month}/{$year}.",
            ]);
        }

        $reading = UtilityReading::where('room_id', $contract->room_id)
            ->where(fn ($query) => $query->where('contract_id', $contract->id)
                ->orWhere(fn ($legacy) => $legacy->whereNull('contract_id')->whereDate('record_date', '>=', $contract->start_date)))
            ->where('month', $servicePeriod->month)
            ->where('year', $servicePeriod->year)
            ->where('reading_type', 'periodic')
            ->where('status', UtilityReading::STATUS_CONFIRMED)
            ->first();

        if (! $reading) {
            throw ValidationException::withMessages([
                'utility_reading' => "Phòng {$contract->room->room_code} chưa chốt điện nước tháng {$servicePeriod->month}/{$servicePeriod->year}.",
            ]);
        }

        $setting = Setting::currentOrCreate();
        $feeSchedule = $lockedFeeSchedule ?? FeeSchedule::forPeriod($servicePeriod);
        $rates = $this->pricing->forPeriod($contract, $servicePeriod, $feeSchedule ?? $setting);

        // Thu tiền phòng, điện, nước và dịch vụ của tháng trước theo lịch đã cấu hình.
        $invoiceDateCarbon = $billingPeriod->copy();
        $invoiceDay = (int) $setting->invoice_day;
        $invoiceDay = max(1, min($invoiceDay, $invoiceDateCarbon->daysInMonth));
        $invoiceDateCarbon->day($invoiceDay);

        $invoiceDate = $invoiceDateCarbon->toDateString();
        $dueDate = $invoiceDateCarbon->copy()
            ->addDays((int) $setting->payment_due_days)
            ->toDateString();

        $electricityUsage = $reading->electricity_new - $reading->electricity_old;
        $waterUsage = $reading->water_new - $reading->water_old;
        $grossRoomFee = $this->roomFeeForPeriod($contract, $servicePeriod);
        $roomRatio = $this->roomBillingRatioForPeriod($contract, $servicePeriod);
        $firstMonthCredit = $this->firstMonthPrepaidCredit($contract, $servicePeriod, $grossRoomFee);
        $roomFee = max(0, $grossRoomFee - $firstMonthCredit);

        if ($electricityUsage < 0 || $waterUsage < 0) {
            throw ValidationException::withMessages([
                'utility_reading' => 'Chỉ số mới không được nhỏ hơn chỉ số cũ.',
            ]);
        }

        $lines = [
            [
                'type' => 'room',
                'name' => "Tiền phòng tháng {$servicePeriod->month}/{$servicePeriod->year}",
                'quantity' => $roomRatio < 1
                    ? round($roomRatio, 4)
                    : ($servicePeriod->isSameMonth($contract->start_date) ? $contract->first_month_rent_days : 1),
                'unit' => $roomRatio < 1 ? 'tháng' : ($servicePeriod->isSameMonth($contract->start_date) ? 'ngày' : 'tháng'),
                'unit_price' => $roomRatio < 1
                    ? (float) $contract->monthly_rent
                    : ($servicePeriod->isSameMonth($contract->start_date)
                    ? round((float) $contract->monthly_rent / $servicePeriod->daysInMonth, 2)
                    : (float) $contract->monthly_rent),
                'amount' => $grossRoomFee,
                'old_index' => null,
                'new_index' => null,
                'note' => $roomRatio < 1
                    ? 'Tính theo số ngày sử dụng phòng mới sau khi chuyển phòng.'
                    : ($servicePeriod->isSameMonth($contract->start_date) && $contract->first_month_rent_days <= 5
                    ? 'Miễn tiền phòng vì thời gian thuê trong tháng không quá 5 ngày.'
                    : "Thu sau cho tháng {$servicePeriod->month}/{$servicePeriod->year} · Hạn ngày 05/{$month}/{$year}"),
                'sort_order' => 1,
            ],
            [
                'type' => 'electricity',
                'name' => "Tiền điện tháng {$servicePeriod->month}/{$servicePeriod->year}",
                'quantity' => $electricityUsage,
                'unit' => 'kWh',
                'unit_price' => (float) $rates->electric_price,
                'amount' => $electricityUsage * (float) $rates->electric_price,
                'old_index' => $reading->electricity_old,
                'new_index' => $reading->electricity_new,
                'note' => null,
                'sort_order' => 2,
            ],
            [
                'type' => 'water',
                'name' => "Tiền nước tháng {$servicePeriod->month}/{$servicePeriod->year}",
                'quantity' => $waterUsage,
                'unit' => 'm³',
                'unit_price' => (float) $rates->water_price,
                'amount' => $waterUsage * (float) $rates->water_price,
                'old_index' => $reading->water_old,
                'new_index' => $reading->water_new,
                'note' => 'Tính theo chỉ số đồng hồ thực tế',
                'sort_order' => 3,
            ],
        ];

        if ($firstMonthCredit > 0) {
            $lines[] = [
                'type' => 'first_month_credit',
                'name' => 'Khấu trừ tiền phòng tháng đầu đã thu trước',
                'quantity' => 1,
                'unit' => 'lần',
                'unit_price' => -$firstMonthCredit,
                'amount' => -$firstMonthCredit,
                'old_index' => null,
                'new_index' => null,
                'note' => 'Tự động khấu trừ khoản đã thanh toán theo chính sách cũ.',
                'sort_order' => 7,
            ];
        }

        $serviceRatio = $roomRatio;
        $serviceLines = [
            // Internet là phí cố định theo phòng, thu một lần mỗi tháng và không phụ thuộc số người.
            ['internet', "Phí internet tháng {$servicePeriod->month}/{$servicePeriod->year}", (float) ($rates->internet_fee ?? 0), $serviceRatio, 4],
            ['service', "Phí dịch vụ tháng {$servicePeriod->month}/{$servicePeriod->year}", (float) ($rates->service_fee ?? 0), $serviceRatio, 5],
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
            'fee_schedule' => $feeSchedule,
            'month' => $month,
            'year' => $year,
            'utility_month' => $servicePeriod->month,
            'utility_year' => $servicePeriod->year,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'room_fee' => $roomFee,
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
            $servicePeriod = Carbon::createFromDate($year, $month, 1)->subMonthNoOverflow();
            $feeSchedule = FeeSchedule::forPeriod($servicePeriod, true);
            $preview = $this->preview($contract, $month, $year, $feeSchedule);

            if (today()->lt(Carbon::parse($preview['invoice_date'])->startOfDay())) {
                throw ValidationException::withMessages([
                    'invoice_date' => 'Hóa đơn kỳ này chỉ được phát hành từ ngày '.
                        Carbon::parse($preview['invoice_date'])->format('d/m/Y').'.',
                ]);
            }

            $lockedReading = UtilityReading::query()->lockForUpdate()->findOrFail($preview['reading']->id);
            if (! $lockedReading->isConfirmed()) {
                throw ValidationException::withMessages([
                    'utility_reading' => 'Chỉ số điện nước không còn ở trạng thái đã xác nhận.',
                ]);
            }
            $revision = ((int) Invoice::query()
                ->where('contract_id', $contract->id)
                ->where('invoice_type', Invoice::TYPE_RENTAL)
                ->where('month', $month)
                ->where('year', $year)
                ->max('revision')) + 1;

            $invoice = Invoice::create([
                'contract_id' => $preview['contract']->id,
                'fee_schedule_id' => $preview['fee_schedule']?->id,
                'invoice_type' => Invoice::TYPE_RENTAL,
                'revision' => $revision,
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

            $lockedReading->update(['status' => UtilityReading::STATUS_LOCKED]);

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

        $billingPeriod = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodStart = $billingPeriod->copy()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $contractStart = Carbon::parse($contract->start_date)->startOfMonth();

        if ($periodStart->lt($contractStart)) {
            throw ValidationException::withMessages([
                'invoice' => 'Chưa đến kỳ thu tiền phòng và điện nước đầu tiên của hợp đồng.',
            ]);
        }

        $effectiveEnd = $this->resolveContractEffectiveEnd($contract);

        if (
            Carbon::parse($contract->start_date)->gt($periodEnd)
            || $effectiveEnd->lt($periodStart)
        ) {
            throw ValidationException::withMessages([
                'contract' => "Hợp đồng {$contract->contract_code} không phát sinh chi phí trong tháng {$periodStart->month}/{$periodStart->year}.",
            ]);
        }
    }

    private function roomFeeForPeriod(Contract $contract, Carbon $servicePeriod): float
    {
        $ratio = $this->roomBillingRatioForPeriod($contract, $servicePeriod);
        if ($ratio < 1) {
            return round((float) $contract->monthly_rent * $ratio);
        }

        if ($servicePeriod->isSameMonth($contract->start_date)) {
            return (float) $contract->calculated_first_month_rent_amount;
        }

        return (float) $contract->monthly_rent;
    }

    private function roomBillingRatioForPeriod(Contract $contract, Carbon $servicePeriod): float
    {
        $transfer = RoomTransfer::query()
            ->where('contract_id', $contract->id)
            ->where('new_room_id', $contract->room_id)
            ->where('status', RoomTransfer::STATUS_COMPLETED)
            ->whereBetween('effective_date', [
                $servicePeriod->copy()->startOfMonth()->toDateString(),
                $servicePeriod->copy()->endOfMonth()->toDateString(),
            ])
            ->latest('effective_date')->latest('id')->first();

        if (! $transfer) {
            return 1.0;
        }

        $days = $transfer->effective_date->copy()->startOfDay()
            ->diffInDays($servicePeriod->copy()->endOfMonth()) + 1;

        return $days / $servicePeriod->daysInMonth;
    }

    private function firstMonthPrepaidCredit(Contract $contract, Carbon $servicePeriod, float $roomFee): float
    {
        if (! $servicePeriod->isSameMonth($contract->start_date) || $roomFee <= 0) {
            return 0.0;
        }

        $paid = (float) DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('invoices.contract_id', $contract->id)
            ->where('invoices.invoice_type', Invoice::TYPE_FIRST_MONTH_RENT)
            ->where('payments.status', 'success')
            ->sum('payments.amount_paid');

        return min($roomFee, $paid);
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

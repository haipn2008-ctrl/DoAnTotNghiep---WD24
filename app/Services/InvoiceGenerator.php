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

        $reading = UtilityReading::where('room_id', $contract->room_id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'confirmed')
            ->first();

        if (! $reading) {
            throw ValidationException::withMessages([
                'utility_reading' => "Phòng {$contract->room->room_code} chưa chốt điện nước tháng {$month}/{$year}.",
            ]);
        }

        $existingInvoice = Invoice::where('room_id', $contract->room_id)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();

        if ($existingInvoice) {
            throw ValidationException::withMessages([
                'invoice' => "Phòng {$contract->room->room_code} đã có hóa đơn tháng {$month}/{$year}.",
            ]);
        }

        $setting = Setting::currentOrCreate();

        $billingDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $invoiceDate = $billingDate->toDateString();
        $dueDate = $billingDate->copy()->addDays((int) ($setting->payment_due_days ?? 10))->toDateString();

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
                'name' => 'Tiền thuê phòng',
                'quantity' => 1,
                'unit' => 'thang',
                'unit_price' => (float) $contract->monthly_rent,
                'amount' => (float) $contract->monthly_rent,
                'old_index' => null,
                'new_index' => null,
                'note' => "Hợp đồng {$contract->contract_code}",
                'sort_order' => 1,
            ],
            [
                'type' => 'electricity',
                'name' => 'Tiền điện',
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
                'name' => 'Tiền nước',
                'quantity' => $waterUsage,
                'unit' => 'm3',
                'unit_price' => (float) $setting->water_price,
                'amount' => $waterUsage * (float) $setting->water_price,
                'old_index' => $reading->water_old,
                'new_index' => $reading->water_new,
                'note' => null,
                'sort_order' => 3,
            ],
        ];

        $serviceLines = [
            ['internet', 'Phí internet', (float) ($setting->internet_fee ?? 0), 4],
            ['service', 'Phí dịch vụ chung', (float) ($setting->service_fee ?? 0), 5],
            ['parking', 'Phí gửi xe', (float) ($setting->parking_fee ?? 0), 6],
        ];

        foreach ($serviceLines as [$type, $name, $amount, $sortOrder]) {
            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'type' => $type,
                'name' => $name,
                'quantity' => 1,
                'unit' => 'thang',
                'unit_price' => $amount,
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
            $preview = $this->preview($contract, $month, $year);

            $invoice = Invoice::create([
                'contract_id' => $preview['contract']->id,
                'room_id' => $preview['room']->id,
                'invoice_code' => $this->nextInvoiceCode($month, $year),
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

            foreach ($preview['lines'] as $line) {
                $invoice->details()->create($line);
            }

            return $invoice->load(['contract.tenant', 'room', 'details']);
        });
    }

    private function ensureContractCanBeBilled(Contract $contract, int $month, int $year): void
    {
        if ($contract->status !== 'active') {
            throw ValidationException::withMessages([
                'contract' => 'Chỉ hợp đồng đang hiệu lực mới được sinh hóa đơn.',
            ]);
        }

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        if (
            Carbon::parse($contract->start_date)->gt($periodEnd)
            || Carbon::parse($contract->end_date)->lt($periodStart)
        ) {
            throw ValidationException::withMessages([
                'contract' => "Hợp đồng {$contract->contract_code} không nằm trong kỳ {$month}/{$year}.",
            ]);
        }
    }

    private function nextInvoiceCode(int $month, int $year): string
    {
        $prefix = sprintf('INV-%04d%02d-', $year, $month);
        $latestCode = Invoice::where('invoice_code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_code')
            ->value('invoice_code');

        $sequence = $latestCode
            ? ((int) substr($latestCode, -4)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}

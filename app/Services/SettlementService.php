<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\SettlementStatement;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    public function generate(Contract $contract, float $manualAmount = 0, ?string $manualDescription = null): SettlementStatement
    {
        return DB::transaction(function () use ($contract, $manualAmount, $manualDescription): SettlementStatement {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            if ($contract->status !== Contract::STATUS_SETTLING || ! $contract->actual_move_out_at) {
                throw ValidationException::withMessages(['contract' => 'Chỉ lập quyết toán sau khi đã checkout thực tế.']);
            }

            $existing = SettlementStatement::query()->where('contract_id', $contract->id)->lockForUpdate()->first();
            if ($existing) {
                return $this->refreshFinancials($existing);
            }

            $checkout = UtilityReading::query()
                ->where('contract_id', $contract->id)
                ->where('reading_type', 'checkout')
                ->lockForUpdate()
                ->first();
            if (! $checkout) {
                throw ValidationException::withMessages(['checkout_reading' => 'Thiếu chỉ số checkout để lập quyết toán.']);
            }

            $moveOutDate = Carbon::parse($contract->actual_move_out_at)->startOfDay();
            $rates = FeeSchedule::forPeriod($moveOutDate, true) ?: Setting::currentOrCreate();
            $items = [];
            $sort = 1;
            $electricityUsage = $checkout->electricity_new - $checkout->electricity_old;
            $waterUsage = $checkout->water_new - $checkout->water_old;
            if ($electricityUsage > 0) {
                $items[] = $this->item('electricity', 'Điện đến ngày trả phòng', $electricityUsage, 'kWh', (float) $rates->electric_price, $sort++, "Chỉ số {$checkout->electricity_old} → {$checkout->electricity_new}");
            }
            if ($waterUsage > 0) {
                $items[] = $this->item('water', 'Nước đến ngày trả phòng', $waterUsage, 'm³', (float) $rates->water_price, $sort++, "Chỉ số {$checkout->water_old} → {$checkout->water_new}");
            }

            $currentMonthAlreadyBilled = Invoice::query()
                ->where('contract_id', $contract->id)
                ->where('invoice_type', Invoice::TYPE_RENTAL)
                ->where('month', $moveOutDate->month)
                ->where('year', $moveOutDate->year)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->exists();
            if (! $currentMonthAlreadyBilled) {
                $periodStart = $moveOutDate->copy()->startOfMonth();
                if ($contract->actual_move_in_at && Carbon::parse($contract->actual_move_in_at)->gt($periodStart)) {
                    $periodStart = Carbon::parse($contract->actual_move_in_at)->startOfDay();
                }
                $days = $periodStart->diffInDays($moveOutDate) + 1;
                $ratio = $days / $moveOutDate->daysInMonth;
                $dailyRent = (float) $contract->monthly_rent / $moveOutDate->daysInMonth;
                $items[] = $this->item('room', 'Tiền phòng lẻ kỳ đến ngày trả phòng', $days, 'ngày', $dailyRent, $sort++, "Từ {$periodStart->format('d/m/Y')} đến {$moveOutDate->format('d/m/Y')}");
                foreach ([['internet', 'Phí internet lẻ kỳ', (float) ($rates->internet_fee ?? 0)], ['service', 'Phí dịch vụ lẻ kỳ', (float) ($rates->service_fee ?? 0)]] as [$type, $name, $monthlyFee]) {
                    if ($monthlyFee > 0) {
                        $items[] = $this->item($type, $name, $days, 'ngày', $monthlyFee / $moveOutDate->daysInMonth, $sort++, 'Tính theo số ngày sử dụng thực tế.');
                    }
                }
            }
            if ($manualAmount > 0) {
                $items[] = $this->item('adjustment', $manualDescription ?: 'Bồi thường/điều chỉnh khi trả phòng', 1, 'lần', $manualAmount, $sort++, 'Khoản điều chỉnh do ban quản lý ghi nhận khi kiểm phòng.');
            }

            $finalCharge = round((float) collect($items)->sum('amount'), 2);
            $invoice = $finalCharge > 0 ? $this->createInvoice($contract, $checkout, $moveOutDate, $items, $finalCharge) : null;
            $previousOutstanding = $this->outstandingAmount($contract, $invoice?->id);
            $depositCredit = $this->depositCredit($contract);
            $net = round($previousOutstanding + $finalCharge - $depositCredit, 2);
            $statement = SettlementStatement::query()->create([
                'contract_id' => $contract->id,
                'invoice_id' => $invoice?->id,
                'checkout_reading_id' => $checkout->id,
                'status' => $this->statusForNet($net),
                'final_charge_amount' => $finalCharge,
                'previous_outstanding_amount' => $previousOutstanding,
                'deposit_credit' => $depositCredit,
                'net_amount' => $net,
                'calculated_at' => now(),
            ]);
            foreach ($items as $item) {
                $statement->items()->create($item);
            }
            if ($depositCredit > 0 && $net >= 0) {
                $contract->forceFill([
                    'deposit_status' => Contract::DEPOSIT_DEDUCTED,
                    'deposit_resolution' => Contract::DEPOSIT_DEDUCTED,
                    'deposit_deduction_amount' => $depositCredit,
                    'deposit_refund_amount' => 0,
                    'deposit_resolved_at' => now(),
                    'deposit_process_reason' => 'Tự động bù trừ vào công nợ và hóa đơn quyết toán.',
                ])->save();
            }

            return $statement->load(['items', 'invoice', 'checkoutReading']);
        }, 3);
    }

    public function refreshFinancials(SettlementStatement $statement): SettlementStatement
    {
        $statement->loadMissing(['contract', 'invoice.payments', 'invoice.adjustments']);
        $finalOutstanding = $statement->invoice ? (float) $statement->invoice->remaining_amount : 0;
        $previousOutstanding = $this->outstandingAmount($statement->contract, $statement->invoice_id);
        $net = round($previousOutstanding + $finalOutstanding - (float) $statement->deposit_credit, 2);
        $statement->forceFill([
            'previous_outstanding_amount' => $previousOutstanding,
            'net_amount' => $net,
            'status' => $this->statusForNet($net),
            'calculated_at' => now(),
        ])->save();

        return $statement->fresh(['items', 'invoice', 'checkoutReading']);
    }

    public function markSettled(Contract $contract): void
    {
        $contract->settlementStatement()->update([
            'status' => SettlementStatement::STATUS_SETTLED,
            'net_amount' => 0,
        ]);
    }

    private function createInvoice(Contract $contract, UtilityReading $checkout, Carbon $date, array $items, float $total): Invoice
    {
        $invoice = Invoice::query()->forceCreate([
            'contract_id' => $contract->id,
            'room_id' => $contract->room_id,
            'utility_reading_id' => $checkout->id,
            'invoice_type' => Invoice::TYPE_SETTLEMENT,
            'lifecycle_event_key' => "contract:{$contract->id}:settlement",
            'invoice_code' => null,
            'month' => $date->month,
            'year' => $date->year,
            'invoice_date' => $date->toDateString(),
            'due_date' => $date->copy()->addDays((int) Setting::currentOrCreate()->payment_due_days)->toDateString(),
            'room_fee' => collect($items)->where('type', 'room')->sum('amount'),
            'electricity_fee' => collect($items)->where('type', 'electricity')->sum('amount'),
            'water_fee' => collect($items)->where('type', 'water')->sum('amount'),
            'internet_fee' => collect($items)->where('type', 'internet')->sum('amount'),
            'service_fee' => collect($items)->where('type', 'service')->sum('amount'),
            'total_amount' => $total,
            'status' => Invoice::STATUS_UNPAID,
        ]);
        $invoice->forceFill(['invoice_code' => sprintf('SET-%04d%02d-%06d', $date->year, $date->month, $invoice->id)])->save();
        foreach ($items as $item) {
            $invoice->details()->create($item);
        }

        return $invoice;
    }

    private function outstandingAmount(Contract $contract, ?int $excludedInvoiceId): float
    {
        return round((float) Invoice::query()
            ->where('contract_id', $contract->id)
            ->when($excludedInvoiceId, fn ($query) => $query->whereKeyNot($excludedInvoiceId))
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->with(['payments', 'adjustments'])
            ->get()
            ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount), 2);
    }

    private function depositCredit(Contract $contract): float
    {
        return in_array($contract->deposit_status, [
            Contract::DEPOSIT_PAID,
            Contract::DEPOSIT_NEEDS_RESOLUTION,
            Contract::DEPOSIT_REFUND_REQUESTED,
            Contract::DEPOSIT_REFUND_APPROVED,
            Contract::DEPOSIT_REFUND_PROCESSING,
        ], true) ? (float) $contract->deposit_amount : 0;
    }

    private function statusForNet(float $net): string
    {
        return $net > 0
            ? SettlementStatement::STATUS_AWAITING_PAYMENT
            : ($net < 0 ? SettlementStatement::STATUS_AWAITING_REFUND : SettlementStatement::STATUS_BALANCED);
    }

    private function item(string $type, string $name, float|int $quantity, string $unit, float $unitPrice, int $sortOrder, ?string $note): array
    {
        return [
            'type' => $type,
            'name' => $name,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => round($unitPrice, 2),
            'amount' => round($quantity * $unitPrice, 2),
            'note' => $note,
            'sort_order' => $sortOrder,
        ];
    }
}

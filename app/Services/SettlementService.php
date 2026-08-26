<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Setting;
use App\Models\SettlementStatement;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    private const DEPOSIT_OFFSET_PREFIX = 'COC-BUTRU-';

    public function generate(Contract $contract, float $manualAmount = 0, ?string $manualDescription = null): SettlementStatement
    {
        return DB::transaction(function () use ($contract, $manualAmount, $manualDescription): SettlementStatement {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            if ($contract->status !== Contract::STATUS_SETTLING || ! $contract->actual_move_out_at) {
                throw ValidationException::withMessages(['contract' => 'Chỉ được lập quyết toán sau khi khách đã trả phòng thực tế.']);
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
                throw ValidationException::withMessages(['checkout_reading' => 'Thiếu chỉ số điện nước khi trả phòng để lập quyết toán.']);
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

            // VND is collected in whole dong. Round each settlement total
            // before issuing the invoice so the payment form and invoice
            // status always compare the same value.
            $finalCharge = round((float) collect($items)->sum('amount'));
            $invoice = $finalCharge > 0 ? $this->createInvoice($contract, $checkout, $moveOutDate, $items, $finalCharge) : null;
            $previousOutstanding = $this->outstandingAmount($contract, $invoice?->id);
            $depositCredit = $this->depositCredit($contract);
            $net = round($previousOutstanding + $finalCharge - $depositCredit);
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

            return $this->refreshFinancials($statement);
        }, 3);
    }

    public function refreshFinancials(SettlementStatement $statement): SettlementStatement
    {
        return DB::transaction(function () use ($statement): SettlementStatement {
            $statement = SettlementStatement::query()
                ->with('contract')
                ->lockForUpdate()
                ->findOrFail($statement->id);

            $allocation = $this->applyDepositOffset(
                $statement->contract,
                (float) $statement->deposit_credit,
                $statement->invoice_id,
            );
            $net = round($allocation['remaining_debt'] - $allocation['refund_amount']);

            $statement->forceFill([
                'previous_outstanding_amount' => $allocation['previous_outstanding'],
                'net_amount' => $net,
                'status' => $this->statusForNet($net),
                'calculated_at' => now(),
            ])->save();

            $this->syncDepositResolution(
                $statement->contract,
                $allocation['applied_amount'],
                $allocation['refund_amount'],
            );

            return $statement->fresh(['items', 'invoice', 'checkoutReading']);
        }, 3);
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
            ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount));
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

    /**
     * Phân bổ tiền cọc vào các hóa đơn còn nợ theo thứ tự cũ nhất trước.
     * Phiếu điều chỉnh có mã cố định để thao tác có thể chạy lại an toàn.
     *
     * @return array{applied_amount: float, refund_amount: float, remaining_debt: float, previous_outstanding: float}
     */
    private function applyDepositOffset(
        Contract $contract,
        float $depositCredit,
        ?int $settlementInvoiceId,
    ): array {
        $depositRemaining = max(0, round($depositCredit));
        $applied = 0.0;
        $remainingDebt = 0.0;
        $previousOutstanding = 0.0;

        $invoices = Invoice::query()
            ->where('contract_id', $contract->id)
            ->where('invoice_type', '!=', Invoice::TYPE_DEPOSIT)
            ->whereNotIn('status', [Invoice::STATUS_CANCELLED, Invoice::STATUS_WRITTEN_OFF])
            ->with(['payments', 'adjustments'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($invoices as $invoice) {
            $code = self::DEPOSIT_OFFSET_PREFIX.$contract->id.'-'.$invoice->id;
            $offset = $invoice->adjustments->firstWhere('adjustment_code', $code);

            // Hóa đơn đã thanh toán theo dữ liệu cũ và không do tiền cọc bù trừ
            // không còn tham gia phân bổ.
            if ($invoice->status === Invoice::STATUS_PAID && ! $offset) {
                continue;
            }

            $otherAdjustments = $invoice->adjustments
                ->reject(fn (InvoiceAdjustment $item): bool => $item->adjustment_code === $code)
                ->sum(fn (InvoiceAdjustment $item): float => $item->signed_amount);
            $paid = (float) $invoice->payments
                ->where('status', 'success')
                ->sum('amount_paid');
            $beforeDeposit = max(0, round(
                (float) $invoice->total_amount + $otherAdjustments - $paid
            ));

            if ($invoice->id !== $settlementInvoiceId) {
                $previousOutstanding += $beforeDeposit;
            }

            $allocated = min($depositRemaining, $beforeDeposit);
            if ($allocated > 0) {
                $adjustment = $invoice->adjustments()->updateOrCreate(
                    ['adjustment_code' => $code],
                    [
                        'direction' => InvoiceAdjustment::DIRECTION_CREDIT,
                        'amount' => $allocated,
                        'reason' => 'Tự động bù trừ tiền cọc khi quyết toán hợp đồng.',
                        'created_by' => null,
                    ]
                );
                if (! $offset) {
                    $invoice->setRelation('adjustments', $invoice->adjustments->push($adjustment));
                }
            } elseif ($offset) {
                $offset->delete();
            }

            $adjustmentAmount = (float) $invoice->adjustments()->get()
                ->sum(fn (InvoiceAdjustment $item): float => $item->signed_amount);
            $invoice->forceFill(['adjustment_amount' => $adjustmentAmount])->save();
            $invoice->refreshStatus();

            $depositRemaining -= $allocated;
            $applied += $allocated;
            $remainingDebt += max(0, $beforeDeposit - $allocated);
        }

        return [
            'applied_amount' => round($applied),
            'refund_amount' => round($depositRemaining),
            'remaining_debt' => round($remainingDebt),
            'previous_outstanding' => round($previousOutstanding),
        ];
    }

    private function syncDepositResolution(Contract $contract, float $applied, float $refund): void
    {
        if ((float) $contract->deposit_amount <= 0) {
            return;
        }

        // Không thay đổi quyết định sau khi Admin đã duyệt hoặc đã chuyển tiền.
        if (in_array($contract->deposit_status, [
            Contract::DEPOSIT_REFUND_APPROVED,
            Contract::DEPOSIT_REFUND_PROCESSING,
            Contract::DEPOSIT_RETURNED,
            Contract::DEPOSIT_PARTIAL,
            Contract::DEPOSIT_FORFEITED,
            Contract::DEPOSIT_REFUNDED,
            Contract::DEPOSIT_RETAINED,
        ], true)) {
            return;
        }

        $common = [
            'deposit_deduction_amount' => $applied,
            'deposit_refund_amount' => $refund,
            'deposit_processed_at' => now(),
            'deposit_process_reason' => 'Tiền cọc được tự động bù trừ vào công nợ khi quyết toán.',
        ];

        if ($refund > 0) {
            $status = in_array($contract->deposit_status, [
                Contract::DEPOSIT_REFUND_REQUESTED,
                Contract::DEPOSIT_REFUND_REJECTED,
            ], true) ? $contract->deposit_status : Contract::DEPOSIT_NEEDS_RESOLUTION;

            $contract->forceFill($common + [
                'deposit_status' => $status,
                'deposit_resolution' => null,
                'deposit_resolved_at' => null,
                'deposit_resolved_by' => null,
            ])->save();

            return;
        }

        $contract->forceFill($common + [
            'deposit_status' => Contract::DEPOSIT_DEDUCTED,
            'deposit_resolution' => Contract::DEPOSIT_DEDUCTED,
            'deposit_resolved_at' => now(),
        ])->save();
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
            'amount' => round($quantity * $unitPrice),
            'note' => $note,
            'sort_order' => $sortOrder,
        ];
    }
}

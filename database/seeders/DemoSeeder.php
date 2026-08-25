<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\SettlementStatement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    /**
     * Bộ dữ liệu demo duy nhất của ứng dụng.
     *
     * Có thể chạy độc lập sau khi đã migrate hoặc thông qua `migrate:fresh --seed`.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AvailableTenantSeeder::class,
            AmenitySeeder::class,
            SettingSeeder::class,
            BusinessScenarioSeeder::class,
            EndOfContractTestSeeder::class,
        ]);

        $this->enrichEndOfContractDocuments();
    }

    /** Bổ sung biên bản bàn giao và bảng quyết toán cho các trạng thái cuối hợp đồng. */
    private function enrichEndOfContractDocuments(): void
    {
        DB::transaction(function (): void {
            $cancelled = Contract::query()->where('contract_code', 'QA-16-CANCELLED')->first();
            $cancelled?->forceFill([
                'deposit_status' => Contract::DEPOSIT_NEEDS_RESOLUTION,
                'deposit_paid_at' => now()->subMonths(2),
                'deposit_process_reason' => 'Hợp đồng đã hủy sau khi thu cọc, đang chờ thống nhất phương án hoàn cọc.',
            ])->save();

            $definitions = [
                'QA-09-SETTLING' => ['mode' => 'outstanding', 'damage' => false],
                'QA-10-REFUND-REQUESTED' => ['mode' => 'refund', 'damage' => false],
                'QA-11-REFUND-APPROVED' => ['mode' => 'refund', 'damage' => false],
                'QA-12-REFUND-PROCESSING' => ['mode' => 'refund', 'damage' => false],
                'QA-13-COMPLETED-REFUNDED' => ['mode' => 'completed_refund', 'damage' => false],
                'QA-14-COMPLETED-DEDUCTED' => ['mode' => 'completed_deducted', 'damage' => true],
                'QA-15-COMPLETED-RETAINED' => ['mode' => 'completed_retained', 'damage' => true],
            ];

            foreach ($definitions as $code => $options) {
                $contract = Contract::query()
                    ->with(['handoverItems', 'invoices.payments', 'invoices.adjustments'])
                    ->where('contract_code', $code)
                    ->firstOrFail();

                $this->seedCheckoutReport($contract, $options['damage']);
                $this->seedSettlementStatement($contract, $options['mode']);
            }
        });
    }

    private function seedCheckoutReport(Contract $contract, bool $hasDamage): void
    {
        $proofPath = null;
        if ($hasDamage) {
            $proofPath = "demo/checkout/contract-{$contract->id}.png";
            Storage::disk('local')->put($proofPath, $this->demoPng());
        }

        $assetReport = $contract->handoverItems->values()->map(function ($item, int $index) use ($hasDamage): array {
            $damaged = $hasDamage && $index === 0;

            return [
                'handover_item_id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'condition' => $damaged ? 'damaged' : 'good',
                'note' => $damaged ? 'Có hư hỏng, đã lập biên bản và ghi nhận chi phí xử lý.' : 'Đã kiểm tra, hoạt động bình thường.',
            ];
        })->all();

        $contract->forceFill([
            'checkout_key_count' => 2,
            'checkout_asset_report' => $assetReport,
            'checkout_damage_note' => $hasDamage ? 'Một tài sản hư hỏng vượt quá hao mòn thông thường.' : null,
            'checkout_photo_paths' => $proofPath ? [$proofPath] : [],
            'checkout_handover_confirmed_at' => $contract->actual_move_out_at ?? now()->subDays(3),
        ])->save();
    }

    private function seedSettlementStatement(Contract $contract, string $mode): void
    {
        $deposit = (float) $contract->deposit_amount;
        $previousOutstanding = $mode === 'outstanding'
            ? round((float) $contract->invoices
                ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount), 2)
            : 0.0;
        $finalCharge = match ($mode) {
            'completed_deducted', 'completed_retained' => $deposit,
            default => 0.0,
        };
        $rawNet = round($previousOutstanding + $finalCharge - $deposit, 2);
        $completed = str_starts_with($mode, 'completed_');
        $status = $completed
            ? SettlementStatement::STATUS_SETTLED
            : ($rawNet > 0 ? SettlementStatement::STATUS_AWAITING_PAYMENT
                : ($rawNet < 0 ? SettlementStatement::STATUS_AWAITING_REFUND : SettlementStatement::STATUS_BALANCED));

        $statement = SettlementStatement::query()->updateOrCreate(
            ['contract_id' => $contract->id],
            [
                'invoice_id' => null,
                'checkout_reading_id' => null,
                'status' => $status,
                'final_charge_amount' => $finalCharge,
                'previous_outstanding_amount' => $previousOutstanding,
                'deposit_credit' => $deposit,
                'net_amount' => $completed ? 0 : $rawNet,
                'calculated_at' => $contract->actual_move_out_at ?? now()->subDays(3),
            ]
        );

        $statement->items()->delete();
        if ($finalCharge > 0) {
            $statement->items()->create([
                'type' => 'adjustment',
                'name' => $mode === 'completed_retained' ? 'Bồi thường tài sản theo biên bản' : 'Khấu trừ chi phí theo biên bản bàn giao',
                'quantity' => 1,
                'unit' => 'lần',
                'unit_price' => $finalCharge,
                'amount' => $finalCharge,
                'note' => 'Khoản tiền minh họa có biên bản bàn giao và ảnh hiện trạng đi kèm.',
                'sort_order' => 1,
            ]);
        }

        $eligibleRefund = max(0, -$rawNet);
        if ($mode === 'outstanding') {
            $contract->forceFill([
                'deposit_status' => Contract::DEPOSIT_DEDUCTED,
                'deposit_resolution' => Contract::DEPOSIT_DEDUCTED,
                'deposit_deduction_amount' => $deposit,
                'deposit_refund_amount' => 0,
                'deposit_resolved_at' => now()->subDays(2),
                'deposit_process_reason' => 'Tiền cọc đã được bù trừ vào công nợ; khách còn thanh toán phần chênh lệch.',
            ])->save();
        }

        if (in_array($mode, ['refund', 'completed_refund'], true)) {
            $values = [
                'deposit_refund_amount' => $eligibleRefund,
                'deposit_deduction_amount' => max(0, $deposit - $eligibleRefund),
                'deposit_process_reason' => 'Hoàn số dư tiền cọc sau khi đối chiếu bảng quyết toán.',
            ];
            if ($mode === 'completed_refund') {
                $proofPath = "demo/deposit-refunds/contract-{$contract->id}.png";
                Storage::disk('public')->put($proofPath, $this->demoPng());
                $values += [
                    'deposit_status' => Contract::DEPOSIT_REFUNDED,
                    'deposit_resolution' => Contract::DEPOSIT_REFUNDED,
                    'deposit_transfer_amount' => $eligibleRefund,
                    'deposit_transferred_at' => now()->subDay(),
                    'deposit_transfer_proof' => $proofPath,
                ];
            }
            $contract->forceFill($values)->save();
        }

        if (in_array($mode, ['completed_deducted', 'completed_retained'], true)) {
            $proofPath = "demo/deposit-deductions/contract-{$contract->id}.png";
            Storage::disk('public')->put($proofPath, $this->demoPng());
            $resolution = $mode === 'completed_retained' ? Contract::DEPOSIT_RETAINED : Contract::DEPOSIT_DEDUCTED;
            $contract->forceFill([
                'deposit_status' => $resolution,
                'deposit_resolution' => $resolution,
                'deposit_refund_amount' => 0,
                'deposit_deduction_amount' => $deposit,
                'deposit_damage_proof' => $proofPath,
                'deposit_process_reason' => 'Xử lý tiền cọc theo biên bản bàn giao và chứng từ thiệt hại.',
                'settlement_note' => 'Đã hoàn tất đối chiếu công nợ, tài sản và tiền cọc.',
            ])->save();
        }
    }

    private function demoPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=');
    }

}

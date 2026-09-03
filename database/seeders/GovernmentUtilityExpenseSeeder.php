<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernmentUtilityExpenseSeeder extends Seeder
{
    public function run(): void
    {
        // Đây chỉ là đơn giá vốn tạm thời của bộ dữ liệu demo, không phải cấu hình
        // cố định của ứng dụng. Giá thực tế vẫn có thể thay đổi theo từng kỳ.
        $electricityUnitPrice = 3000;
        $waterUnitPrice = 12000;

        $admin = User::query()
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Admin'))
            ->firstOrFail();

        DB::transaction(function () use ($admin, $electricityUnitPrice, $waterUnitPrice): void {
            Expense::query()->whereIn('expense_code', ['EXP-DEMO-001', 'EXP-DEMO-002'])->delete();

            $monthlyUsage = Invoice::query()
                ->with('utilityReading:id,electricity_old,electricity_new,water_old,water_new')
                ->where('invoice_type', Invoice::TYPE_RENTAL)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->whereNotNull('utility_reading_id')
                ->where(function ($query): void {
                    $query->where('invoice_code', 'like', 'INV-PAID-%')
                        ->orWhere('invoice_code', 'like', 'HDON-DEMO-%');
                })
                ->get()
                ->groupBy(fn (Invoice $invoice) => sprintf('%04d%02d', $invoice->year, $invoice->month));

            foreach ($monthlyUsage as $periodKey => $invoices) {
                $electricityUsage = $invoices->sum(fn (Invoice $invoice) => max(
                    0,
                    (float) $invoice->utilityReading->electricity_new
                        - (float) $invoice->utilityReading->electricity_old
                ));
                $waterUsage = $invoices->sum(fn (Invoice $invoice) => max(
                    0,
                    (float) $invoice->utilityReading->water_new
                        - (float) $invoice->utilityReading->water_old
                ));

                $year = (int) substr($periodKey, 0, 4);
                $month = (int) substr($periodKey, 4, 2);
                $expenseDate = now()->setDate($year, $month, 10)->startOfDay();

                // Không ghi nhận một khoản "đã chi" khi ngày thanh toán còn ở tương lai.
                if ($expenseDate->isFuture()) {
                    continue;
                }

                $this->upsertUtilityExpense(
                    "EXP-GOV-ELECTRICITY-{$periodKey}",
                    Expense::CATEGORY_ELECTRICITY,
                    "Thanh toán tiền điện nhà nước kỳ {$month}/{$year}",
                    $electricityUsage,
                    $electricityUnitPrice,
                    'kWh',
                    $expenseDate->toDateString(),
                    'Tổng công ty Điện lực Việt Nam (EVN)',
                    $admin
                );
                $this->upsertUtilityExpense(
                    "EXP-GOV-WATER-{$periodKey}",
                    Expense::CATEGORY_WATER,
                    "Thanh toán tiền nước nhà nước kỳ {$month}/{$year}",
                    $waterUsage,
                    $waterUnitPrice,
                    'm³',
                    $expenseDate->toDateString(),
                    'Đơn vị cấp nước địa phương',
                    $admin
                );
            }
        });
    }

    private function upsertUtilityExpense(
        string $code,
        string $category,
        string $title,
        float $usage,
        int $unitPrice,
        string $unit,
        string $expenseDate,
        string $payee,
        User $admin
    ): void {
        Expense::query()->updateOrCreate(
            ['expense_code' => $code],
            [
                'category' => $category,
                'title' => $title,
                'amount' => round($usage * $unitPrice),
                'expense_date' => $expenseDate,
                'room_id' => null,
                'support_request_id' => null,
                'payer_name' => $payee,
                'payment_method' => Expense::METHOD_BANK_TRANSFER,
                'receipt_image' => null,
                'notes' => sprintf(
                    'Đã thanh toán %.0f %s × %sđ/%s. Đơn giá vốn tạm thời của kỳ demo, có thể thay đổi ở kỳ sau.',
                    $usage,
                    $unit,
                    number_format($unitPrice, 0, ',', '.'),
                    $unit
                ),
                'created_by' => $admin->id,
            ]
        );
    }
}

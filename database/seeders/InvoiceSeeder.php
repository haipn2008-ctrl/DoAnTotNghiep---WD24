<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Contract;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = Contract::all();

        if ($contracts->count() > 0) {
            foreach ($contracts as $contract) {
                // Tạo 12 hóa đơn cho mỗi contract (1 hóa đơn/tháng)
                for ($month = 1; $month <= 12; $month++) {
                    $invoiceDate = Carbon::createFromDate(2026, $month, 1);
                    $amount = $contract->room->price;

                    Invoice::updateOrCreate(
                        ['contract_id' => $contract->id, 'invoice_date' => $invoiceDate->toDateString()],
                        [
                            'total_amount' => $amount,
                            'status' => rand(0, 1) ? 'paid' : 'unpaid'
                        ]
                    );
                }
            }
        }
    }
}

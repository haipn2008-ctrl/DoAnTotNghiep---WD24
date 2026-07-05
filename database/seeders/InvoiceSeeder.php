<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Setting;
use App\Models\UtilityReading;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $setting = Setting::firstOrCreate([], [
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);

        $contracts = Contract::with('room')->get();

        if ($contracts->count() > 0) {
            foreach ($contracts as $contract) {
                // Tạo 12 hóa đơn cho mỗi contract (1 hóa đơn/tháng)
                for ($month = 1; $month <= 12; $month++) {
                    $billingDate = Carbon::createFromDate(2026, $month, 1);

                    if (
                        $billingDate->lt(Carbon::parse($contract->start_date)->startOfMonth())
                        || $billingDate->gt(Carbon::parse($contract->end_date)->startOfMonth())
                    ) {
                        continue;
                    }

                    $utilityReading = UtilityReading::where('room_id', $contract->room_id)
                        ->where('month', $month)
                        ->where('year', 2026)
                        ->first();

                    $electricityUsage = $utilityReading
                        ? $utilityReading->electricity_new - $utilityReading->electricity_old
                        : 0;
                    $waterUsage = $utilityReading
                        ? $utilityReading->water_new - $utilityReading->water_old
                        : 0;
                    $electricityFee = $electricityUsage * $setting->electric_price;
                    $waterFee = $waterUsage * $setting->water_price;
                    $roomFee = $contract->monthly_rent;
                    $totalAmount = $roomFee + $electricityFee + $waterFee + $setting->internet_fee + $setting->service_fee;

                    Invoice::updateOrCreate(
                        [
                            'contract_id' => $contract->id,
                            'month' => $month,
                            'year' => 2026,
                        ],
                        [
                            'room_id' => $contract->room_id,
                            'utility_reading_id' => $utilityReading?->id,
                            'invoice_date' => $billingDate->copy()->endOfMonth()->toDateString(),
                            'due_date' => $billingDate->copy()->endOfMonth()->addDays(7)->toDateString(),
                            'room_fee' => $roomFee,
                            'electricity_fee' => $electricityFee,
                            'water_fee' => $waterFee,
                            'internet_fee' => $setting->internet_fee,
                            'service_fee' => $setting->service_fee,
                            'total_amount' => $totalAmount,
                            'status' => rand(0, 1) ? 'paid' : 'unpaid'
                        ]
                    );
                }
            }
        }
    }
}

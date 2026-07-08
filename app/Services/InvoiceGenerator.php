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
    /**
     * Xem trước hóa đơn.
     */
    public function preview(
        Contract $contract,
        int $month,
        int $year
    ): array {

        $contract->loadMissing([
            'room',
            'tenant'
        ]);

        $this->ensureContractCanBeBilled(
            $contract,
            $month,
            $year
        );

        /*
        |--------------------------------------------------------------------------
        | Lấy kỳ điện nước
        |--------------------------------------------------------------------------
        */

        $reading = UtilityReading::where('room_id', $contract->room_id)
            ->where('month', $month)
            ->where('year', $year)
            ->confirmed()
            ->first();

        if (!$reading) {

            throw ValidationException::withMessages([
                'utility' => "Phòng {$contract->room->room_code} chưa chốt điện nước tháng {$month}/{$year}."
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra đã sinh hóa đơn chưa
        |--------------------------------------------------------------------------
        */

        if (
            Invoice::where('room_id', $contract->room_id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists()
        ) {

            throw ValidationException::withMessages([
                'invoice' => "Hóa đơn tháng {$month}/{$year} đã tồn tại."
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Lấy cấu hình hệ thống
        |--------------------------------------------------------------------------
        */

        $setting = Setting::first();

        if (!$setting) {

            throw ValidationException::withMessages([
                'setting' => 'Chưa cấu hình giá điện nước.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Ngày lập hóa đơn
        |--------------------------------------------------------------------------
        */

        $invoiceDate = Carbon::create(
            $year,
            $month,
            1
        )->endOfMonth();

        $dueDate = $invoiceDate
            ->copy()
            ->addDays(
                $setting->payment_due_days ?? 10
            );

        /*
        |--------------------------------------------------------------------------
        | Tính tiền điện nước
        |--------------------------------------------------------------------------
        */

        $electricUsage = $reading->electric_usage;
        $waterUsage = $reading->water_usage;

        $electricAmount = $reading->electric_amount;
        $waterAmount = $reading->water_amount;

        /*
        |--------------------------------------------------------------------------
        | Danh sách chi tiết hóa đơn
        |--------------------------------------------------------------------------
        */

        $details = [

            [
                'type' => 'room',
                'name' => 'Tiền thuê phòng',

                'quantity' => 1,
                'unit' => 'tháng',

                'unit_price' => $contract->monthly_rent,

                'amount' => $contract->monthly_rent,

                'old_index' => null,
                'new_index' => null,

                'note' => "Hợp đồng {$contract->contract_code}",

                'sort_order' => 1,
            ],

            [
                'type' => 'electric',

                'name' => 'Tiền điện',

                'quantity' => $electricUsage,

                'unit' => 'kWh',

                'unit_price' => $reading->electric_unit_price,

                'amount' => $electricAmount,

                'old_index' => $reading->electric_old,

                'new_index' => $reading->electric_new,

                'note' => null,

                'sort_order' => 2,
            ],

            [
                'type' => 'water',

                'name' => 'Tiền nước',

                'quantity' => $waterUsage,

                'unit' => 'm³',

                'unit_price' => $reading->water_unit_price,

                'amount' => $waterAmount,

                'old_index' => $reading->water_old,

                'new_index' => $reading->water_new,

                'note' => null,

                'sort_order' => 3,
            ],
                    /*
        |--------------------------------------------------------------------------
        | Internet
        |--------------------------------------------------------------------------
        */

        if (($setting->internet_fee ?? 0) > 0) {

            $details[] = [

                'type' => 'internet',

                'name' => 'Phí Internet',

                'quantity' => 1,

                'unit' => 'tháng',

                'unit_price' => $setting->internet_fee,

                'amount' => $setting->internet_fee,

                'old_index' => null,

                'new_index' => null,

                'note' => null,

                'sort_order' => 4,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Dịch vụ
        |--------------------------------------------------------------------------
        */

        if (($setting->service_fee ?? 0) > 0) {

            $details[] = [

                'type' => 'service',

                'name' => 'Phí dịch vụ',

                'quantity' => 1,

                'unit' => 'tháng',

                'unit_price' => $setting->service_fee,

                'amount' => $setting->service_fee,

                'old_index' => null,

                'new_index' => null,

                'note' => null,

                'sort_order' => 5,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Phí gửi xe (nếu có)
        |--------------------------------------------------------------------------
        */

        if (
            property_exists($setting, 'parking_fee')
            && ($setting->parking_fee ?? 0) > 0
        ) {

            $details[] = [

                'type' => 'other',

                'name' => 'Phí gửi xe',

                'quantity' => 1,

                'unit' => 'tháng',

                'unit_price' => $setting->parking_fee,

                'amount' => $setting->parking_fee,

                'old_index' => null,

                'new_index' => null,

                'note' => null,

                'sort_order' => 6,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Tính tổng
        |--------------------------------------------------------------------------
        */

        $roomFee = collect($details)
            ->where('type', 'room')
            ->sum('amount');

        $electricFee = collect($details)
            ->where('type', 'electric')
            ->sum('amount');

        $waterFee = collect($details)
            ->where('type', 'water')
            ->sum('amount');

        $internetFee = collect($details)
            ->where('type', 'internet')
            ->sum('amount');

        $serviceFee = collect($details)
            ->whereIn('type', [
                'service',
                'other'
            ])
            ->sum('amount');

        $total = collect($details)
            ->sum('amount');

        return [

            'contract' => $contract,

            'room' => $contract->room,

            'tenant' => $contract->tenant,

            'reading' => $reading,

            'month' => $month,

            'year' => $year,

            'invoice_date' => $invoiceDate->toDateString(),

            'due_date' => $dueDate->toDateString(),

            'room_fee' => $roomFee,

            'electricity_fee' => $electricFee,

            'water_fee' => $waterFee,

            'internet_fee' => $internetFee,

            'service_fee' => $serviceFee,

            'total_amount' => $total,

            'status' => Invoice::STATUS_UNPAID,

            'details' => $details,
        ];
    }
        /*
    |--------------------------------------------------------------------------
    | Sinh hóa đơn
    |--------------------------------------------------------------------------
    */

    public function issue(
        Contract $contract,
        int $month,
        int $year
    ): Invoice {

        return DB::transaction(function () use (
            $contract,
            $month,
            $year
        ) {

            $preview = $this->preview(
                $contract,
                $month,
                $year
            );

            /*
            |--------------------------------------------------------------------------
            | Tạo hóa đơn
            |--------------------------------------------------------------------------
            */

            $invoice = Invoice::create([

                'contract_id' => $preview['contract']->id,

                'room_id' => $preview['room']->id,

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

                'status' => Invoice::STATUS_UNPAID,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Lưu chi tiết hóa đơn
            |--------------------------------------------------------------------------
            */

            foreach ($preview['details'] as $detail) {

                $invoice->details()->create($detail);
            }

            /*
            |--------------------------------------------------------------------------
            | Load relationship
            |--------------------------------------------------------------------------
            */

            return $invoice->load([

                'contract',

                'contract.tenant',

                'room',

                'utilityReading',

                'details',
            ]);
        });
    }
        /*
    |--------------------------------------------------------------------------
    | Kiểm tra hợp đồng có được phép sinh hóa đơn
    |--------------------------------------------------------------------------
    */

    private function ensureContractCanBeBilled(
        Contract $contract,
        int $month,
        int $year
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Hợp đồng phải đang hiệu lực
        |--------------------------------------------------------------------------
        */

        if ($contract->status !== 'active') {

            throw ValidationException::withMessages([
                'contract' => 'Chỉ hợp đồng đang hiệu lực mới được sinh hóa đơn.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Kỳ cần sinh hóa đơn
        |--------------------------------------------------------------------------
        */

        $periodStart = Carbon::create(
            $year,
            $month,
            1
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Hợp đồng phải còn hiệu lực trong kỳ
        |--------------------------------------------------------------------------
        */

        if (

            Carbon::parse($contract->start_date)->gt($periodEnd)

            ||

            Carbon::parse($contract->end_date)->lt($periodStart)

        ) {

            throw ValidationException::withMessages([

                'contract' => "Hợp đồng {$contract->contract_code} không còn hiệu lực trong kỳ {$month}/{$year}."

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Không cho sinh hóa đơn 2 lần
        |--------------------------------------------------------------------------
        */

        if (

            Invoice::where('room_id', $contract->room_id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists()

        ) {

            throw ValidationException::withMessages([

                'invoice' => 'Hóa đơn tháng này đã được tạo.'

            ]);
        }
    }
}

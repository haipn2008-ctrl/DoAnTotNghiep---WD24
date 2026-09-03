<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RecentPaidContractsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->whereHas('role', fn ($query) => $query->where('role_name', 'Admin'))->firstOrFail();
            $roleId = Role::query()->where('role_name', 'User')->value('id');
            $setting = Setting::currentOrCreate();
            $fees = FeeSchedule::forPeriod(now()) ?? FeeSchedule::query()->latest('effective_from')->firstOrFail();
            $assets = Amenity::query()
                ->active()
                ->assets()
                ->whereIn('name', DefaultRoomAssetsSeeder::DEFAULT_ASSETS)
                ->get();

            $profiles = [
                ['Nguyễn Minh Khôi', 'nguyenminhkhoi@gmail.com', '0985124101', '001096141101', '1996-01-14', 'male'],
                ['Trần Thùy Linh', 'tranthuylinh@gmail.com', '0985124102', '001299221102', '1999-02-22', 'female'],
                ['Phạm Quốc Bảo', 'phamquocbao@gmail.com', '0985124103', '038098131103', '1998-03-13', 'male'],
                ['Lê Ngọc Mai', 'lengocmai@gmail.com', '0985124104', '031200251104', '2000-05-25', 'female'],
                ['Vũ Đức Anh', 'vuducanh@gmail.com', '0985124105', '036097091105', '1997-09-09', 'male'],
            ];

            foreach ($profiles as $index => [$name, $email, $phone, $cccd, $birthDate, $gender]) {
                $sequence = $index + 1;
                $start = now()->subMonthsNoOverflow(5 - $index)->startOfMonth();
                $room = Room::query()->updateOrCreate(
                    ['room_code' => sprintf('P4%02d', $sequence)],
                    [
                        'floor' => 4,
                        'price' => 3400000 + ($index * 150000),
                        'area' => 24 + $index,
                        'max_people' => 3,
                        'current_people' => 1,
                        'status' => Room::STATUS_OCCUPIED,
                        'description' => 'Phòng đang được thuê, dữ liệu lịch sử thanh toán đầy đủ.',
                    ]
                );
                $room->amenities()->sync($assets->mapWithKeys(fn (Amenity $asset) => [$asset->id => [
                    'quantity' => 1, 'condition' => 'normal', 'note' => null,
                ]])->all());

                $user = User::query()->updateOrCreate(['email' => $email], [
                    'name' => $name,
                    'phone' => $phone,
                    'password' => Hash::make('Tenant@123456'),
                    'role_id' => $roleId,
                    'status' => User::STATUS_ACTIVE,
                    'activated_at' => $start,
                    'must_change_password' => false,
                ]);
                $tenant = Tenant::query()->updateOrCreate(['cccd' => $cccd], [
                    'user_id' => $user->id,
                    'status' => Tenant::STATUS_ACTIVE,
                    'full_name' => $name,
                    'date_of_birth' => $birthDate,
                    'gender' => $gender,
                    'cccd_issue_date' => Carbon::parse($birthDate)->addYears(20),
                    'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                    'phone' => $phone,
                    'email' => $email,
                    'address' => 'Hà Nội',
                ]);

                $note = 'Hợp đồng demo thanh toán đầy đủ số '.$sequence;
                $contract = Contract::query()->where('note', $note)->first();
                if (! $contract) {
                    $contract = new Contract();
                    $contract->forceFill(['contract_code' => 'HD-SEED-PAID-'.$sequence]);
                }
                $content = '<h1>HỢP ĐỒNG THUÊ PHÒNG</h1><p>Hợp đồng thuê phòng của '.$name.'.</p>';
                $contract->forceFill([
                    'room_id' => $room->id,
                    'tenant_id' => $tenant->id,
                    'representative_tenant_id' => $tenant->id,
                    'monthly_rent' => $room->price,
                    'deposit_amount' => $room->price,
                    'deposit_status' => Contract::DEPOSIT_PAID,
                    'deposit_paid_at' => $start,
                    'number_of_people' => 1,
                    'internet_enabled' => true,
                    'service_enabled' => true,
                    'start_date' => $start,
                    'end_date' => $start->copy()->addYear(),
                    'signed_at' => $start->copy()->subDays(3),
                    'signed_confirmed_by' => $admin->id,
                    'actual_move_in_at' => $start->copy()->setTime(9, 0),
                    'checked_in_by' => $admin->id,
                    'status' => Contract::STATUS_ACTIVE,
                    'electric_price_snapshot' => $setting->electric_price,
                    'water_price_snapshot' => $setting->water_price,
                    'internet_fee_snapshot' => $setting->internet_fee,
                    'service_fee_snapshot' => $setting->service_fee,
                    'contract_content' => $content,
                    'contract_content_snapshotted_at' => $start->copy()->subDays(3),
                    'contract_content_sha256' => hash('sha256', $content),
                    'note' => $note,
                ])->save();
                $contract->forceFill([
                    'contract_code' => 'HD'.str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT),
                ])->save();

                ContractTenant::query()->updateOrCreate(
                    ['contract_id' => $contract->id, 'tenant_id' => $tenant->id],
                    [
                        'role' => ContractTenant::ROLE_REPRESENTATIVE,
                        'full_name' => $name,
                        'date_of_birth' => $birthDate,
                        'identity_number' => $cccd,
                        'phone' => $phone,
                        'address' => 'Hà Nội',
                        'status' => ContractTenant::STATUS_CHECKED_IN,
                        'actual_move_in_at' => $start->copy()->setTime(9, 0),
                        'vehicle_declaration_status' => ContractTenant::VEHICLE_NONE,
                    ]
                );

                $this->seedPaidBilling($contract, $room, $user, $admin, $fees, $start, $sequence);
            }
        });
    }

    public function seedPaidBilling(Contract $contract, Room $room, User $user, User $admin, FeeSchedule $fees, Carbon $start, int $sequence): void
    {
        $electricity = 1000 + ($sequence * 100);
        $water = 200 + ($sequence * 20);

        $lastClosedMonth = now()->subMonthNoOverflow()->startOfMonth();
        $unclosedDemoRooms = ['P104', 'P202', 'P404'];
        for ($period = $start->copy(); $period->lte($lastClosedMonth); $period->addMonthNoOverflow()) {
            $usageElectricity = 55 + ($sequence * 3);
            $usageWater = 5 + $sequence;
            $isUnclosedDemoPeriod = $period->isSameMonth($lastClosedMonth)
                && in_array($room->room_code, $unclosedDemoRooms, true);

            if ($isUnclosedDemoPeriod) {
                $existingDraft = UtilityReading::query()
                    ->where('contract_id', $contract->id)
                    ->where('month', $period->month)
                    ->where('year', $period->year)
                    ->where('reading_type', 'periodic')
                    ->first();

                if ($existingDraft?->isDraft() && ! $existingDraft->invoices()->exists()) {
                    $existingDraft->delete();
                }

                continue;
            }

            $reading = UtilityReading::query()->updateOrCreate([
                'contract_id' => $contract->id,
                'month' => $period->month,
                'year' => $period->year,
                'reading_type' => 'periodic',
            ], [
                'room_id' => $room->id,
                'record_date' => $period->copy()->endOfMonth(),
                'electricity_old' => $electricity,
                'electricity_new' => $electricity + $usageElectricity,
                'water_old' => $water,
                'water_new' => $water + $usageWater,
                // Kỳ gần nhất phải ở trạng thái đã chốt để màn sinh hóa đơn có thể sử dụng.
                // Các kỳ cũ hơn đã có hóa đơn nên được khóa.
                'status' => $period->isSameMonth($lastClosedMonth)
                    ? UtilityReading::STATUS_CONFIRMED
                    : UtilityReading::STATUS_LOCKED,
                'note' => 'Chỉ số đã chốt và thanh toán đầy đủ.',
            ]);
            $electricity += $usageElectricity;
            $water += $usageWater;

            // Chỉ số tháng gần nhất dành cho hóa đơn phát hành trong tháng hiện tại.
            // Không phát hành sẵn để quản trị viên còn có thể thao tác luồng demo thực tế.
            if ($period->isSameMonth($lastClosedMonth)) {
                continue;
            }

            $electricityFee = $usageElectricity * (float) $fees->electric_price;
            $waterFee = $usageWater * (float) $fees->water_price;
            $total = (float) $contract->monthly_rent + $electricityFee + $waterFee
                + (float) $fees->internet_fee + (float) $fees->service_fee;
            $billingPeriod = $period->copy()->addMonthNoOverflow();
            $periodKey = $billingPeriod->format('Ym');
            $invoice = Invoice::query()->updateOrCreate(
                ['invoice_code' => "INV-PAID-{$contract->id}-{$periodKey}"],
                [
                    'contract_id' => $contract->id,
                    'room_id' => $room->id,
                    'utility_reading_id' => $reading->id,
                    'fee_schedule_id' => $fees->id,
                    'invoice_type' => Invoice::TYPE_RENTAL,
                    'revision' => 1,
                    'month' => $billingPeriod->month,
                    'year' => $billingPeriod->year,
                    'invoice_date' => $billingPeriod->copy()->day(5),
                    'issued_at' => $billingPeriod->copy()->day(5)->setTime(8, 0),
                    'issued_by' => $admin->id,
                    'due_date' => $billingPeriod->copy()->day(10),
                    'room_fee' => $contract->monthly_rent,
                    'electricity_fee' => $electricityFee,
                    'water_fee' => $waterFee,
                    'internet_fee' => $fees->internet_fee,
                    'service_fee' => $fees->service_fee,
                    'total_amount' => $total,
                    'adjustment_amount' => 0,
                    'status' => Invoice::STATUS_PAID,
                ]
            );

            $details = [
                ['room', 'Tiền phòng tháng '.$period->format('m/Y'), 1, 'tháng', (float) $contract->monthly_rent],
                ['electricity', 'Tiền điện tháng '.$period->format('m/Y'), $usageElectricity, 'kWh', (float) $fees->electric_price],
                ['water', 'Tiền nước tháng '.$period->format('m/Y'), $usageWater, 'm³', (float) $fees->water_price],
                ['internet', 'Phí Internet tháng '.$period->format('m/Y'), 1, 'tháng', (float) $fees->internet_fee],
                ['service', 'Phí dịch vụ tháng '.$period->format('m/Y'), 1, 'người', (float) $fees->service_fee],
            ];
            foreach ($details as $sort => [$type, $name, $quantity, $unit, $unitPrice]) {
                InvoiceDetail::query()->updateOrCreate(
                    ['invoice_id' => $invoice->id, 'type' => $type],
                    ['name' => $name, 'quantity' => $quantity, 'unit' => $unit, 'unit_price' => $unitPrice, 'amount' => $quantity * $unitPrice, 'sort_order' => $sort + 1]
                );
            }

            Payment::query()->updateOrCreate(
                ['transaction_code' => "GD-PAID-{$contract->id}-{$periodKey}"],
                [
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $total,
                    'payment_date' => $billingPeriod->copy()->day(8),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'status' => Payment::STATUS_SUCCESS,
                    'submitted_by' => $user->id,
                    'confirmed_by' => $admin->id,
                    'reviewed_at' => $billingPeriod->copy()->day(8)->setTime(10, 0),
                    'note' => 'Khách đã thanh toán đủ hóa đơn tháng '.$period->format('m/Y').'.',
                ]
            );
        }
    }
}

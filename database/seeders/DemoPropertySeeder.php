<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractOccupant;
use App\Models\ContractOccupantHistory;
use App\Models\ContractStatusHistory;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoPropertySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rooms = $this->seedRooms();
            $tenants = $this->seedTenants();
            $members = $this->seedHouseholdMembers();
            $contracts = $this->seedContracts($rooms, $tenants, $members);
            $this->seedBillingHistory($contracts);
            $this->seedSupportRequests($contracts);
        });
    }

    private function seedRooms(): array
    {
        $definitions = [
            ['A101', 1, 3200000, 24, 2, Room::STATUS_OCCUPIED, [], ['Ghế' => ['quantity' => 2]], 'Phòng góc tầng một, cửa sổ lớn hướng sân trong, phù hợp một đến hai người.'],
            ['A102', 1, 3500000, 27, 2, Room::STATUS_OCCUPIED, [], ['Ghế' => ['quantity' => 2], 'Bàn' => ['quantity' => 2]], 'Phòng thoáng, có khu bếp riêng và bàn làm việc cạnh cửa sổ.'],
            ['A103', 1, 3000000, 22, 1, Room::STATUS_AVAILABLE, ['Máy giặt', 'Bếp điện'], [], 'Phòng studio yên tĩnh, vừa sơn mới, sẵn sàng nhận khách.'],
            ['A104', 1, 3300000, 25, 2, Room::STATUS_OCCUPIED, [], ['Bếp điện' => ['condition' => 'damaged', 'note' => 'Mặt bếp bị nứt, chờ thay mới.']], 'Phòng gần lối để xe, thuận tiện cho người thường xuyên đi làm sớm.'],
            ['B201', 2, 3800000, 30, 3, Room::STATUS_OCCUPIED, [], ['Giường' => ['quantity' => 2], 'Ghế' => ['quantity' => 3], 'Tủ quần áo' => ['quantity' => 2]], 'Phòng rộng hướng đông, đón nắng sáng và nhiều ánh sáng tự nhiên.'],
            ['B202', 2, 3600000, 28, 2, Room::STATUS_OCCUPIED, [], ['Ghế' => ['quantity' => 2]], 'Phòng có máy giặt riêng và khu phơi đồ kín đáo.'],
            ['B203', 2, 3400000, 26, 2, Room::STATUS_MAINTENANCE, ['Máy giặt'], ['Bình nóng lạnh' => ['condition' => 'damaged', 'note' => 'Đang rò nước, đã lên lịch sửa chữa.']], 'Đang bảo trì đường nước và thay mới thiết bị vệ sinh.'],
            ['B204', 2, 3700000, 29, 2, Room::STATUS_OCCUPIED, [], ['Ghế' => ['quantity' => 2]], 'Phòng cuối hành lang, ít tiếng ồn, không gian thoáng.'],
            ['C301', 3, 4200000, 34, 3, Room::STATUS_OCCUPIED, [], ['Giường' => ['quantity' => 2], 'Ghế' => ['quantity' => 3], 'Tủ quần áo' => ['quantity' => 2]], 'Căn phòng lớn nhất tầng ba, phù hợp gia đình trẻ hoặc nhóm hai người.'],
            ['C302', 3, 3900000, 30, 2, Room::STATUS_AVAILABLE, [], ['Ghế' => ['quantity' => 2]], 'Phòng có nội thất cơ bản đầy đủ và có thể vào ở ngay.'],
            ['C303', 3, 4000000, 32, 2, Room::STATUS_AVAILABLE, [], ['Ghế' => ['quantity' => 2], 'Bàn' => ['quantity' => 2]], 'Phòng hai mặt thoáng, khu vực sinh hoạt rộng.'],
            ['C304', 3, 3800000, 29, 2, Room::STATUS_AVAILABLE, ['Tủ lạnh'], ['Ghế' => ['quantity' => 2]], 'Phòng gọn gàng, phù hợp nhân viên văn phòng cần không gian yên tĩnh.'],
        ];

        $assets = Amenity::query()->active()->assets()->get()->keyBy('name');
        $defaultInventory = $assets->mapWithKeys(fn (Amenity $asset): array => [
            $asset->id => [
                'quantity' => $asset->name === 'Ghế' ? 2 : 1,
                'condition' => 'normal',
                'note' => null,
            ],
        ])->all();

        $rooms = [];
        foreach ($definitions as [$code, $floor, $price, $area, $people, $status, $missingAssets, $overrides, $description]) {
            $room = Room::updateOrCreate(
                ['room_code' => $code],
                [
                    'floor' => $floor,
                    'price' => $price,
                    'area' => $area,
                    'max_people' => max(2, $people),
                    'current_people' => $status === Room::STATUS_OCCUPIED ? $people : 0,
                    'status' => $status,
                    'description' => $description,
                ]
            );

            $inventory = $defaultInventory;
            foreach ($missingAssets as $assetName) {
                if ($asset = $assets->get($assetName)) {
                    unset($inventory[$asset->id]);
                }
            }
            foreach ($overrides as $assetName => $values) {
                if ($asset = $assets->get($assetName)) {
                    $inventory[$asset->id] = array_merge($inventory[$asset->id], $values);
                }
            }

            $room->amenities()->sync($inventory);
            $rooms[$code] = $room;
        }

        return $rooms;
    }

    private function seedTenants(): array
    {
        $definitions = [
            ['giahuy@example.test', 'Phạm Gia Huy', '1997-03-18', 'male', '038097001234', '2021-04-12', 'Cục Cảnh sát QLHC về TTXH', 'Hạ Long, Quảng Ninh'],
            ['ngocmai@example.test', 'Lê Ngọc Mai', '1999-11-02', 'female', '001199008765', '2022-01-20', 'Cục Cảnh sát QLHC về TTXH', 'Thanh Xuân, Hà Nội'],
            ['ducanh@example.test', 'Vũ Đức Anh', '1996-07-25', 'male', '031096004321', '2020-08-17', 'Công an TP Hải Phòng', 'Lê Chân, Hải Phòng'],
            ['khanhlinh@example.test', 'Đặng Khánh Linh', '2000-05-09', 'female', '034300006789', '2023-02-06', 'Cục Cảnh sát QLHC về TTXH', 'Nam Định, Nam Định'],
            ['quangnam@example.test', 'Bùi Quang Nam', '1995-12-14', 'male', '030095002468', '2019-10-24', 'Công an tỉnh Hải Dương', 'Chí Linh, Hải Dương'],
            ['thanhthao@example.test', 'Đỗ Thanh Thảo', '1998-08-21', 'female', '036198007531', '2021-11-15', 'Cục Cảnh sát QLHC về TTXH', 'Việt Trì, Phú Thọ'],
            ['tuankiet@example.test', 'Hoàng Tuấn Kiệt', '1994-01-30', 'male', '033094009876', '2020-06-09', 'Công an tỉnh Hưng Yên', 'Văn Lâm, Hưng Yên'],
            ['phuonguyen@example.test', 'Ngô Phương Uyên', '2001-09-12', 'female', '037301005432', '2023-05-18', 'Cục Cảnh sát QLHC về TTXH', 'Ninh Bình, Ninh Bình'],
        ];

        $tenants = [];
        foreach ($definitions as [$email, $name, $birthDate, $gender, $cccd, $issueDate, $issuePlace, $address]) {
            $user = User::where('email', $email)->sole();
            $tenant = Tenant::updateOrCreate(
                ['cccd' => $cccd],
                [
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'date_of_birth' => $birthDate,
                    'gender' => $gender,
                    'cccd_issue_date' => $issueDate,
                    'cccd_issue_place' => $issuePlace,
                    'phone' => $user->phone,
                    'email' => $email,
                    'address' => $address,
                ]
            );
            $tenants[$email] = $tenant;
        }

        return $tenants;
    }

    private function seedHouseholdMembers(): array
    {
        $definitions = [
            ['a101-1', 'Nguyễn Thu Hà', '1998-06-12', 'female', '001198010101', '0911000101', 'member.a101@example.test', 'Cầu Giấy, Hà Nội'],
            ['a102-1', 'Trần Minh Khôi', '1996-10-23', 'male', '001096010102', '0911000102', 'member.a102@example.test', 'Đống Đa, Hà Nội'],
            ['a104-1', 'Lê Hoàng An', '2000-01-15', 'other', '001200010104', '0911000104', 'member.a104@example.test', 'Hai Bà Trưng, Hà Nội'],
            ['b201-1', 'Phạm Thảo Vy', '1999-04-08', 'female', '001199020101', '0911000201', 'member1.b201@example.test', 'Nam Từ Liêm, Hà Nội'],
            ['b201-2', 'Nguyễn Nhật Nam', '1997-12-19', 'male', '001097020102', '0911000202', 'member2.b201@example.test', 'Bắc Từ Liêm, Hà Nội'],
            ['b202-1', 'Đỗ Hải Yến', '2001-07-27', 'female', '001201020201', '0911000203', 'member.b202@example.test', 'Hà Đông, Hà Nội'],
            ['b204-1', 'Trần Quốc Bảo', '1995-09-03', 'male', '001095020401', '0911000204', 'member.b204@example.test', 'Long Biên, Hà Nội'],
            ['c301-1', 'Hoàng Minh Châu', '1998-03-21', 'female', '001198030101', '0911000301', 'member1.c301@example.test', 'Thanh Xuân, Hà Nội'],
            ['c301-2', 'Hoàng Gia Bảo', '2002-11-06', 'male', '001202030102', '0911000302', 'member2.c301@example.test', 'Thanh Xuân, Hà Nội'],
        ];

        $members = [];
        foreach ($definitions as [$key, $name, $birthDate, $gender, $cccd, $phone, $email, $address]) {
            $members[$key] = Tenant::updateOrCreate(
                ['cccd' => $cccd],
                [
                    'user_id' => null,
                    'full_name' => $name,
                    'date_of_birth' => $birthDate,
                    'gender' => $gender,
                    'cccd_issue_date' => '2022-06-01',
                    'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                ]
            );
        }

        return $members;
    }

    private function seedContracts(array $rooms, array $tenants, array $members): array
    {
        $start = now()->startOfMonth();
        $definitions = [
            ['HD-AP-2025-001', 'A101', 'giahuy@example.test', -10, 14, Contract::STATUS_ACTIVE, 'paid', ['a101-1']],
            ['HD-AP-2025-002', 'A102', 'ngocmai@example.test', -8, 16, Contract::STATUS_ACTIVE, 'paid', ['a102-1']],
            ['HD-AP-2026-003', 'A104', 'ducanh@example.test', -6, 12, Contract::STATUS_ACTIVE, 'paid', ['a104-1']],
            ['HD-AP-2026-004', 'B201', 'khanhlinh@example.test', -5, 13, Contract::STATUS_ACTIVE, 'paid', ['b201-1', 'b201-2']],
            ['HD-AP-2026-005', 'B202', 'quangnam@example.test', -4, 12, Contract::STATUS_ACTIVE, 'paid', ['b202-1']],
            ['HD-AP-2026-006', 'B204', 'thanhthao@example.test', -3, 12, Contract::STATUS_ACTIVE, 'paid', ['b204-1']],
            ['HD-AP-2025-007', 'C301', 'tuankiet@example.test', -15, -2, Contract::STATUS_EXPIRED, 'returned', ['c301-1', 'c301-2']],
            ['HD-AP-2025-008', 'C302', 'phuonguyen@example.test', -12, -4, Contract::STATUS_TERMINATED, 'returned', []],
        ];

        $contracts = [];
        foreach ($definitions as [$code, $roomCode, $email, $startOffset, $endOffset, $status, $depositStatus, $memberKeys]) {
            $startDate = $start->copy()->addMonths($startOffset);
            $endDate = $start->copy()->addMonths($endOffset)->endOfMonth();
            $room = $rooms[$roomCode];
            $tenant = $tenants[$email];
            $people = count($memberKeys) + 1;
            $ended = in_array($status, [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED], true);
            $contract = Contract::updateOrCreate(
                ['contract_code' => $code],
                [
                    'room_id' => $room->id,
                    'tenant_id' => $tenant->id,
                    'representative_tenant_id' => $tenant->id,
                    'representative_is_occupant' => true,
                    'monthly_rent' => $room->price,
                    'deposit_amount' => $room->price * 2,
                    'deposit_status' => $depositStatus,
                    'deposit_paid_at' => $startDate->copy()->subDays(7),
                    'number_of_people' => $people,
                    'signed_at' => $startDate->copy()->subDays(10),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'actual_end_date' => $ended ? $endDate : null,
                    'terminated_at' => $status === Contract::STATUS_TERMINATED ? $endDate : null,
                    'terminated_by' => $status === Contract::STATUS_TERMINATED ? 'tenant' : null,
                    'termination_reason' => $status === Contract::STATUS_TERMINATED ? 'Chuyển nơi làm việc sang tỉnh khác' : null,
                    'termination_note' => $status === Contract::STATUS_TERMINATED ? 'Hai bên đã đối soát công nợ và bàn giao phòng đầy đủ.' : null,
                    'note' => $status === Contract::STATUS_ACTIVE
                        ? 'Khách thuê thanh toán tiền phòng qua chuyển khoản vào đầu tháng.'
                        : 'Hợp đồng lưu để kiểm thử lịch sử thuê phòng.',
                ]
            );
            $contract->forceFill([
                'status' => $status,
                'signed_at' => $startDate->copy()->subDays(10),
                'signed_confirmed_by' => User::where('email', 'admin@nhatroanphuc.test')->value('id'),
                'scheduled_move_in_date' => $startDate,
                'reservation_expires_at' => $startDate->copy()->addDays(2),
                'actual_move_in_at' => in_array($status, [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED, Contract::STATUS_SETTLING], true) ? $startDate : null,
                'actual_move_out_at' => $ended ? $endDate : null,
                'deposit_status' => Contract::DEPOSIT_PAID,
                'landlord_name_snapshot' => Setting::current()?->landlord_name,
                'landlord_address_snapshot' => Setting::current()?->landlord_address,
                'landlord_phone_snapshot' => Setting::current()?->landlord_phone,
                'landlord_identity_snapshot' => Setting::current()?->landlord_identity_number,
                'property_address_snapshot' => Setting::current()?->property_address,
            ])->save();
            $occupantStatus = $ended
                ? ContractOccupant::STATUS_MOVED_OUT
                : (in_array($status, Contract::OPEN_OCCUPANCY_STATUSES, true)
                    ? ContractOccupant::STATUS_CHECKED_IN
                    : ContractOccupant::STATUS_APPROVED);
            foreach ([$tenant, ...array_map(fn ($key) => $members[$key], $memberKeys)] as $index => $person) {
                $occupant = ContractOccupant::updateOrCreate(
                    ['contract_id' => $contract->id, 'tenant_id' => $person->id],
                    [
                        'role' => $index === 0 ? ContractOccupant::ROLE_REPRESENTATIVE : ContractOccupant::ROLE_OCCUPANT,
                        'full_name' => $person->full_name,
                        'date_of_birth' => $person->date_of_birth,
                        'identity_number' => $person->cccd,
                        'phone' => $person->phone,
                        'relationship' => $index === 0 ? 'Người đại diện hợp đồng' : 'Người ở',
                        'address' => $person->address,
                        'status' => $occupantStatus,
                        'reviewed_by' => User::where('email', 'admin@nhatroanphuc.test')->value('id'),
                        'reviewed_at' => now(),
                        'actual_move_in_at' => in_array($occupantStatus, [ContractOccupant::STATUS_CHECKED_IN, ContractOccupant::STATUS_MOVED_OUT], true) ? $startDate : null,
                        'actual_move_out_at' => $occupantStatus === ContractOccupant::STATUS_MOVED_OUT ? $endDate : null,
                    ]
                );
                ContractOccupantHistory::firstOrCreate(
                    ['contract_occupant_id' => $occupant->id, 'action' => 'demo_seed'],
                    ['from_status' => null, 'to_status' => $occupantStatus, 'reason' => 'Dữ liệu minh họa cư trú.', 'performed_at' => now(), 'metadata' => ['seeded' => true]]
                );
            }
            ContractStatusHistory::updateOrCreate(
                ['contract_id' => $contract->id, 'action' => 'demo_seed'],
                [
                    'from_status' => null,
                    'to_status' => $status,
                    'reason' => 'Dữ liệu minh họa vòng đời hợp đồng.',
                    'performed_by' => User::where('email', 'admin@nhatroanphuc.test')->value('id'),
                    'performed_at' => now(),
                    'metadata' => ['seeded' => true],
                ]
            );
            $contracts[] = $contract;
        }

        return $contracts;
    }

    private function seedBillingHistory(array $contracts): void
    {
        $setting = Setting::query()->where('is_active', true)->sole();
        $admin = User::where('email', 'admin@nhatroanphuc.test')->sole();

        foreach ($contracts as $contractIndex => $contract) {
            $contract->load('room');
            $periodEnd = $contract->status === Contract::STATUS_ACTIVE
                ? now()->startOfMonth()
                : Carbon::parse($contract->actual_end_date)->startOfMonth();
            $periodCount = $contract->status === Contract::STATUS_ACTIVE ? 4 : 3;
            $electricity = 720 + ($contractIndex * 185);
            $water = 84 + ($contractIndex * 17);

            UtilityReading::updateOrCreate(
                ['lifecycle_event_key' => "contract:{$contract->id}:handover"],
                [
                    'room_id' => $contract->room_id,
                    'contract_id' => $contract->id,
                    'month' => $contract->start_date->month,
                    'year' => $contract->start_date->year,
                    'record_date' => $contract->start_date,
                    'reading_type' => 'handover',
                    'electricity_old' => $electricity,
                    'electricity_new' => $electricity,
                    'water_old' => $water,
                    'water_new' => $water,
                    'status' => 'confirmed',
                    'note' => 'Chỉ số bàn giao dữ liệu minh họa.',
                ]
            );

            for ($offset = $periodCount - 1; $offset >= 0; $offset--) {
                $period = $periodEnd->copy()->subMonths($offset);
                $electricUsage = 72 + (($contractIndex * 13 + $period->month * 7) % 95);
                $waterUsage = 5 + (($contractIndex + $period->month) % 9);
                $reading = UtilityReading::updateOrCreate(
                    ['room_id' => $contract->room_id, 'month' => $period->month, 'year' => $period->year],
                    [
                        'record_date' => $period->copy()->endOfMonth(),
                        'electricity_old' => $electricity,
                        'electricity_new' => $electricity + $electricUsage,
                        'water_old' => $water,
                        'water_new' => $water + $waterUsage,
                        'status' => 'confirmed',
                        'note' => 'Chỉ số được ghi và đối chiếu cùng khách thuê cuối tháng.',
                    ]
                );
                $electricity += $electricUsage;
                $water += $waterUsage;

                $electricityFee = $electricUsage * (float) $setting->electric_price;
                $waterFee = $waterUsage * (float) $setting->water_price;
                $total = (float) $contract->monthly_rent + $electricityFee + $waterFee
                    + (float) $setting->internet_fee + (float) $setting->service_fee;
                $age = $period->diffInMonths(now()->startOfMonth());
                $status = $age >= 2 ? Invoice::STATUS_PAID : ($age === 1 ? Invoice::STATUS_PARTIAL : Invoice::STATUS_UNPAID);
                $invoice = Invoice::updateOrCreate(
                    ['room_id' => $contract->room_id, 'month' => $period->month, 'year' => $period->year],
                    [
                        'invoice_code' => sprintf('HDON-%s-%s', $contract->room->room_code, $period->format('Ym')),
                        'contract_id' => $contract->id,
                        'utility_reading_id' => $reading->id,
                        'invoice_date' => $period->copy()->endOfMonth(),
                        'due_date' => $period->copy()->endOfMonth()->addDays(10),
                        'room_fee' => $contract->monthly_rent,
                        'electricity_fee' => $electricityFee,
                        'water_fee' => $waterFee,
                        'internet_fee' => $setting->internet_fee,
                        'service_fee' => $setting->service_fee,
                        'total_amount' => $total,
                        'status' => $status,
                    ]
                );

                $this->seedInvoiceDetails($invoice, $contract, $reading, $electricUsage, $waterUsage, $setting);
                $this->seedPayment($invoice, $status, $admin, $contractIndex);
            }
        }
    }

    private function seedInvoiceDetails(Invoice $invoice, Contract $contract, UtilityReading $reading, int $electricUsage, int $waterUsage, Setting $setting): void
    {
        $details = [
            ['room', 'Tiền thuê phòng', 1, 'tháng', $contract->monthly_rent, $contract->monthly_rent, null, null],
            ['electricity', 'Tiền điện sinh hoạt', $electricUsage, 'kWh', $setting->electric_price, $invoice->electricity_fee, $reading->electricity_old, $reading->electricity_new],
            ['water', 'Tiền nước sinh hoạt', $waterUsage, 'm³', $setting->water_price, $invoice->water_fee, $reading->water_old, $reading->water_new],
            ['internet', 'Internet cáp quang', 1, 'tháng', $setting->internet_fee, $setting->internet_fee, null, null],
            ['service', 'Phí vệ sinh và dịch vụ chung', 1, 'tháng', $setting->service_fee, $setting->service_fee, null, null],
        ];

        foreach ($details as $sortOrder => [$type, $name, $quantity, $unit, $unitPrice, $amount, $oldIndex, $newIndex]) {
            InvoiceDetail::updateOrCreate(
                ['invoice_id' => $invoice->id, 'sort_order' => $sortOrder],
                compact('type', 'name', 'quantity', 'unit') + [
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'old_index' => $oldIndex,
                    'new_index' => $newIndex,
                    'note' => $type === 'electricity' || $type === 'water' ? 'Tính theo chỉ số đồng hồ thực tế.' : null,
                ]
            );
        }
    }

    private function seedPayment(Invoice $invoice, string $status, User $admin, int $contractIndex): void
    {
        if ($status === Invoice::STATUS_UNPAID) {
            if ($contractIndex % 2 === 0) {
                Payment::updateOrCreate(
                    ['transaction_code' => 'CK-CHO-'.$invoice->invoice_code],
                    [
                        'invoice_id' => $invoice->id,
                        'amount_paid' => round((float) $invoice->total_amount * 0.35),
                        'payment_date' => now()->toDateString(),
                        'payment_method' => Payment::METHOD_BANK_TRANSFER,
                        'status' => Payment::STATUS_PENDING,
                        'submitted_by' => $invoice->contract->tenant->user_id,
                        'note' => 'Khách đã gửi ảnh chuyển khoản, chờ quản trị viên xác nhận.',
                    ]
                );
            }

            return;
        }

        $amount = $status === Invoice::STATUS_PAID
            ? (float) $invoice->total_amount
            : round((float) $invoice->total_amount * 0.55);
        Payment::updateOrCreate(
            ['transaction_code' => 'CK-'.$invoice->invoice_code],
            [
                'invoice_id' => $invoice->id,
                'amount_paid' => $amount,
                'payment_date' => $invoice->due_date->copy()->subDays(3),
                'payment_method' => $contractIndex % 3 === 0 ? Payment::METHOD_CASH : Payment::METHOD_BANK_TRANSFER,
                'status' => Payment::STATUS_SUCCESS,
                'confirmed_by' => $admin->id,
                'reviewed_at' => $invoice->due_date->copy()->subDays(3),
                'note' => $status === Invoice::STATUS_PAID ? 'Đã thanh toán đủ trong hạn.' : 'Khách thanh toán trước một phần hóa đơn.',
            ]
        );
    }

    private function seedSupportRequests(array $contracts): void
    {
        $admin = User::where('email', 'quanly@nhatroanphuc.test')->sole();
        $samples = [
            ['repair', 'Vòi nước bồn rửa bị rò nhẹ', 'Nước nhỏ giọt liên tục từ tối qua, nhờ ban quản lý sắp xếp thợ kiểm tra giúp.', SupportRequest::STATUS_NEW, null],
            ['utility', 'Nhờ kiểm tra lại chỉ số điện tháng này', 'Chỉ số điện tăng cao hơn tháng trước, tôi muốn đối chiếu lại ảnh công tơ.', SupportRequest::STATUS_IN_PROGRESS, null],
            ['invoice', 'Cần giải thích khoản phí dịch vụ', 'Cho tôi hỏi phí vệ sinh và dịch vụ chung tháng này bao gồm những hạng mục nào?', SupportRequest::STATUS_RESOLVED, 'Phí gồm vệ sinh hành lang, thu gom rác và bảo trì khu vực chung.'],
            ['repair', 'Đèn hành lang tầng hai bị chập chờn', 'Đèn gần cầu thang nhấp nháy vào buổi tối, có thể gây khó quan sát.', SupportRequest::STATUS_RESOLVED, 'Kỹ thuật đã thay bóng và kiểm tra lại đường điện lúc 15 giờ hôm nay.'],
            ['contract', 'Xin xác nhận thủ tục gia hạn hợp đồng', 'Tôi muốn tiếp tục thuê thêm một năm, nhờ quản lý cho biết giấy tờ cần chuẩn bị.', SupportRequest::STATUS_IN_PROGRESS, null],
            ['other', 'Đề nghị giữ xe ngoài khu vực quy định', 'Tôi muốn để thêm một xe của người thân trong sân dài hạn.', SupportRequest::STATUS_REJECTED, 'Sân xe đã đủ công suất và không thể nhận thêm xe ngoài hợp đồng.'],
        ];

        foreach ($samples as $index => [$category, $subject, $description, $status, $response]) {
            $contract = $contracts[$index % 6];
            SupportRequest::updateOrCreate(
                ['submission_token' => sprintf('10000000-0000-4000-8000-%012d', $index + 1)],
                [
                    'user_id' => $contract->tenant->user_id,
                    'tenant_id' => $contract->tenant_id,
                    'contract_id' => $contract->id,
                    'category' => $category,
                    'subject' => $subject,
                    'description' => $description,
                    'status' => $status,
                    'admin_response' => $response,
                    'handled_by' => $status === SupportRequest::STATUS_NEW ? null : $admin->id,
                    'responded_at' => $response ? now()->subDays($index + 1) : null,
                ]
            );
        }
    }
}

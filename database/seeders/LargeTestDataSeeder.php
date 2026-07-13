<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LargeTestDataSeeder extends Seeder
{
    private const ADMIN_COUNT = 8;

    private const TENANT_COUNT = 70;

    private const ROOM_COUNT = 90;

    private const BILLING_MONTHS = 12;

    private Generator $faker;

    private Carbon $now;

    public function run(): void
    {
        $this->ensureDatabaseIsEmpty();

        $this->faker = FakerFactory::create('vi_VN');
        $this->faker->seed(20260713);
        $this->now = Carbon::now()->startOfDay();

        DB::disableQueryLog();

        DB::transaction(function () {
            [$adminIds, $tenantUserIds] = $this->seedUsers();
            $tenantIds = $this->seedTenants($tenantUserIds);
            $amenityIds = $this->seedAmenitiesAndSettings();
            $roomIds = $this->seedRooms($amenityIds);
            $billingContracts = $this->seedContracts($roomIds, $tenantIds);
            $readingIds = $this->seedUtilityReadings($roomIds, $billingContracts);
            $invoiceIds = $this->seedInvoicesAndDetails(
                $roomIds,
                $billingContracts,
                $readingIds
            );
            $this->seedPayments($invoiceIds, $adminIds);
        });

        $this->printSummary();
    }

    private function ensureDatabaseIsEmpty(): void
    {
        $tables = ['roles', 'users', 'rooms', 'tenants', 'contracts', 'invoices'];

        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException(
                    'LargeTestDataSeeder chỉ được chạy trên database trống. '
                    .'Hãy dùng: php artisan migrate:fresh --seed '
                    .'--seeder=Database\\Seeders\\LargeTestDataSeeder --force'
                );
            }
        }
    }

    /**
     * @return array{0: array<int>, 1: array<int>}
     */
    private function seedUsers(): array
    {
        $timestamp = $this->now->copy();

        DB::table('roles')->insert([
            ['role_name' => 'Admin', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['role_name' => 'User', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);

        $roles = DB::table('roles')->pluck('id', 'role_name');
        $password = Hash::make('password');
        $users = [];

        for ($index = 1; $index <= self::ADMIN_COUNT; $index++) {
            $users[] = [
                'name' => sprintf('Quản trị viên %02d', $index),
                'email' => sprintf('admin%02d@test.local', $index),
                'phone' => sprintf('090100%04d', $index),
                'email_verified_at' => $timestamp,
                'password' => $password,
                'role_id' => $roles['Admin'],
                'remember_token' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        for ($index = 1; $index <= self::TENANT_COUNT; $index++) {
            $users[] = [
                'name' => sprintf('Khách thuê %03d', $index),
                'email' => sprintf('tenant%03d@test.local', $index),
                'phone' => sprintf('091%07d', $index),
                'email_verified_at' => $index % 11 === 0 ? null : $timestamp,
                'password' => $password,
                'role_id' => $roles['User'],
                'remember_token' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('users')->insert($users);

        return [
            DB::table('users')->where('role_id', $roles['Admin'])->pluck('id')->all(),
            DB::table('users')->where('role_id', $roles['User'])->orderBy('id')->pluck('id')->all(),
        ];
    }

    /**
     * @param  array<int>  $userIds
     * @return array<int>
     */
    private function seedTenants(array $userIds): array
    {
        $specialNames = [
            'Nguyễn Thị Ánh Dương',
            'Trần Văn Minh-Khôi',
            "Lê O'Connor",
            'Phạm Thị Bích Ngọc',
            'Đỗ Hoàng Long',
        ];
        $tenants = [];

        foreach ($userIds as $offset => $userId) {
            $index = $offset + 1;
            $birthDate = $this->faker->dateTimeBetween('-55 years', '-18 years');
            $issueDate = $this->faker->dateTimeBetween('-10 years', '-1 year');

            $tenants[] = [
                'user_id' => $userId,
                'full_name' => $specialNames[$offset] ?? $this->faker->name(),
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'gender' => $index % 2 === 0 ? 'female' : 'male',
                'cccd' => sprintf('079%09d', $index),
                'cccd_issue_date' => $issueDate->format('Y-m-d'),
                'cccd_issue_place' => $index % 3 === 0
                    ? 'Cục Cảnh sát quản lý hành chính về trật tự xã hội'
                    : 'Công an thành phố Hà Nội',
                'phone' => sprintf('092%07d', $index),
                'email' => sprintf('tenant.profile.%03d@example.com', $index),
                'address' => $index === self::TENANT_COUNT
                    ? str_repeat('Địa chỉ kiểm thử dài, ', 12).'Việt Nam'
                    : $this->faker->address(),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        DB::table('tenants')->insert($tenants);

        return DB::table('tenants')->orderBy('id')->pluck('id')->all();
    }

    /**
     * @return array<int>
     */
    private function seedAmenitiesAndSettings(): array
    {
        DB::table('settings')->insert([
            'electric_price' => 3500,
            'water_price' => 18000,
            'internet_fee' => 100000,
            'service_fee' => 50000,
            'parking_fee' => 100000,
            'invoice_day' => 5,
            'payment_due_days' => 10,
            'is_active' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $names = [
            'Máy lạnh',
            'Máy giặt',
            'Wifi',
            'Tủ lạnh',
            'Nóng lạnh',
            'Ban công',
            'Bãi đỗ xe',
            'Thang máy',
        ];

        DB::table('amenities')->insert(array_map(fn (string $name) => [
            'name' => $name,
            'description' => "Tiện ích {$name} dùng cho dữ liệu kiểm thử",
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ], $names));

        return DB::table('amenities')->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param  array<int>  $amenityIds
     * @return array<int>
     */
    private function seedRooms(array $amenityIds): array
    {
        $rooms = [];

        for ($index = 1; $index <= self::ROOM_COUNT; $index++) {
            $floor = (int) ceil($index / 10);
            $position = (($index - 1) % 10) + 1;
            $status = match (true) {
                $index <= 60 => 'occupied',
                $index <= 80 => 'available',
                default => 'maintenance',
            };
            $maxPeople = $index % 17 === 0 ? 1 : (($index % 4) + 2);

            $rooms[] = [
                'room_code' => sprintf('P%02d%02d', $floor, $position),
                'floor' => $floor,
                'price' => $index === self::ROOM_COUNT
                    ? 12000000
                    : 1800000 + (($index % 12) * 250000),
                'area' => $index === 1 ? 12.5 : 18 + (($index % 9) * 3.5),
                'max_people' => $maxPeople,
                'current_people' => $status === 'occupied'
                    ? min($maxPeople, ($index % $maxPeople) + 1)
                    : 0,
                'thumbnail' => null,
                'description' => $index % 23 === 0
                    ? str_repeat('Phòng kiểm thử có mô tả dài. ', 10)
                    : "Phòng kiểm thử tầng {$floor}, vị trí {$position}",
                'status' => $status,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        DB::table('rooms')->insert($rooms);
        $roomIds = DB::table('rooms')->orderBy('id')->pluck('id')->all();
        $pivots = [];

        foreach ($roomIds as $offset => $roomId) {
            $amenityCount = 2 + ($offset % 4);

            for ($step = 0; $step < $amenityCount; $step++) {
                $amenityId = $amenityIds[($offset + $step * 2) % count($amenityIds)];
                $pivots["{$roomId}-{$amenityId}"] = [
                    'room_id' => $roomId,
                    'amenity_id' => $amenityId,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }
        }

        DB::table('amenity_room')->insert(array_values($pivots));

        return $roomIds;
    }

    /**
     * @param  array<int>  $roomIds
     * @param  array<int>  $tenantIds
     * @return array<int, array{id: int, start: Carbon}>
     */
    private function seedContracts(array $roomIds, array $tenantIds): array
    {
        $contracts = [];
        $primaryCodesByRoom = [];
        $sequence = 1;

        foreach ($roomIds as $offset => $roomId) {
            $roomNumber = $offset + 1;
            $status = match (true) {
                $roomNumber <= 60 => 'active',
                $roomNumber <= 75 => 'expired',
                default => 'terminated',
            };
            $billingStart = match ($status) {
                'active' => $this->now->copy()->startOfMonth()->subMonths(11),
                'expired' => $this->now->copy()->startOfMonth()->subMonths(13),
                default => $this->now->copy()->startOfMonth()->subMonths(23),
            };
            $code = sprintf('HD%04d', $sequence++);
            $tenantId = $tenantIds[$offset % count($tenantIds)];
            $endDate = match ($status) {
                'active' => $this->now->copy()->addYear()->endOfMonth(),
                default => $billingStart->copy()->addMonths(11)->endOfMonth(),
            };

            $contracts[] = $this->contractRow(
                $code,
                $roomId,
                $tenantId,
                $tenantIds[($offset + 7) % count($tenantIds)],
                $billingStart,
                $endDate,
                $status,
                $roomNumber
            );
            $primaryCodesByRoom[$roomId] = ['code' => $code, 'start' => $billingStart];
        }

        for ($index = 0; $index < 20; $index++) {
            $roomId = $roomIds[60 + $index];
            $start = $this->now->copy()->startOfMonth()->addMonths(1 + ($index % 3));
            $contracts[] = $this->contractRow(
                sprintf('HD%04d', $sequence++),
                $roomId,
                $tenantIds[(60 + $index) % count($tenantIds)],
                null,
                $start,
                $start->copy()->addYear()->subDay(),
                'pending',
                100 + $index
            );
        }

        for ($index = 0; $index < 20; $index++) {
            $status = $index < 10 ? 'expired' : 'terminated';
            $roomId = $roomIds[$index * 3];
            $start = $this->now->copy()->startOfMonth()->subMonths(36 + $index);
            $contracts[] = $this->contractRow(
                sprintf('HD%04d', $sequence++),
                $roomId,
                $tenantIds[($index + 25) % count($tenantIds)],
                null,
                $start,
                $start->copy()->addMonths(10)->endOfMonth(),
                $status,
                130 + $index
            );
        }

        DB::table('contracts')->insert($contracts);
        $contractIds = DB::table('contracts')->pluck('id', 'contract_code');
        $primary = [];

        foreach ($primaryCodesByRoom as $roomId => $data) {
            $primary[$roomId] = [
                'id' => $contractIds[$data['code']],
                'start' => $data['start'],
            ];
        }

        return $primary;
    }

    /**
     * @return array<string, mixed>
     */
    private function contractRow(
        string $code,
        int $roomId,
        int $tenantId,
        ?int $representativeTenantId,
        Carbon $start,
        Carbon $end,
        string $status,
        int $variant
    ): array {
        $terminated = $status === 'terminated';

        return [
            'contract_code' => $code,
            'room_id' => $roomId,
            'tenant_id' => $tenantId,
            'representative_tenant_id' => $variant % 9 === 0 ? $representativeTenantId : null,
            'monthly_rent' => $roomId === self::ROOM_COUNT
                ? 12000000
                : 1800000 + (($roomId % 12) * 250000),
            'deposit_amount' => 3600000 + (($roomId % 8) * 500000),
            'deposit_status' => match ($status) {
                'pending' => 'pending',
                'active' => 'paid',
                default => 'returned',
            },
            'deposit_paid_at' => $status === 'pending' ? null : $start->copy()->subDays(3),
            'number_of_people' => ($variant % 4) + 1,
            'signed_at' => $status === 'pending' ? null : $start->copy()->subDays(5)->toDateString(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'actual_end_date' => $terminated ? $end->toDateString() : null,
            'extended_at' => $variant % 13 === 0 ? $end->copy()->subMonths(2) : null,
            'extend_start_date' => $variant % 13 === 0 ? $end->copy()->subMonth()->toDateString() : null,
            'extend_end_date' => $variant % 13 === 0 ? $end->toDateString() : null,
            'extend_reason' => $variant % 13 === 0 ? 'Gia hạn để kiểm thử dữ liệu' : null,
            'extend_note' => $variant % 13 === 0 ? 'Ghi chú gia hạn có dấu tiếng Việt.' : null,
            'terminated_at' => $terminated ? $end->toDateString() : null,
            'terminated_by' => $terminated ? ($variant % 2 === 0 ? 'admin' : 'tenant') : null,
            'termination_reason' => $terminated ? 'Kết thúc hợp đồng theo thỏa thuận' : null,
            'termination_note' => $terminated ? 'Dữ liệu kiểm thử hợp đồng đã kết thúc.' : null,
            'contract_file' => null,
            'status' => $status,
            'note' => $variant % 19 === 0 ? str_repeat('Ghi chú hợp đồng dài. ', 8) : null,
            'created_at' => $start->copy()->subDays(7),
            'updated_at' => $this->now,
        ];
    }

    /**
     * @param  array<int>  $roomIds
     * @param  array<int, array{id: int, start: Carbon}>  $billingContracts
     * @return array<string, int>
     */
    private function seedUtilityReadings(array $roomIds, array $billingContracts): array
    {
        $rows = [];

        foreach ($roomIds as $offset => $roomId) {
            $electricity = 1000 + (($offset + 1) * 37);
            $water = 100 + (($offset + 1) * 5);

            for ($period = 0; $period < self::BILLING_MONTHS; $period++) {
                $date = $billingContracts[$roomId]['start']->copy()->addMonths($period);
                $electricityUsage = ($offset + $period) % 37 === 0
                    ? 0
                    : 45 + (($offset * 17 + $period * 13) % 310);
                $waterUsage = ($offset + $period) % 41 === 0
                    ? 0
                    : 4 + (($offset * 3 + $period * 2) % 24);

                if ($offset === self::ROOM_COUNT - 1 && $period === self::BILLING_MONTHS - 1) {
                    $electricityUsage = 1200;
                    $waterUsage = 90;
                }

                $rows[] = [
                    'room_id' => $roomId,
                    'month' => $date->month,
                    'year' => $date->year,
                    'record_date' => $date->copy()->endOfMonth()->toDateString(),
                    'electricity_old' => $electricity,
                    'electricity_new' => $electricity + $electricityUsage,
                    'electricity_image' => null,
                    'water_old' => $water,
                    'water_new' => $water + $waterUsage,
                    'water_image' => null,
                    'status' => 'confirmed',
                    'note' => $electricityUsage === 0 && $waterUsage === 0
                        ? 'Không phát sinh tiêu thụ trong kỳ'
                        : null,
                    'created_at' => $date->copy()->endOfMonth(),
                    'updated_at' => $date->copy()->endOfMonth(),
                ];

                $electricity += $electricityUsage;
                $water += $waterUsage;
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('utility_readings')->insert($chunk);
        }

        return DB::table('utility_readings')
            ->get(['id', 'room_id', 'month', 'year'])
            ->mapWithKeys(fn ($reading) => [
                $this->periodKey($reading->room_id, $reading->month, $reading->year) => $reading->id,
            ])
            ->all();
    }

    /**
     * @param  array<int>  $roomIds
     * @param  array<int, array{id: int, start: Carbon}>  $billingContracts
     * @param  array<string, int>  $readingIds
     * @return array<string, array{id: int, total: float, status: string, due: Carbon}>
     */
    private function seedInvoicesAndDetails(
        array $roomIds,
        array $billingContracts,
        array $readingIds
    ): array {
        $readings = DB::table('utility_readings')
            ->get()
            ->keyBy(fn ($reading) => $this->periodKey(
                $reading->room_id,
                $reading->month,
                $reading->year
            ));
        $roomPrices = DB::table('rooms')->pluck('price', 'id');
        $invoices = [];

        foreach ($roomIds as $offset => $roomId) {
            for ($period = 0; $period < self::BILLING_MONTHS; $period++) {
                $date = $billingContracts[$roomId]['start']->copy()->addMonths($period);
                $key = $this->periodKey($roomId, $date->month, $date->year);
                $reading = $readings[$key];
                $electricityFee = ($reading->electricity_new - $reading->electricity_old) * 3500;
                $waterFee = ($reading->water_new - $reading->water_old) * 18000;
                $roomFee = (float) $roomPrices[$roomId];
                $internetFee = ($offset + $period) % 29 === 0 ? 0 : 100000;
                $serviceFee = ($offset + $period) % 31 === 0 ? 0 : 50000;
                $total = $roomFee + $electricityFee + $waterFee + $internetFee + $serviceFee;
                $bucket = (($offset * self::BILLING_MONTHS) + $period) % 20;
                $status = match (true) {
                    $bucket < 9 => 'paid',
                    $bucket < 14 => 'partial',
                    default => 'unpaid',
                };

                $invoices[] = [
                    'invoice_code' => sprintf('INV-%04d%02d-R%03d', $date->year, $date->month, $offset + 1),
                    'contract_id' => $billingContracts[$roomId]['id'],
                    'room_id' => $roomId,
                    'utility_reading_id' => $readingIds[$key],
                    'month' => $date->month,
                    'year' => $date->year,
                    'invoice_date' => $date->copy()->endOfMonth()->toDateString(),
                    'due_date' => $date->copy()->endOfMonth()->addDays(10)->toDateString(),
                    'room_fee' => $roomFee,
                    'electricity_fee' => $electricityFee,
                    'water_fee' => $waterFee,
                    'internet_fee' => $internetFee,
                    'service_fee' => $serviceFee,
                    'total_amount' => $total,
                    'status' => $status,
                    'created_at' => $date->copy()->endOfMonth(),
                    'updated_at' => $date->copy()->endOfMonth(),
                ];
            }
        }

        foreach (array_chunk($invoices, 500) as $chunk) {
            DB::table('invoices')->insert($chunk);
        }

        $storedInvoices = DB::table('invoices')->get()->keyBy('invoice_code');
        $details = [];
        $result = [];

        foreach ($invoices as $invoice) {
            $stored = $storedInvoices[$invoice['invoice_code']];
            $reading = $readings[$this->periodKey(
                $invoice['room_id'],
                $invoice['month'],
                $invoice['year']
            )];
            $lines = [
                ['room', 'Tiền thuê phòng', 1, 'tháng', $invoice['room_fee'], null, null, 1],
                ['electricity', 'Tiền điện', $reading->electricity_new - $reading->electricity_old, 'kWh', 3500, $reading->electricity_old, $reading->electricity_new, 2],
                ['water', 'Tiền nước', $reading->water_new - $reading->water_old, 'm3', 18000, $reading->water_old, $reading->water_new, 3],
                ['internet', 'Phí internet', 1, 'tháng', $invoice['internet_fee'], null, null, 4],
                ['service', 'Phí dịch vụ', 1, 'tháng', $invoice['service_fee'], null, null, 5],
            ];

            foreach ($lines as [$type, $name, $quantity, $unit, $unitPrice, $old, $new, $sort]) {
                $details[] = [
                    'invoice_id' => $stored->id,
                    'type' => $type,
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'amount' => $quantity * $unitPrice,
                    'old_index' => $old,
                    'new_index' => $new,
                    'note' => $quantity == 0 ? 'Dòng dữ liệu biên có số lượng bằng 0' : null,
                    'sort_order' => $sort,
                    'created_at' => $stored->created_at,
                    'updated_at' => $stored->updated_at,
                ];
            }

            $result[$invoice['invoice_code']] = [
                'id' => $stored->id,
                'total' => (float) $invoice['total_amount'],
                'status' => $invoice['status'],
                'due' => Carbon::parse($invoice['due_date']),
            ];
        }

        foreach (array_chunk($details, 500) as $chunk) {
            DB::table('invoice_details')->insert($chunk);
        }

        return $result;
    }

    /**
     * @param  array<string, array{id: int, total: float, status: string, due: Carbon}>  $invoices
     * @param  array<int>  $adminIds
     */
    private function seedPayments(array $invoices, array $adminIds): void
    {
        $payments = [];
        $sequence = 1;

        foreach ($invoices as $invoice) {
            if ($invoice['status'] === 'paid') {
                $splitPayment = $sequence % 4 === 0;
                $amounts = $splitPayment
                    ? [round($invoice['total'] * 0.4), $invoice['total'] - round($invoice['total'] * 0.4)]
                    : [$invoice['total']];

                foreach ($amounts as $part => $amount) {
                    $payments[] = $this->paymentRow(
                        $invoice['id'],
                        $amount,
                        $invoice['due']->copy()->subDays(max(0, 2 - $part)),
                        'success',
                        $adminIds[$sequence % count($adminIds)],
                        $sequence++
                    );
                }
            } elseif ($invoice['status'] === 'partial') {
                $ratio = 0.35 + (($sequence % 4) * 0.1);
                $payments[] = $this->paymentRow(
                    $invoice['id'],
                    round($invoice['total'] * $ratio),
                    $invoice['due']->copy()->subDay(),
                    'success',
                    $adminIds[$sequence % count($adminIds)],
                    $sequence++
                );
            } elseif ($sequence % 5 === 0) {
                $payments[] = $this->paymentRow(
                    $invoice['id'],
                    round($invoice['total'] * 0.25),
                    $invoice['due']->copy(),
                    $sequence % 10 === 0 ? 'failed' : 'pending',
                    $adminIds[$sequence % count($adminIds)],
                    $sequence++
                );
            } else {
                $sequence++;
            }
        }

        foreach (array_chunk($payments, 500) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentRow(
        int $invoiceId,
        float $amount,
        Carbon $date,
        string $status,
        int $adminId,
        int $sequence
    ): array {
        $methods = ['cash', 'bank_transfer', 'qr'];

        return [
            'invoice_id' => $invoiceId,
            'amount_paid' => $amount,
            'payment_date' => $date->toDateString(),
            'payment_method' => $methods[$sequence % count($methods)],
            'transaction_code' => $sequence % 17 === 0
                ? null
                : sprintf('TXN-%06d', $sequence),
            'status' => $status,
            'confirmed_by' => $adminId,
            'note' => $status === 'failed'
                ? 'Giao dịch thất bại dùng để kiểm tra bộ lọc'
                : ($sequence % 23 === 0 ? 'Thanh toán có ghi chú tiếng Việt.' : null),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    private function periodKey(int $roomId, int $month, int $year): string
    {
        return "{$roomId}-{$year}-{$month}";
    }

    private function printSummary(): void
    {
        $summary = [
            ['Tài khoản quản trị', DB::table('users')->where('role_id', 1)->count()],
            ['Tài khoản khách thuê', DB::table('users')->where('role_id', 2)->count()],
            ['Phòng', DB::table('rooms')->count()],
            ['Khách thuê', DB::table('tenants')->count()],
            ['Hợp đồng', DB::table('contracts')->count()],
            ['Chỉ số điện nước', DB::table('utility_readings')->count()],
            ['Hóa đơn', DB::table('invoices')->count()],
            ['Chi tiết hóa đơn', DB::table('invoice_details')->count()],
            ['Thanh toán', DB::table('payments')->count()],
        ];

        $this->command?->newLine();
        $this->command?->table(['Dữ liệu', 'Số lượng'], $summary);
        $this->command?->info('Đăng nhập quản trị: admin01@test.local / password');
        $this->command?->info('Đăng nhập khách thuê: tenant001@test.local / password');
    }
}

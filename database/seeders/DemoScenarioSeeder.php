<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Expense;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SupportRequest;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoScenarioSeeder extends Seeder
{
    /**
     * Dữ liệu trình diễn nghiệp vụ an toàn.
     *
     * Không tạo yêu cầu gia hạn, trả phòng, đổi phòng hoặc hoàn cọc. Các luồng
     * này phải được thao tác độc lập trên giao diện để không làm xung đột trạng
     * thái của cùng một hợp đồng.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->whereHas('role', fn ($query) => $query->where('role_name', 'Admin'))->firstOrFail();
            $setting = Setting::currentOrCreate();
            $feeSchedule = FeeSchedule::forPeriod(now()) ?? FeeSchedule::query()->create([
                'effective_from' => '2000-01-01',
                'electric_price' => $setting->electric_price,
                'water_price' => $setting->water_price,
                'internet_fee' => $setting->internet_fee,
                'service_fee' => $setting->service_fee,
            ]);

            $tenants = Tenant::query()->whereHas('user', fn ($query) => $query->where('email', 'like', '%@gmail.com'))
                ->orderBy('id')->take(9)->get();
            $rooms = Room::query()->orderBy('room_code')->take(9)->get();

            if ($tenants->count() < 9 || $rooms->count() < 9) {
                throw new \RuntimeException('Cần ít nhất 9 khách thuê Gmail và 9 phòng trước khi tạo dữ liệu demo.');
            }

            $scenarios = [
                ['active-paid', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(5)->startOfDay(), now()->addMonthsNoOverflow(7)->startOfDay(), Contract::DEPOSIT_PAID],
                ['active-partial', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(3)->startOfDay(), now()->addMonthsNoOverflow(9)->startOfDay(), Contract::DEPOSIT_PAID],
                ['active-overdue', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(2)->startOfDay(), now()->addMonthsNoOverflow(10)->startOfDay(), Contract::DEPOSIT_PAID],
                ['awaiting-move-in', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(4)->startOfDay(), now()->addMonthsNoOverflow(8)->startOfDay(), Contract::DEPOSIT_PAID],
                ['pending-deposit', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(11)->startOfDay(), now()->addDays(18)->startOfDay(), Contract::DEPOSIT_PAID],
                ['pending-signature', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(2)->startOfDay(), now()->addMonthsNoOverflow(10)->startOfDay(), Contract::DEPOSIT_PAID],
                ['draft', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(11)->startOfDay(), now()->addDays(27)->startOfDay(), Contract::DEPOSIT_PAID],
                ['expiring-soon', Contract::STATUS_ACTIVE, now()->subMonthsNoOverflow(8)->startOfDay(), now()->addMonthsNoOverflow(4)->startOfDay(), Contract::DEPOSIT_PAID],
                ['expired-10-days', Contract::STATUS_EXPIRED, now()->subYear()->startOfDay(), now()->subDays(10)->startOfDay(), Contract::DEPOSIT_PAID],
            ];

            $contracts = [];
            foreach ($scenarios as $index => [$key, $status, $startDate, $endDate, $depositStatus]) {
                $contracts[$key] = $this->contract(
                    $key,
                    $rooms[$index],
                    $tenants[$index],
                    $status,
                    $startDate,
                    $endDate,
                    $depositStatus,
                    $admin,
                    $setting,
                );
            }

            $this->billingScenarios($contracts, $feeSchedule, $admin);
            $paidBillingSeeder = new RecentPaidContractsSeeder();
            foreach (['active-paid', 'active-partial', 'active-overdue', 'awaiting-move-in', 'pending-deposit', 'pending-signature', 'draft', 'expiring-soon', 'expired-10-days'] as $offset => $key) {
                $contract = $contracts[$key]->loadMissing(['room', 'tenant.user']);
                $paidBillingSeeder->seedPaidBilling(
                    $contract,
                    $contract->room,
                    $contract->tenant->user,
                    $admin,
                    $feeSchedule,
                    $contract->start_date->copy()->startOfMonth(),
                    10 + $offset,
                );
            }
            $this->supportScenarios($contracts, $admin);
            $this->residentScenarios($contracts, $admin);
            $this->expenseScenarios($rooms, $admin);
        });
    }

    private function contract(string $key, Room $room, Tenant $tenant, string $status, Carbon $start, Carbon $end, string $depositStatus, User $admin, Setting $setting): Contract
    {
        $demoNote = 'Tình huống demo: '.str_replace('-', ' ', $key);
        $legacyCode = 'HD-DEMO-'.strtoupper($key);
        $content = '<h1>HỢP ĐỒNG THUÊ PHÒNG</h1><p>Hợp đồng thuê phòng của '.$tenant->full_name.'.</p>';
        $contract = Contract::query()
            ->where('contract_code', $legacyCode)
            ->orWhere('note', $demoNote)
            ->first();
        if (! $contract) {
            $signed = in_array($status, [
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
                ...Contract::OPEN_OCCUPANCY_STATUSES,
            ], true);
            $occupied = in_array($status, Contract::OPEN_OCCUPANCY_STATUSES, true);
            $contract = new Contract();
            $contract->forceFill([
                // Mã tạm chỉ tồn tại trong transaction; sau khi có ID sẽ đổi
                // sang cùng chuẩn HD000001 như luồng tạo hợp đồng thực tế.
                'contract_code' => $legacyCode,
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'representative_tenant_id' => $tenant->id,
                'monthly_rent' => $room->price,
                'deposit_amount' => $room->price,
                'deposit_status' => $depositStatus,
                'deposit_paid_at' => $depositStatus === Contract::DEPOSIT_PAID ? now()->subDays(15) : null,
                'number_of_people' => 1,
                'internet_enabled' => true,
                'service_enabled' => true,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'signed_at' => $signed ? now()->subDays(20) : null,
                'signed_confirmed_by' => $signed ? $admin->id : null,
                'signature_due_at' => $status === Contract::STATUS_PENDING_SIGNATURE ? now()->addDays(2) : null,
                'deposit_due_at' => $status === Contract::STATUS_PENDING_DEPOSIT ? now()->addDays(2) : null,
                'scheduled_move_in_date' => $status === Contract::STATUS_AWAITING_MOVE_IN ? now()->addDays(3)->toDateString() : null,
                'actual_move_in_at' => $occupied ? $start : null,
                'checked_in_by' => $occupied ? $admin->id : null,
                'status' => $status,
                'electric_price_snapshot' => $setting->electric_price,
                'water_price_snapshot' => $setting->water_price,
                'internet_fee_snapshot' => $setting->internet_fee,
                'service_fee_snapshot' => $setting->service_fee,
                'contract_content' => $signed ? $content : null,
                'contract_content_snapshotted_at' => $signed ? now()->subDays(20) : null,
                'contract_content_sha256' => $signed ? hash('sha256', $content) : null,
                'note' => $demoNote,
            ]);
            $contract->save();
        }

        $standardCode = 'HD'.str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT);
        $signed = in_array($status, [
            Contract::STATUS_PENDING_DEPOSIT,
            Contract::STATUS_AWAITING_MOVE_IN,
            ...Contract::OPEN_OCCUPANCY_STATUSES,
        ], true);
        $occupied = in_array($status, Contract::OPEN_OCCUPANCY_STATUSES, true);
        $contract->forceFill([
            'contract_code' => $standardCode,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'representative_tenant_id' => $tenant->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => $status,
            'deposit_status' => $depositStatus,
            'deposit_paid_at' => $depositStatus === Contract::DEPOSIT_PAID ? $start : null,
            'signed_at' => $signed ? $start->copy()->subDays(3) : null,
            'signed_confirmed_by' => $signed ? $admin->id : null,
            'signature_due_at' => $status === Contract::STATUS_PENDING_SIGNATURE ? now()->addDays(2) : null,
            'deposit_due_at' => $status === Contract::STATUS_PENDING_DEPOSIT ? now()->addDays(2) : null,
            'scheduled_move_in_date' => $status === Contract::STATUS_AWAITING_MOVE_IN ? now()->addDays(3)->toDateString() : null,
            'actual_move_in_at' => $occupied ? $start->copy()->setTime(9, 0) : null,
            'checked_in_by' => $occupied ? $admin->id : null,
            'monthly_rent' => $room->price,
            'deposit_amount' => $room->price,
            'contract_content' => $signed ? $content : null,
            'contract_content_snapshotted_at' => $signed ? $start->copy()->subDays(3) : null,
            'contract_content_sha256' => $signed ? hash('sha256', $content) : null,
            'note' => $demoNote,
        ]);
        // Chỉ seeder mới được phép đồng bộ lại kịch bản mẫu đã ký.
        // Luồng ứng dụng vẫn giữ nguyên cơ chế đóng băng nội dung hợp đồng.
        $contract->saveQuietly();

        $room->update(['status' => $occupied ? Room::STATUS_OCCUPIED : ($contract->status === Contract::STATUS_DRAFT ? Room::STATUS_AVAILABLE : Room::STATUS_OCCUPIED), 'current_people' => $occupied ? 1 : 0]);

        ContractTenant::query()->updateOrCreate(
            ['contract_id' => $contract->id, 'tenant_id' => $tenant->id],
            [
                'role' => ContractTenant::ROLE_REPRESENTATIVE,
                'full_name' => $tenant->full_name,
                'date_of_birth' => $tenant->date_of_birth,
                'identity_number' => $tenant->cccd,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'status' => $occupied ? ContractTenant::STATUS_CHECKED_IN : ContractTenant::STATUS_APPROVED,
                'actual_move_in_at' => $occupied ? $contract->actual_move_in_at : null,
                'vehicle_declaration_status' => ContractTenant::VEHICLE_NONE,
            ]
        );

        return $contract;
    }

    private function billingScenarios(array $contracts, FeeSchedule $feeSchedule, User $admin): void
    {
        $period = now()->subMonthNoOverflow()->startOfMonth();
        foreach (['active-paid', 'active-partial', 'active-overdue'] as $offset => $key) {
            $contract = $contracts[$key];
            $reading = UtilityReading::query()->updateOrCreate(
                ['contract_id' => $contract->id, 'month' => $period->month, 'year' => $period->year, 'reading_type' => 'periodic'],
                [
                    'room_id' => $contract->room_id,
                    'record_date' => $period->copy()->endOfMonth()->toDateString(),
                    'electricity_old' => 1200 + ($offset * 100),
                    'electricity_new' => 1280 + ($offset * 110),
                    'water_old' => 300 + ($offset * 30),
                    'water_new' => 307 + ($offset * 32),
                    'status' => UtilityReading::STATUS_LOCKED,
                    'note' => 'Chỉ số kỳ demo đã xác nhận.',
                ]
            );

            $electricity = ($reading->electricity_new - $reading->electricity_old) * (float) $feeSchedule->electric_price;
            $water = ($reading->water_new - $reading->water_old) * (float) $feeSchedule->water_price;
            $total = (float) $contract->monthly_rent + $electricity + $water + (float) $feeSchedule->internet_fee + (float) $feeSchedule->service_fee;
            $status = Invoice::STATUS_PAID;
            $dueDate = now()->addDays(5);

            $invoice = Invoice::query()->updateOrCreate(
                ['invoice_code' => 'HDON-DEMO-'.strtoupper($key)],
                [
                    'contract_id' => $contract->id,
                    'room_id' => $contract->room_id,
                    'utility_reading_id' => $reading->id,
                    'fee_schedule_id' => $feeSchedule->id,
                    'invoice_type' => Invoice::TYPE_RENTAL,
                    'revision' => 1,
                    'month' => now()->month,
                    'year' => now()->year,
                    'invoice_date' => now()->startOfMonth()->addDays(4)->toDateString(),
                    'issued_at' => now()->startOfMonth()->addDays(4),
                    'issued_by' => $admin->id,
                    'due_date' => $dueDate->toDateString(),
                    'room_fee' => $contract->monthly_rent,
                    'electricity_fee' => $electricity,
                    'water_fee' => $water,
                    'internet_fee' => $feeSchedule->internet_fee,
                    'service_fee' => $feeSchedule->service_fee,
                    'total_amount' => $total,
                    'adjustment_amount' => 0,
                    'status' => $status,
                ]
            );

            $details = [
                ['room', 'Tiền phòng tháng '.$period->format('m/Y'), 1, 'tháng', (float) $contract->monthly_rent],
                ['electricity', 'Tiền điện', $reading->electricity_new - $reading->electricity_old, 'kWh', (float) $feeSchedule->electric_price],
                ['water', 'Tiền nước', $reading->water_new - $reading->water_old, 'm³', (float) $feeSchedule->water_price],
                ['internet', 'Phí Internet', 1, 'phòng', (float) $feeSchedule->internet_fee],
                ['service', 'Phí dịch vụ', 1, 'người', (float) $feeSchedule->service_fee],
            ];
            foreach ($details as $sort => [$type, $name, $quantity, $unit, $unitPrice]) {
                InvoiceDetail::query()->updateOrCreate(
                    ['invoice_id' => $invoice->id, 'type' => $type],
                    ['name' => $name, 'quantity' => $quantity, 'unit' => $unit, 'unit_price' => $unitPrice, 'amount' => $quantity * $unitPrice, 'sort_order' => $sort + 1]
                );
            }

            if (in_array($key, ['active-paid', 'active-partial', 'active-overdue'], true)) {
                $amount = $total;
                Payment::query()->updateOrCreate(
                    ['transaction_code' => 'GD-DEMO-'.strtoupper($key)],
                    ['invoice_id' => $invoice->id, 'amount_paid' => $amount, 'payment_date' => now()->toDateString(), 'payment_method' => Payment::METHOD_BANK_TRANSFER, 'status' => Payment::STATUS_SUCCESS, 'submitted_by' => $contract->tenant->user_id, 'confirmed_by' => $admin->id, 'reviewed_at' => now(), 'note' => 'Thanh toán minh họa.']
                );
            } else {
                Payment::query()->updateOrCreate(
                    ['transaction_code' => 'GD-DEMO-CHO-DUYET'],
                    ['invoice_id' => $invoice->id, 'amount_paid' => round($total * 0.5), 'payment_date' => now()->toDateString(), 'payment_method' => Payment::METHOD_QR, 'status' => Payment::STATUS_PENDING, 'submitted_by' => $contract->tenant->user_id, 'note' => 'Giao dịch demo đang chờ quản lý xác nhận.']
                );
                Payment::query()->updateOrCreate(
                    ['transaction_code' => 'GD-DEMO-TU-CHOI'],
                    ['invoice_id' => $invoice->id, 'amount_paid' => 100000, 'payment_date' => now()->subDays(3)->toDateString(), 'payment_method' => Payment::METHOD_BANK_TRANSFER, 'status' => Payment::STATUS_FAILED, 'submitted_by' => $contract->tenant->user_id, 'confirmed_by' => $admin->id, 'reviewed_at' => now()->subDays(2), 'review_note' => 'Không khớp nội dung chuyển khoản.', 'note' => 'Giao dịch demo đã bị từ chối.']
                );
            }

            // Khép lại các giao dịch chờ từ kịch bản cũ để dữ liệu demo không còn công nợ treo.
            Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', Payment::STATUS_PENDING)
                ->update([
                    'status' => Payment::STATUS_FAILED,
                    'confirmed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'review_note' => 'Đã đóng kịch bản công nợ cũ.',
                ]);
        }
    }

    private function supportScenarios(array $contracts, User $admin): void
    {
        $items = [
            ['active-paid', 'repair', 'Điều hòa làm lạnh yếu', SupportRequest::STATUS_NEW, null],
            ['active-partial', 'utility', 'Cần kiểm tra lại chỉ số nước', SupportRequest::STATUS_IN_PROGRESS, 'Ban quản lý đang sắp xếp kiểm tra đồng hồ.'],
            ['active-overdue', 'other', 'Đề nghị bổ sung thùng rác tầng', SupportRequest::STATUS_RESOLVED, 'Đã bổ sung thùng rác tại khu vực hành lang.'],
        ];
        foreach ($items as [$key, $category, $subject, $status, $response]) {
            $contract = $contracts[$key];
            SupportRequest::query()->updateOrCreate(
                ['submission_token' => '00000000-0000-4000-8000-'.str_pad((string) (crc32($key) % 1000000000000), 12, '0', STR_PAD_LEFT)],
                ['user_id' => $contract->tenant->user_id, 'tenant_id' => $contract->tenant_id, 'contract_id' => $contract->id, 'category' => $category, 'subject' => $subject, 'description' => 'Nội dung yêu cầu dùng để trình diễn quy trình tiếp nhận và xử lý.', 'status' => $status, 'admin_response' => $response, 'handled_by' => $status === SupportRequest::STATUS_NEW ? null : $admin->id, 'responded_at' => $response ? now()->subDay() : null]
            );
        }
    }

    private function residentScenarios(array $contracts, User $admin): void
    {
        $contract = $contracts['active-paid'];
        $member = ContractTenant::query()->where('contract_id', $contract->id)->firstOrFail();
        Vehicle::query()->updateOrCreate(
            ['license_plate' => '29X1-678.90'],
            ['tenant_id' => $contract->tenant_id, 'vehicle_type' => 'motorcycle', 'vehicle_name' => 'Honda Vision', 'color' => 'Đen', 'status' => Vehicle::STATUS_APPROVED, 'submitted_by' => $contract->tenant->user_id, 'reviewed_by' => $admin->id, 'reviewed_at' => now()->subDays(20), 'review_note' => 'Thông tin hợp lệ.']
        );
        TemporaryResidence::query()->updateOrCreate(
            ['reference_number' => 'TT-DEMO-001'],
            ['tenant_id' => $contract->tenant_id, 'contract_id' => $contract->id, 'room_id' => $contract->room_id, 'contract_tenant_id' => $member->id, 'start_date' => $contract->start_date, 'end_date' => $contract->end_date, 'status' => 'active', 'note' => 'Hồ sơ tạm trú đang hiệu lực.', 'verified_by' => $admin->id, 'verified_at' => now()->subDays(10)]
        );
    }

    private function expenseScenarios($rooms, User $admin): void
    {
        // Hai khoản tiện ích cũ dùng số tiền minh họa cố định, không còn phù hợp với
        // dữ liệu chỉ số theo tháng. GovernmentUtilityExpenseSeeder sẽ tạo lại theo sản lượng.
        Expense::query()->whereIn('expense_code', ['EXP-DEMO-001', 'EXP-DEMO-002'])->delete();

        $items = [
            ['EXP-DEMO-003', Expense::CATEGORY_MAINTENANCE, 'Bảo dưỡng điều hòa phòng P101', 450000, $rooms->first()->id],
            ['EXP-DEMO-004', Expense::CATEGORY_CLEANING, 'Vệ sinh khu vực chung', 900000, null],
        ];
        foreach ($items as $offset => [$code, $category, $title, $amount, $roomId]) {
            Expense::query()->updateOrCreate(
                ['expense_code' => $code],
                ['category' => $category, 'title' => $title, 'amount' => $amount, 'expense_date' => now()->subDays($offset * 5)->toDateString(), 'room_id' => $roomId, 'payer_name' => $admin->name, 'payment_method' => Expense::METHOD_BANK_TRANSFER, 'notes' => 'Chi phí minh họa báo cáo doanh thu - chi phí.', 'created_by' => $admin->id]
            );
        }
    }
}

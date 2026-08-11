<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractOccupant;
use App\Models\ContractOccupantHistory;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuthenticationScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedClientScenario(
                'auth.client@example.test',
                'AUTH-ACTIVE',
                '099000000001',
                '0900000101',
                Contract::STATUS_ACTIVE,
                Room::STATUS_OCCUPIED,
                now()->subMonths(3)->startOfMonth(),
                now()->addMonths(9)->endOfMonth(),
                Invoice::STATUS_UNPAID,
            );

            $this->seedClientScenario(
                'auth.pending@example.test',
                'AUTH-PENDING',
                '099000000002',
                '0900000102',
                Contract::STATUS_ACTIVE,
                Room::STATUS_OCCUPIED,
                now()->startOfMonth(),
                now()->addYear()->endOfMonth(),
                Invoice::STATUS_PAID,
            );

            $this->seedClientScenario(
                'auth.settling@example.test',
                'AUTH-SETTLING',
                '099000000003',
                '0900000103',
                Contract::STATUS_SETTLING,
                Room::STATUS_AVAILABLE,
                now()->subYear()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                Invoice::STATUS_PARTIAL,
            );

            $this->seedPaymentsAndSupportRequests();
        });
    }

    private function seedPaymentsAndSupportRequests(): void
    {
        $admin = User::where('email', 'auth.admin@example.test')->sole();
        $active = User::where('email', 'auth.client@example.test')->sole();
        $pending = User::where('email', 'auth.pending@example.test')->sole();
        $settling = User::where('email', 'auth.settling@example.test')->sole();

        $activeInvoice = Invoice::whereHas('contract.tenant', fn ($query) => $query->where('user_id', $active->id))->sole();
        $pendingInvoice = Invoice::whereHas('contract.tenant', fn ($query) => $query->where('user_id', $pending->id))->sole();
        $settlingInvoice = Invoice::whereHas('contract.tenant', fn ($query) => $query->where('user_id', $settling->id))->sole();

        $payments = [
            [$activeInvoice, 500000, Payment::STATUS_PENDING, Payment::METHOD_BANK_TRANSFER, 'SEED-PENDING', $active],
            [$pendingInvoice, $pendingInvoice->total_amount, Payment::STATUS_SUCCESS, Payment::METHOD_CASH, 'SEED-SUCCESS', $pending],
            [$settlingInvoice, 1000000, Payment::STATUS_SUCCESS, Payment::METHOD_QR, 'SEED-PARTIAL', $settling],
            [$settlingInvoice, 200000, Payment::STATUS_FAILED, Payment::METHOD_BANK_TRANSFER, 'SEED-FAILED', $settling],
        ];

        foreach ($payments as [$invoice, $amount, $status, $method, $code, $submitter]) {
            Payment::updateOrCreate(
                ['transaction_code' => $code],
                [
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $amount,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $method,
                    'status' => $status,
                    'submitted_by' => $method === Payment::METHOD_CASH ? null : $submitter->id,
                    'confirmed_by' => $status === Payment::STATUS_PENDING ? null : $admin->id,
                    'reviewed_at' => $status === Payment::STATUS_PENDING ? null : now(),
                    'review_note' => $status === Payment::STATUS_FAILED ? 'Minh chứng không hợp lệ.' : null,
                    'note' => 'Dữ liệu kiểm thử thanh toán xuyên suốt.',
                ]
            );
        }

        $tenant = $active->tenant()->with('contracts')->sole();
        foreach ([
            SupportRequest::STATUS_NEW => ['repair', 'Sửa vòi nước'],
            SupportRequest::STATUS_IN_PROGRESS => ['utility', 'Kiểm tra công tơ'],
            SupportRequest::STATUS_RESOLVED => ['invoice', 'Giải đáp hóa đơn'],
            SupportRequest::STATUS_REJECTED => ['other', 'Yêu cầu không hợp lệ'],
        ] as $status => [$category, $subject]) {
            SupportRequest::updateOrCreate(
                ['submission_token' => "00000000-0000-4000-8000-{$this->supportTokenSuffix($status)}"],
                [
                    'user_id' => $active->id,
                    'tenant_id' => $tenant->id,
                    'contract_id' => $tenant->contracts->first()->id,
                    'category' => $category,
                    'subject' => $subject,
                    'description' => 'Dữ liệu kiểm thử yêu cầu hỗ trợ.',
                    'status' => $status,
                    'admin_response' => in_array($status, [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true)
                        ? 'Phản hồi mẫu từ quản trị viên.'
                        : null,
                    'handled_by' => $status === SupportRequest::STATUS_NEW ? null : $admin->id,
                    'responded_at' => in_array($status, [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true)
                        ? now()
                        : null,
                ]
            );
        }
    }

    private function supportTokenSuffix(string $status): string
    {
        return match ($status) {
            SupportRequest::STATUS_NEW => '000000000001',
            SupportRequest::STATUS_IN_PROGRESS => '000000000002',
            SupportRequest::STATUS_RESOLVED => '000000000003',
            default => '000000000004',
        };
    }

    private function seedClientScenario(
        string $email,
        string $code,
        string $cccd,
        string $phone,
        string $contractStatus,
        string $roomStatus,
        Carbon $startDate,
        Carbon $endDate,
        string $invoiceStatus,
    ): void {
        $checkedOut = in_array($contractStatus, [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED], true);
        $user = User::where('email', $email)->sole();
        $room = Room::updateOrCreate(
            ['room_code' => $code],
            [
                'floor' => 9,
                'price' => 3500000,
                'area' => 28,
                'max_people' => 3,
                'current_people' => $roomStatus === Room::STATUS_OCCUPIED ? 2 : 0,
                'description' => "Phòng dữ liệu kiểm thử {$code}",
                'status' => $roomStatus,
            ]
        );
        $tenant = Tenant::updateOrCreate(
            ['cccd' => $cccd],
            [
                'user_id' => $user->id,
                'full_name' => $user->name,
                'date_of_birth' => '1998-08-10',
                'gender' => 'other',
                'cccd_issue_date' => '2020-08-10',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'phone' => $phone,
                'email' => $email,
                'address' => 'Địa chỉ kiểm thử kịch bản AUTH',
            ]
        );
        $memberSuffix = str_pad((string) (abs(crc32($code)) % 100000000), 8, '0', STR_PAD_LEFT);
        $member = Tenant::updateOrCreate(
            ['cccd' => '0792'.$memberSuffix],
            [
                'user_id' => null,
                'full_name' => 'Thành viên '.$code,
                'date_of_birth' => '2000-01-15',
                'gender' => 'other',
                'cccd_issue_date' => '2022-01-15',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'phone' => '08'.$memberSuffix,
                'email' => strtolower($code).'.member@example.test',
                'address' => 'Địa chỉ thành viên kiểm thử '.$code,
            ]
        );
        $contract = Contract::updateOrCreate(
            ['contract_code' => "HD-{$code}"],
            [
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'representative_tenant_id' => $tenant->id,
                'representative_is_occupant' => true,
                'monthly_rent' => 3500000,
                'deposit_amount' => 7000000,
                'deposit_status' => Contract::DEPOSIT_PAID,
                'deposit_paid_at' => $startDate->copy()->subDays(5),
                'number_of_people' => 2,
                'signed_at' => $startDate->copy()->subDays(7),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'actual_end_date' => $checkedOut ? $endDate : null,
                'note' => "Hợp đồng dữ liệu kiểm thử {$code}",
            ]
        );
        $contract->forceFill([
            'status' => $contractStatus,
            'signed_at' => $startDate->copy()->subDays(7),
            'scheduled_move_in_date' => $startDate,
            'reservation_expires_at' => $startDate->copy()->addDays(2),
            'actual_move_in_at' => (in_array($contractStatus, Contract::OPEN_OCCUPANCY_STATUSES, true) || $checkedOut) ? $startDate : null,
            'actual_move_out_at' => $checkedOut ? $endDate : null,
        ])->save();
        $occupantStatus = in_array($contractStatus, Contract::OPEN_OCCUPANCY_STATUSES, true)
            ? ContractOccupant::STATUS_CHECKED_IN
            : ContractOccupant::STATUS_MOVED_OUT;
        foreach ([$tenant, $member] as $index => $person) {
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
                    'reviewed_at' => now(),
                    'actual_move_in_at' => $startDate,
                    'actual_move_out_at' => $occupantStatus === ContractOccupant::STATUS_MOVED_OUT ? $endDate : null,
                ]
            );
            ContractOccupantHistory::firstOrCreate(
                ['contract_occupant_id' => $occupant->id, 'action' => 'authentication_scenario_seed'],
                ['from_status' => null, 'to_status' => $occupantStatus, 'reason' => 'Dữ liệu kiểm thử xác thực.', 'performed_at' => now(), 'metadata' => ['seeded' => true]]
            );
        }

        $billingPeriod = $checkedOut
            ? $endDate->copy()->startOfMonth()
            : now()->subMonth()->startOfMonth();
        $reading = UtilityReading::updateOrCreate(
            ['room_id' => $room->id, 'month' => $billingPeriod->month, 'year' => $billingPeriod->year],
            [
                'record_date' => $billingPeriod->copy()->endOfMonth(),
                'electricity_old' => 1000,
                'electricity_new' => 1150,
                'water_old' => 100,
                'water_new' => 112,
                'status' => 'confirmed',
                'note' => "Chỉ số kiểm thử {$code}",
            ]
        );
        $invoice = Invoice::updateOrCreate(
            ['room_id' => $room->id, 'month' => $billingPeriod->month, 'year' => $billingPeriod->year],
            [
                'invoice_code' => "INV-{$code}-{$billingPeriod->format('Ym')}",
                'contract_id' => $contract->id,
                'utility_reading_id' => $reading->id,
                'invoice_date' => $billingPeriod->copy()->endOfMonth(),
                'due_date' => $billingPeriod->copy()->endOfMonth()->addDays(7),
                'room_fee' => 3500000,
                'electricity_fee' => 525000,
                'water_fee' => 180000,
                'internet_fee' => 100000,
                'service_fee' => 50000,
                'total_amount' => 4355000,
                'status' => $invoiceStatus,
            ]
        );

        $details = [
            ['type' => 'room', 'name' => 'Tiền phòng', 'quantity' => 1, 'unit' => 'tháng', 'unit_price' => 3500000, 'amount' => 3500000],
            ['type' => 'electricity', 'name' => 'Tiền điện', 'quantity' => 150, 'unit' => 'kWh', 'unit_price' => 3500, 'amount' => 525000, 'old_index' => 1000, 'new_index' => 1150],
            ['type' => 'water', 'name' => 'Tiền nước', 'quantity' => 12, 'unit' => 'm³', 'unit_price' => 15000, 'amount' => 180000, 'old_index' => 100, 'new_index' => 112],
            ['type' => 'internet', 'name' => 'Internet', 'quantity' => 1, 'unit' => 'tháng', 'unit_price' => 100000, 'amount' => 100000],
            ['type' => 'service', 'name' => 'Phí dịch vụ', 'quantity' => 1, 'unit' => 'tháng', 'unit_price' => 50000, 'amount' => 50000],
        ];

        foreach ($details as $sortOrder => $detail) {
            InvoiceDetail::updateOrCreate(
                ['invoice_id' => $invoice->id, 'sort_order' => $sortOrder],
                $detail + ['sort_order' => $sortOrder]
            );
        }
    }
}

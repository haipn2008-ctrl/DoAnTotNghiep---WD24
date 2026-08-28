<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractExtensionRequest;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractStatusHistory;
use App\Models\ContractTemplate;
use App\Models\ContractTenant;
use App\Models\ContractTenantHistory;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SupportRequest;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\Vehicle;
use App\Services\ContractDocumentService;
use App\Services\InvoiceGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessScenarioSeeder extends Seeder
{
    private User $admin;

    private Setting $setting;

    /** @var array<string, Contract> */
    private array $contracts = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->admin = User::query()->where('email', 'admin@nhatroanphuc.test')->sole();
            $this->setting = Setting::query()->where('is_active', true)->sole();
            if (Contract::query()->where('contract_code', 'QA-01-DRAFT')->exists()) {
                $this->loadExistingContracts();
                $this->syncNewWorkflowScenarios();

                return;
            }

            $this->seedContractScenarios();
            $this->seedBillingScenarios();
            $this->seedVehicleScenarios();
            $this->seedRequestScenarios();
            $this->seedTemporaryResidenceScenarios();
            $this->seedLifecycleAlerts();
            $this->seedStandaloneRooms();
            $this->syncNewWorkflowScenarios();
        });
    }

    private function seedContractScenarios(): void
    {
        $definitions = [
            // Mỗi mã phòng là một tình huống độc lập để kiểm thử thủ công.
            ['draft', 'QA-01', 'qa.client.a@example.test', Contract::STATUS_DRAFT, Contract::DEPOSIT_PENDING, 1],
            ['pending_signature', 'QA-02', 'qa.client.b@example.test', Contract::STATUS_PENDING_SIGNATURE, Contract::DEPOSIT_PENDING, 2],
            ['pending_deposit', 'QA-03', 'qa.client.c@example.test', Contract::STATUS_PENDING_DEPOSIT, Contract::DEPOSIT_PENDING, 1],
            ['awaiting_move_in', 'QA-04', 'giahuy@example.test', Contract::STATUS_AWAITING_MOVE_IN, Contract::DEPOSIT_PAID, 2],
            // Hai người đã nhận phòng, một yêu cầu pending bên dưới sẽ giữ slot cuối cùng.
            ['active_unpaid', 'QA-05', 'ngocmai@example.test', Contract::STATUS_ACTIVE, Contract::DEPOSIT_PAID, 2],
            ['active_partial', 'QA-06', 'ducanh@example.test', Contract::STATUS_ACTIVE, Contract::DEPOSIT_PAID, 2],
            ['active_paid', 'QA-07', 'khanhlinh@example.test', Contract::STATUS_ACTIVE, Contract::DEPOSIT_PAID, 2],
            ['expired', 'QA-08', 'quangnam@example.test', Contract::STATUS_EXPIRED, Contract::DEPOSIT_PAID, 2],
            ['settling', 'QA-09', 'thanhthao@example.test', Contract::STATUS_SETTLING, Contract::DEPOSIT_NEEDS_RESOLUTION, 2],
            ['refund_requested', 'QA-10', 'tuankiet@example.test', Contract::STATUS_SETTLING, Contract::DEPOSIT_REFUND_REQUESTED, 1],
            ['refund_approved', 'QA-11', 'phuonguyen@example.test', Contract::STATUS_SETTLING, Contract::DEPOSIT_REFUND_APPROVED, 1],
            ['refund_processing', 'QA-12', 'hoanganh.nguyen@example.test', Contract::STATUS_SETTLING, Contract::DEPOSIT_REFUND_PROCESSING, 1],
            ['completed_refunded', 'QA-13', 'baongoc.tran@example.test', Contract::STATUS_COMPLETED, Contract::DEPOSIT_REFUNDED, 1],
            ['completed_deducted', 'QA-14', 'minhduc.pham@example.test', Contract::STATUS_COMPLETED, Contract::DEPOSIT_DEDUCTED, 1],
            ['completed_retained', 'QA-15', 'thutrang.le@example.test', Contract::STATUS_COMPLETED, Contract::DEPOSIT_RETAINED, 1],
            ['cancelled', 'QA-16', 'khanhtoan.vu@example.test', Contract::STATUS_CANCELLED, Contract::DEPOSIT_PENDING, 1],
        ];

        foreach ($definitions as $index => [$key, $roomCode, $email, $status, $depositStatus, $people]) {
            $tenant = $this->tenantFor($email, $index + 1);
            $room = $this->roomFor($roomCode, $status, $people, $index + 1);
            $contract = $this->createContract($key, $room, $tenant, $status, $depositStatus, $people, $index + 1);
            $this->contracts[$key] = $contract;
            $this->seedMembers($contract, $tenant, $status, $people, $index + 1);
            $this->seedHandoverSnapshot($contract);

            if (! in_array($status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_SIGNATURE, Contract::STATUS_CANCELLED], true)) {
                $this->seedDepositInvoice($contract, $depositStatus !== Contract::DEPOSIT_PENDING);
            }
        }

        $this->seedExceptionalMemberStates($this->contracts['active_unpaid']);
    }

    private function tenantFor(string $email, int $index): Tenant
    {
        $user = User::query()->where('email', $email)->sole();
        $tenant = Tenant::query()->where('user_id', $user->id)->first();
        if ($tenant) {
            return $tenant;
        }
        $tenant = new Tenant;
        $tenant->fill([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'date_of_birth' => Carbon::create(1990 + ($index % 10), (($index - 1) % 12) + 1, 10),
            'gender' => $index % 2 === 0 ? 'female' : 'male',
            'cccd' => sprintf('099%09d', $index),
            'cccd_issue_date' => '2021-01-15',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => 'Địa chỉ kiểm thử nghiệp vụ số '.$index.', Hà Nội',
        ])->save();

        return $tenant;
    }

    private function roomFor(string $code, string $contractStatus, int $people, int $index): Room
    {
        $occupied = in_array($contractStatus, Contract::OPEN_OCCUPANCY_STATUSES, true);
        $room = Room::query()->create([
            'room_code' => $code,
            'floor' => (int) ceil($index / 4),
            'price' => 3000000 + (($index % 4) * 250000),
            'area' => 22 + $index,
            'max_people' => max(3, $people),
            'current_people' => $occupied ? $people : 0,
            'status' => $occupied ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
            'description' => 'Dữ liệu kiểm thử: '.$this->scenarioLabel($contractStatus),
        ]);

        $assets = Amenity::query()->active()->assets()->take(4)->get();
        $room->amenities()->sync($assets->mapWithKeys(fn (Amenity $asset, int $assetIndex) => [
            $asset->id => [
                'quantity' => $assetIndex === 0 ? 2 : 1,
                'condition' => $index === 14 && $assetIndex === 0 ? 'damaged' : 'normal',
                'note' => $index === 14 && $assetIndex === 0 ? 'Hư hỏng để kiểm thử khấu trừ cọc.' : null,
            ],
        ])->all());

        return $room;
    }

    private function createContract(
        string $key,
        Room $room,
        Tenant $tenant,
        string $status,
        string $depositStatus,
        int $people,
        int $index,
    ): Contract {
        $preMoveIn = in_array($status, [
            Contract::STATUS_DRAFT,
            Contract::STATUS_PENDING_SIGNATURE,
            Contract::STATUS_PENDING_DEPOSIT,
            Contract::STATUS_AWAITING_MOVE_IN,
            Contract::STATUS_CANCELLED,
        ], true);
        $start = $preMoveIn ? now()->addMonthNoOverflow()->startOfMonth() : now()->subMonthNoOverflow()->startOfMonth();
        $end = $status === Contract::STATUS_EXPIRED
            ? now()->subDay()->startOfDay()
            : $start->copy()->addYear()->subDay();
        $movedOut = in_array($status, [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED], true);
        $signed = ! in_array($status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_SIGNATURE], true);
        $depositAmount = (float) $room->price; // Cọc đúng một tháng tiền phòng.

        $contract = Contract::query()->create([
            'contract_code' => sprintf('QA-%02d-%s', $index, strtoupper(str_replace('_', '-', $key))),
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'representative_tenant_id' => $tenant->id,
            'monthly_rent' => $room->price,
            'deposit_amount' => $depositAmount,
            'deposit_status' => $depositStatus,
            'number_of_people' => $people,
            'internet_enabled' => true,
            'service_enabled' => true,
            'parking_vehicle_type' => null,
            'parking_quantity' => 0,
            'start_date' => $start,
            'end_date' => $end,
            'scheduled_move_in_date' => $start,
            'reservation_expires_at' => $start->copy()->addDays(3),
            'note' => 'Kịch bản kiểm thử: '.$key,
        ]);

        $completed = $status === Contract::STATUS_COMPLETED;
        $contract->forceFill([
            'status' => $status,
            'signed_at' => $signed ? now()->subMonths(2) : null,
            'signed_confirmed_by' => $signed ? $this->admin->id : null,
            'signature_due_at' => $status === Contract::STATUS_PENDING_SIGNATURE ? now()->subDay() : $start->copy()->subDays(10),
            'deposit_due_at' => $status === Contract::STATUS_PENDING_DEPOSIT ? now()->subDay() : $start->copy()->subDays(5),
            'deposit_paid_at' => $depositStatus === Contract::DEPOSIT_PENDING ? null : $start->copy()->subDays(4),
            'move_in_terms_confirmed_at' => $status === Contract::STATUS_DRAFT ? null : now()->subMonths(2),
            'move_in_terms_confirmed_by' => $status === Contract::STATUS_DRAFT ? null : $this->admin->id,
            'move_in_details_confirmed_at' => $signed ? $start->copy()->subDay() : null,
            'move_in_details_confirmed_by' => $signed ? $tenant->user_id : null,
            'actual_move_in_at' => $preMoveIn ? null : $start,
            'checked_in_by' => $preMoveIn ? null : $this->admin->id,
            'actual_move_out_at' => $movedOut ? now()->subDays(3) : null,
            'actual_end_date' => $movedOut ? now()->subDays(3) : null,
            'checked_out_by' => $movedOut ? $this->admin->id : null,
            'checkout_reason' => $movedOut ? 'Đã trả phòng, chờ xử lý công nợ và tiền cọc.' : null,
            'cancelled_at' => $status === Contract::STATUS_CANCELLED ? now()->subDay() : null,
            'cancelled_by' => $status === Contract::STATUS_CANCELLED ? $this->admin->id : null,
            'cancel_reason' => $status === Contract::STATUS_CANCELLED ? 'Khách không tiếp tục nhận phòng.' : null,
            'completed_at' => $completed ? now()->subDay() : null,
            'completed_by' => $completed ? $this->admin->id : null,
            'deposit_resolution' => $completed ? $depositStatus : null,
            'deposit_resolved_at' => $completed ? now()->subDay() : null,
            'deposit_resolved_by' => $completed ? $this->admin->id : null,
            'deposit_refund_amount' => in_array($depositStatus, [Contract::DEPOSIT_REFUNDED, Contract::DEPOSIT_REFUND_PROCESSING], true) ? $depositAmount : 0,
            'deposit_deduction_amount' => $depositStatus === Contract::DEPOSIT_DEDUCTED ? 500000 : 0,
            'deposit_process_reason' => $depositStatus === Contract::DEPOSIT_DEDUCTED ? 'Khấu trừ chi phí tài sản hư hỏng.' : null,
            'deposit_bank_name' => str_starts_with($key, 'refund_') ? 'MB' : null,
            'deposit_bank_account_number' => str_starts_with($key, 'refund_') ? '0123456789' : null,
            'deposit_bank_account_name' => str_starts_with($key, 'refund_') ? strtoupper($tenant->full_name) : null,
            'deposit_refund_requested_at' => str_starts_with($key, 'refund_') ? now()->subDays(2) : null,
            'deposit_refund_approved_at' => in_array($depositStatus, [Contract::DEPOSIT_REFUND_APPROVED, Contract::DEPOSIT_REFUND_PROCESSING], true) ? now()->subDay() : null,
            'deposit_transfer_amount' => $depositStatus === Contract::DEPOSIT_REFUND_PROCESSING ? $depositAmount : 0,
            'landlord_name_snapshot' => $this->setting->landlord_name,
            'landlord_address_snapshot' => $this->setting->landlord_address,
            'landlord_phone_snapshot' => $this->setting->landlord_phone,
            'landlord_identity_snapshot' => $this->setting->landlord_identity_number,
            'property_address_snapshot' => $this->setting->property_address,
            'electric_price_snapshot' => $signed ? $this->setting->electric_price : null,
            'water_price_snapshot' => $signed ? $this->setting->water_price : null,
            'internet_fee_snapshot' => $signed ? $this->setting->internet_fee : null,
            'service_fee_snapshot' => $signed ? $this->setting->service_fee : null,
        ])->save();

        ContractStatusHistory::query()->create([
            'contract_id' => $contract->id,
            'from_status' => null,
            'to_status' => $status,
            'action' => 'business_scenario_seed',
            'reason' => 'Khởi tạo dữ liệu kiểm thử '.$key.'.',
            'performed_by' => $this->admin->id,
            'performed_at' => now(),
            'metadata' => ['scenario' => $key],
        ]);

        return $contract;
    }

    private function seedMembers(Contract $contract, Tenant $representative, string $contractStatus, int $people, int $seed): void
    {
        $status = match (true) {
            in_array($contractStatus, Contract::OPEN_OCCUPANCY_STATUSES, true) => ContractTenant::STATUS_CHECKED_IN,
            in_array($contractStatus, [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED], true) => ContractTenant::STATUS_MOVED_OUT,
            $contractStatus === Contract::STATUS_CANCELLED => ContractTenant::STATUS_WITHDRAWN,
            default => ContractTenant::STATUS_APPROVED,
        };

        $peopleModels = [$representative];
        for ($memberIndex = 2; $memberIndex <= $people; $memberIndex++) {
            $number = 500 + ($seed * 10) + $memberIndex;
            $peopleModels[] = Tenant::query()->create([
                'full_name' => "Thành viên QA {$seed}.{$memberIndex}",
                'date_of_birth' => '1995-05-20',
                'gender' => $memberIndex % 2 ? 'female' : 'male',
                'cccd' => sprintf('088%09d', $number),
                'cccd_issue_date' => '2021-03-01',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'phone' => sprintf('097%07d', $number),
                'email' => "member{$seed}.{$memberIndex}@example.test",
                'address' => 'Hà Nội',
            ]);
        }

        foreach ($peopleModels as $index => $person) {
            $membership = ContractTenant::query()->create([
                'contract_id' => $contract->id,
                'tenant_id' => $person->id,
                'role' => $index === 0 ? ContractTenant::ROLE_REPRESENTATIVE : ContractTenant::ROLE_TENANT,
                'full_name' => $person->full_name,
                'date_of_birth' => $person->date_of_birth,
                'identity_number' => $person->cccd,
                'phone' => $person->phone,
                'relationship' => $index === 0 ? 'Người đại diện hợp đồng' : 'Người thuê cùng phòng',
                'address' => $person->address,
                'status' => $status,
                'reviewed_by' => $this->admin->id,
                'reviewed_at' => now(),
                'actual_move_in_at' => in_array($status, [ContractTenant::STATUS_CHECKED_IN, ContractTenant::STATUS_MOVED_OUT], true) ? $contract->start_date : null,
                'actual_move_out_at' => $status === ContractTenant::STATUS_MOVED_OUT ? $contract->actual_move_out_at : null,
            ]);
            ContractTenantHistory::query()->create([
                'contract_tenant_id' => $membership->id,
                'from_status' => null,
                'to_status' => $status,
                'action' => 'business_scenario_seed',
                'reason' => 'Dữ liệu kiểm thử trạng thái người thuê.',
                'performed_by' => $this->admin->id,
                'performed_at' => now(),
                'metadata' => ['seeded' => true],
            ]);
        }
    }

    private function seedExceptionalMemberStates(Contract $contract): void
    {
        foreach ([
            ContractTenant::STATUS_PENDING,
            ContractTenant::STATUS_REJECTED,
            ContractTenant::STATUS_WITHDRAWN,
        ] as $index => $status) {
            $person = Tenant::query()->create([
                'full_name' => 'QA thành viên '.str_replace('_', ' ', $status),
                'date_of_birth' => '1994-01-01',
                'gender' => 'other',
                'cccd' => sprintf('077000000%03d', $index + 1),
                'cccd_issue_date' => '2021-03-01',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'phone' => sprintf('0960000%03d', $index + 1),
                'email' => 'member-state-'.$status.'@example.test',
                'address' => 'Hà Nội',
            ]);
            $membership = ContractTenant::query()->create([
                'contract_id' => $contract->id,
                'tenant_id' => $person->id,
                'role' => ContractTenant::ROLE_TENANT,
                'full_name' => $person->full_name,
                'date_of_birth' => $person->date_of_birth,
                'identity_number' => $person->cccd,
                'phone' => $person->phone,
                'relationship' => 'Người thuê khai báo bổ sung',
                'status' => $status,
                'reviewed_by' => $status === ContractTenant::STATUS_PENDING ? null : $this->admin->id,
                'reviewed_at' => $status === ContractTenant::STATUS_PENDING ? null : now(),
                'review_note' => $status === ContractTenant::STATUS_REJECTED ? 'Thông tin CCCD chưa hợp lệ.' : null,
            ]);
            ContractTenantHistory::query()->create([
                'contract_tenant_id' => $membership->id,
                'from_status' => null,
                'to_status' => $status,
                'action' => 'business_scenario_seed',
                'performed_at' => now(),
                'metadata' => ['seeded' => true],
            ]);
        }
    }

    private function seedHandoverSnapshot(Contract $contract): void
    {
        if ($contract->status === Contract::STATUS_DRAFT) {
            return;
        }

        foreach ($contract->room->amenities as $asset) {
            $contract->handoverItems()->create([
                'amenity_id' => $asset->id,
                'name' => $asset->name,
                'description' => $asset->description,
                'is_quantifiable' => $asset->is_quantifiable,
                'quantity' => $asset->pivot->quantity,
                'condition' => $asset->pivot->condition,
                'note' => $asset->pivot->note,
            ]);
        }
        $contract->forceFill(['move_in_inventory_snapshotted_at' => now()->subMonths(2)])->save();
    }

    private function seedDepositInvoice(Contract $contract, bool $paid): void
    {
        $invoice = Invoice::query()->create([
            'contract_id' => $contract->id,
            'invoice_type' => Invoice::TYPE_DEPOSIT,
            'invoice_code' => 'DEP-'.$contract->contract_code,
            'lifecycle_event_key' => "contract:{$contract->id}:deposit",
            'room_id' => $contract->room_id,
            'month' => $contract->start_date->month,
            'year' => $contract->start_date->year,
            'invoice_date' => now()->subMonths(2)->toDateString(),
            'due_date' => $contract->deposit_due_at?->toDateString() ?? now()->toDateString(),
            'room_fee' => 0,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => $contract->deposit_amount,
            'status' => $paid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
        ]);
        $invoice->details()->create([
            'type' => Invoice::TYPE_DEPOSIT,
            'name' => 'Tiền cọc hợp đồng '.$contract->contract_code,
            'quantity' => 1,
            'unit' => 'lần',
            'unit_price' => $contract->deposit_amount,
            'amount' => $contract->deposit_amount,
            'sort_order' => 1,
        ]);

        if ($paid) {
            Payment::query()->create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->total_amount,
                'payment_date' => $contract->deposit_paid_at?->toDateString() ?? now()->subMonth()->toDateString(),
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
                'transaction_code' => 'COC-'.$contract->id,
                'status' => Payment::STATUS_SUCCESS,
                'submitted_by' => $contract->tenant->user_id,
                'confirmed_by' => $this->admin->id,
                'reviewed_at' => now()->subMonth(),
                'note' => 'Chỉ thu tiền cọc trước khi khách nhận phòng.',
            ]);
        }
    }

    private function seedBillingScenarios(): void
    {
        $cases = [
            'active_unpaid' => Invoice::STATUS_UNPAID,
            'active_partial' => Invoice::STATUS_PARTIAL,
            'active_paid' => Invoice::STATUS_PAID,
            'expired' => Invoice::STATUS_WRITTEN_OFF,
            'settling' => Invoice::STATUS_UNPAID,
        ];

        foreach ($cases as $key => $targetStatus) {
            $contract = $this->contracts[$key];
            $servicePeriod = now()->subMonthNoOverflow()->startOfMonth();
            $base = 1000 + ($contract->id * 20);
            UtilityReading::query()->create([
                'room_id' => $contract->room_id,
                'contract_id' => $contract->id,
                'month' => $servicePeriod->month,
                'year' => $servicePeriod->year,
                'reading_type' => 'periodic',
                'record_date' => $servicePeriod->copy()->endOfMonth(),
                'electricity_old' => $base,
                'electricity_new' => $base + 120,
                'water_old' => 100,
                'water_new' => 108,
                'status' => 'confirmed',
                'note' => 'Chỉ số chốt cuối tháng để thu vào ngày 05 tháng sau.',
            ]);

            $invoice = app(InvoiceGenerator::class)->issue($contract, now()->month, now()->year);
            if ($targetStatus === Invoice::STATUS_PARTIAL || $targetStatus === Invoice::STATUS_PAID) {
                $amount = $targetStatus === Invoice::STATUS_PAID
                    ? (float) $invoice->total_amount
                    : round((float) $invoice->total_amount / 2);
                Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $amount,
                    'payment_date' => $invoice->due_date,
                    'payment_method' => $targetStatus === Invoice::STATUS_PAID ? Payment::METHOD_QR : Payment::METHOD_CASH,
                    'transaction_code' => 'THUE-'.$contract->id,
                    'status' => Payment::STATUS_SUCCESS,
                    'submitted_by' => $targetStatus === Invoice::STATUS_PAID ? $contract->tenant->user_id : null,
                    'confirmed_by' => $this->admin->id,
                    'reviewed_at' => now(),
                ]);
                $invoice->refreshStatus();
            } elseif ($targetStatus === Invoice::STATUS_UNPAID && $key === 'active_unpaid') {
                foreach ([Payment::STATUS_PENDING, Payment::STATUS_FAILED] as $index => $paymentStatus) {
                    Payment::query()->create([
                        'invoice_id' => $invoice->id,
                        'amount_paid' => 500000,
                        'payment_date' => now()->toDateString(),
                        'payment_method' => Payment::METHOD_BANK_TRANSFER,
                        'transaction_code' => 'CHO-DUYET-'.$contract->id.'-'.$index,
                        'status' => $paymentStatus,
                        'submitted_by' => $contract->tenant->user_id,
                        'confirmed_by' => $paymentStatus === Payment::STATUS_FAILED ? $this->admin->id : null,
                        'reviewed_at' => $paymentStatus === Payment::STATUS_FAILED ? now() : null,
                        'review_note' => $paymentStatus === Payment::STATUS_FAILED ? 'Ảnh giao dịch không hợp lệ.' : null,
                    ]);
                }
            } elseif ($targetStatus === Invoice::STATUS_WRITTEN_OFF) {
                $invoice->forceFill([
                    'status' => Invoice::STATUS_WRITTEN_OFF,
                    'written_off_at' => now(),
                    'written_off_by' => $this->admin->id,
                    'write_off_reason' => 'Xóa nợ có phê duyệt để kiểm thử.',
                ])->save();
            }

            $this->seedHandoverReading($contract, $base);
            if ($contract->actual_move_out_at) {
                $this->seedCheckoutReading($contract, $base + 120);
            }
        }
    }

    private function seedHandoverReading(Contract $contract, int $electricity): void
    {
        UtilityReading::query()->create([
            'room_id' => $contract->room_id,
            'contract_id' => $contract->id,
            'month' => $contract->start_date->month,
            'year' => $contract->start_date->year,
            'reading_type' => 'handover',
            'record_date' => $contract->start_date,
            'electricity_old' => $electricity,
            'electricity_new' => $electricity,
            'water_old' => 100,
            'water_new' => 100,
            'status' => 'confirmed',
            'lifecycle_event_key' => "contract:{$contract->id}:handover",
            'note' => 'Chỉ số lúc nhận phòng.',
        ]);
    }

    private function seedCheckoutReading(Contract $contract, int $electricity): void
    {
        UtilityReading::query()->create([
            'room_id' => $contract->room_id,
            'contract_id' => $contract->id,
            'month' => $contract->actual_move_out_at->month,
            'year' => $contract->actual_move_out_at->year,
            'reading_type' => 'checkout',
            'record_date' => $contract->actual_move_out_at,
            'electricity_old' => $electricity,
            'electricity_new' => $electricity,
            'water_old' => 108,
            'water_new' => 108,
            'status' => 'confirmed',
            'lifecycle_event_key' => "contract:{$contract->id}:checkout",
            'note' => 'Chỉ số lúc trả phòng.',
        ]);
    }

    private function seedVehicleScenarios(): void
    {
        $cases = [
            'active_unpaid' => [Vehicle::STATUS_PENDING, '29A-10001'],
            'active_partial' => [Vehicle::STATUS_APPROVED, '29A-10002'],
            'active_paid' => [Vehicle::STATUS_REJECTED, '29A-10003'],
        ];
        foreach ($cases as $key => [$status, $plate]) {
            $contract = $this->contracts[$key];
            $owner = $contract->members()->where('status', ContractTenant::STATUS_CHECKED_IN)->oldest('id')->firstOrFail()->tenant;
            $vehicle = Vehicle::query()->create([
                'tenant_id' => $owner->id,
                'vehicle_type' => 'motorcycle',
                'vehicle_name' => 'Xe của '.$owner->full_name,
                'license_plate' => $plate,
                'status' => $status,
                'submitted_by' => $contract->tenant->user_id,
                'reviewed_by' => $status === Vehicle::STATUS_PENDING ? null : $this->admin->id,
                'reviewed_at' => $status === Vehicle::STATUS_PENDING ? null : now(),
                'review_note' => $status === Vehicle::STATUS_REJECTED ? 'Biển số chưa rõ.' : null,
            ]);

            if ($status === Vehicle::STATUS_PENDING) {
                ContractLifecycleAlert::query()->create([
                    'contract_id' => $contract->id,
                    'tenant_id' => $owner->id,
                    'vehicle_id' => $vehicle->id,
                    'type' => 'vehicle_review',
                    'dedupe_key' => 'vehicle:'.$vehicle->id,
                    'title' => 'Phương tiện chờ duyệt',
                    'message' => $owner->full_name.' đã gửi đăng ký xe.',
                    'metadata' => ['vehicle_id' => $vehicle->id],
                    'detected_at' => now(),
                ]);
            }
        }
    }

    private function seedRequestScenarios(): void
    {
        $contract = $this->contracts['active_paid'];
        foreach ([
            ContractExtensionRequest::STATUS_PENDING,
            ContractExtensionRequest::STATUS_APPROVED,
            ContractExtensionRequest::STATUS_REJECTED,
        ] as $index => $status) {
            $statusLabel = match ($status) {
                ContractExtensionRequest::STATUS_APPROVED => 'đã được duyệt',
                ContractExtensionRequest::STATUS_REJECTED => 'đã bị từ chối',
                default => 'đang chờ duyệt',
            };
            ContractExtensionRequest::query()->create([
                'contract_id' => $contract->id,
                'current_end_date' => $contract->end_date,
                'requested_end_date' => $contract->end_date->copy()->addMonths(6 + $index),
                'reason' => 'Yêu cầu gia hạn mẫu '.$statusLabel.'.',
                'status' => $status,
                'admin_note' => $status === ContractExtensionRequest::STATUS_REJECTED ? 'Phòng đã có lịch bảo trì.' : null,
                'processed_at' => $status === ContractExtensionRequest::STATUS_PENDING ? null : now(),
            ]);
        }

        foreach ([
            ContractTerminationRequest::STATUS_PENDING,
            ContractTerminationRequest::STATUS_APPROVED,
            ContractTerminationRequest::STATUS_REJECTED,
        ] as $index => $status) {
            ContractTerminationRequest::query()->create([
                'contract_id' => $contract->id,
                'tenant_id' => $contract->tenant_id,
                'requested_end_date' => now()->addMonths(1 + $index),
                'reason' => 'Yêu cầu trả phòng mẫu: '.$status,
                'status' => $status,
                'admin_note' => $status === ContractTerminationRequest::STATUS_REJECTED ? 'Ngày trả phòng không hợp lệ.' : null,
                'processed_at' => $status === ContractTerminationRequest::STATUS_PENDING ? null : now(),
            ]);
        }

        $supportCases = [
            [SupportRequest::STATUS_NEW, 'repair', 'Vòi nước bị rò'],
            [SupportRequest::STATUS_IN_PROGRESS, 'utility', 'Kiểm tra chỉ số điện'],
            [SupportRequest::STATUS_RESOLVED, 'invoice', 'Giải thích hóa đơn'],
            [SupportRequest::STATUS_REJECTED, 'other', 'Yêu cầu không thuộc phạm vi'],
        ];
        foreach ($supportCases as $index => [$status, $category, $subject]) {
            SupportRequest::query()->create([
                'submission_token' => sprintf('20000000-0000-4000-8000-%012d', $index + 1),
                'user_id' => $contract->tenant->user_id,
                'tenant_id' => $contract->tenant_id,
                'contract_id' => $contract->id,
                'category' => $category,
                'subject' => $subject,
                'description' => 'Dữ liệu kiểm thử yêu cầu hỗ trợ.',
                'status' => $status,
                'admin_response' => in_array($status, [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true) ? 'Phản hồi mẫu của quản trị viên.' : null,
                'handled_by' => $status === SupportRequest::STATUS_NEW ? null : $this->admin->id,
                'responded_at' => in_array($status, [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true) ? now() : null,
            ]);
        }
    }

    private function seedTemporaryResidenceScenarios(): void
    {
        $contract = $this->contracts['active_partial'];
        foreach (['pending', 'active', 'expired', 'cancelled'] as $index => $status) {
            TemporaryResidence::query()->create([
                'tenant_id' => $contract->tenant_id,
                'contract_id' => $contract->id,
                'start_date' => now()->subMonths($index + 1),
                'end_date' => in_array($status, ['expired', 'cancelled'], true) ? now()->subDays($index + 1) : null,
                'status' => $status,
                'note' => 'Kịch bản tạm trú: '.$status,
                'signed_at' => $status === 'active' ? now()->subMonth() : null,
            ]);
        }
    }

    private function seedLifecycleAlerts(): void
    {
        $cases = [
            ['pending_signature', 'signature_overdue', 'Quá hạn ký', 'Hợp đồng đã quá hạn ký.'],
            ['pending_deposit', 'deposit_overdue', 'Quá hạn cọc', 'Khách chưa hoàn tất tiền cọc.'],
            ['awaiting_move_in', 'move_in_overdue', 'Quá hạn nhận phòng', 'Khách chưa đến nhận phòng.'],
            ['expired', 'contract_expired', 'Hợp đồng hết hạn', 'Khách vẫn đang ở sau ngày hết hạn.'],
            ['settling', 'deposit_exception', 'Cần xử lý tiền cọc', 'Hợp đồng đang chờ quyết toán tiền cọc.'],
        ];
        foreach ($cases as [$key, $type, $title, $message]) {
            $contract = $this->contracts[$key];
            ContractLifecycleAlert::query()->create([
                'contract_id' => $contract->id,
                'tenant_id' => $contract->tenant_id,
                'type' => $type,
                'dedupe_key' => 'seed:'.$key,
                'title' => $title,
                'message' => $message,
                'metadata' => ['scenario' => $key],
                'detected_at' => now(),
            ]);
        }
    }

    private function seedStandaloneRooms(): void
    {
        foreach ([
            ['QA-TRONG', Room::STATUS_AVAILABLE, 'Phòng trống có chỉ số điện nước ban đầu.'],
            ['QA-BAO-TRI', Room::STATUS_MAINTENANCE, 'Phòng đang bảo trì, không được lập hợp đồng mới.'],
        ] as $index => [$code, $status, $description]) {
            $room = Room::query()->create([
                'room_code' => $code,
                'floor' => 5,
                'price' => 3500000,
                'area' => 28,
                'max_people' => 3,
                'current_people' => 0,
                'status' => $status,
                'description' => $description,
            ]);
            UtilityReading::query()->create([
                'room_id' => $room->id,
                'contract_id' => null,
                'month' => now()->month,
                'year' => now()->year,
                'reading_type' => 'baseline',
                'record_date' => now()->toDateString(),
                'electricity_old' => 0,
                'electricity_new' => 1500 + ($index * 100),
                'water_old' => 0,
                'water_new' => 150 + ($index * 10),
                'status' => 'confirmed',
                'lifecycle_event_key' => 'room:'.$room->id.':baseline',
                'note' => 'Chỉ số nền của phòng mới.',
            ]);
        }
    }

    /** Nạp lại các hợp đồng QA khi chạy seeder lần hai trên database demo hiện có. */
    private function loadExistingContracts(): void
    {
        $codes = [
            'draft' => 'QA-01-DRAFT',
            'pending_signature' => 'QA-02-PENDING-SIGNATURE',
            'pending_deposit' => 'QA-03-PENDING-DEPOSIT',
            'awaiting_move_in' => 'QA-04-AWAITING-MOVE-IN',
            'active_unpaid' => 'QA-05-ACTIVE-UNPAID',
            'active_partial' => 'QA-06-ACTIVE-PARTIAL',
            'active_paid' => 'QA-07-ACTIVE-PAID',
            'expired' => 'QA-08-EXPIRED',
            'settling' => 'QA-09-SETTLING',
            'refund_requested' => 'QA-10-REFUND-REQUESTED',
            'refund_approved' => 'QA-11-REFUND-APPROVED',
            'refund_processing' => 'QA-12-REFUND-PROCESSING',
            'completed_refunded' => 'QA-13-COMPLETED-REFUNDED',
            'completed_deducted' => 'QA-14-COMPLETED-DEDUCTED',
            'completed_retained' => 'QA-15-COMPLETED-RETAINED',
            'cancelled' => 'QA-16-CANCELLED',
        ];

        $contracts = Contract::query()->whereIn('contract_code', array_values($codes))->get()->keyBy('contract_code');
        foreach ($codes as $key => $code) {
            if ($contract = $contracts->get($code)) {
                $this->contracts[$key] = $contract;
            }
        }
    }

    /** Đồng bộ dữ liệu mẫu cho snapshot hợp đồng, phụ lục giá và giới hạn số người. */
    private function syncNewWorkflowScenarios(): void
    {
        $this->syncCapacityScenario();
        $this->seedAppendixScenarios();

        $documents = app(ContractDocumentService::class);
        $template = ContractTemplate::activeOrCreate();
        foreach ($this->contracts as $contract) {
            if (! $contract->contract_template_id) {
                $contract->forceFill(['contract_template_id' => $template->id])->save();
            }

            if ($contract->signed_at && ! $contract->contract_content_snapshotted_at) {
                $documents->snapshotSignedDocument($contract->fresh());
            }
        }
    }

    /** Yêu cầu thêm người đang chờ duyệt cũng giữ một chỗ trong sức chứa phòng. */
    private function syncCapacityScenario(): void
    {
        $contract = $this->contracts['active_unpaid'] ?? null;
        if (! $contract) {
            return;
        }

        $reservedPeople = $contract->members()->current()->count();
        $checkedInPeople = $contract->members()
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->count();

        $contract->forceFill(['number_of_people' => $reservedPeople])->save();
        $contract->room->forceFill([
            'current_people' => $checkedInPeople,
            'max_people' => $reservedPeople,
        ])->save();
    }

    /** Tạo đủ ba trạng thái tiêu biểu và một phụ lục giá đã có hiệu lực. */
    private function seedAppendixScenarios(): void
    {
        if ($contract = $this->contracts['active_paid'] ?? null) {
            $adjustments = [
                'electric_price' => [
                    'old' => (float) $contract->electric_price_snapshot,
                    'new' => (float) $contract->electric_price_snapshot + 500,
                ],
                'water_price' => [
                    'old' => (float) $contract->water_price_snapshot,
                    'new' => (float) $contract->water_price_snapshot + 1000,
                ],
                'internet_fee' => [
                    'old' => (float) $contract->internet_fee_snapshot,
                    'new' => (float) $contract->internet_fee_snapshot + 20000,
                ],
                'service_fee' => [
                    'old' => (float) $contract->service_fee_snapshot,
                    'new' => (float) $contract->service_fee_snapshot + 10000,
                ],
            ];

            $this->createSeededAppendix(
                $contract,
                'Điều chỉnh nhiều đơn giá dịch vụ',
                'Hai bên thống nhất điều chỉnh giá điện, nước, Internet và phí dịch vụ chung cho các kỳ dịch vụ tiếp theo.',
                ContractAppendix::STATUS_ACCEPTED,
                now()->addMonthNoOverflow()->startOfMonth(),
                $adjustments,
            );
        }

        if ($contract = $this->contracts['active_partial'] ?? null) {
            $this->createSeededAppendix(
                $contract,
                ContractTemplate::CLAUSE_LABELS['monthly_payment'],
                'Bên B thanh toán hóa đơn chậm nhất vào hết ngày 10 hằng tháng theo thông báo trên hệ thống.',
                ContractAppendix::STATUS_PENDING_TENANT,
                now()->addWeek(),
            );
        }

        if ($contract = $this->contracts['active_unpaid'] ?? null) {
            $this->createSeededAppendix(
                $contract,
                ContractTemplate::CLAUSE_LABELS['tenant_obligations'],
                'Bên B có trách nhiệm cập nhật đầy đủ thông tin người ở và phương tiện phát sinh trong thời gian thuê.',
                ContractAppendix::STATUS_REJECTED,
                now()->addWeek(),
            );
        }
    }

    private function createSeededAppendix(
        Contract $contract,
        string $title,
        string $content,
        string $status,
        Carbon $effectiveFrom,
        ?array $priceAdjustments = null,
    ): ContractAppendix {
        $code = "PL-{$contract->contract_code}-01-R1";
        if ($existing = ContractAppendix::query()->where('code', $code)->first()) {
            return $existing;
        }

        $sentAt = now()->subDays(2);
        $respondedAt = $status === ContractAppendix::STATUS_PENDING_TENANT ? null : now()->subDay();
        $appendix = new ContractAppendix([
            'contract_id' => $contract->id,
            'appendix_number' => 1,
            'revision' => 1,
            'code' => $code,
            'title' => $title,
            'legal_basis' => 'Thỏa thuận bổ sung giữa Bên A và Bên B trong quá trình thực hiện hợp đồng.',
            'content' => $content,
            'price_adjustments' => $priceAdjustments,
            'effective_from' => $effectiveFrom->toDateString(),
            'status' => $status,
            'created_by' => $this->admin->id,
            'sent_at' => $sentAt,
            'sent_by' => $this->admin->id,
            'responded_at' => $respondedAt,
            'responded_by' => $respondedAt ? $contract->tenant->user_id : null,
            'accepted_at' => $status === ContractAppendix::STATUS_ACCEPTED ? $respondedAt : null,
            'rejected_at' => $status === ContractAppendix::STATUS_REJECTED ? $respondedAt : null,
            'rejection_reason' => $status === ContractAppendix::STATUS_REJECTED
                ? 'Khách đề nghị làm rõ phạm vi thông tin cần cập nhật trước khi chấp nhận.'
                : null,
        ]);
        $appendix->content_sha256 = hash('sha256', $appendix->hashPayload());
        $appendix->save();

        return $appendix;
    }

    private function scenarioLabel(string $status): string
    {
        return match ($status) {
            Contract::STATUS_DRAFT => 'hợp đồng bản nháp',
            Contract::STATUS_PENDING_SIGNATURE => 'chờ ký hợp đồng',
            Contract::STATUS_PENDING_DEPOSIT => 'chờ đóng cọc',
            Contract::STATUS_AWAITING_MOVE_IN => 'đã cọc, chờ nhận phòng',
            Contract::STATUS_ACTIVE => 'đang thuê',
            Contract::STATUS_EXPIRED => 'hết hạn nhưng vẫn đang ở',
            Contract::STATUS_SETTLING => 'đã trả phòng, đang quyết toán',
            Contract::STATUS_COMPLETED => 'đã hoàn tất',
            Contract::STATUS_CANCELLED => 'đã hủy',
            default => $status,
        };
    }
}

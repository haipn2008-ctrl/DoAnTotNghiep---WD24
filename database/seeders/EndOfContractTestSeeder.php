<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractTenant;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Notifications\ClientPortalNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EndOfContractTestSeeder extends Seeder
{
    private const PASSWORD = 'Test@123456';

    public function run(): void
    {
        DB::transaction(function (): void {
            $adminRole = Role::query()->firstOrCreate(['role_name' => 'Admin']);
            $clientRole = Role::query()->firstOrCreate(['role_name' => 'User']);
            $admin = User::query()->where('role_id', $adminRole->id)->where('status', User::STATUS_ACTIVE)->first()
                ?? User::query()->create([
                    'name' => 'Admin kiểm thử cuối hợp đồng',
                    'email' => 'endcycle.admin@example.test',
                    'phone' => '0909000099',
                    'role_id' => $adminRole->id,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => User::STATUS_ACTIVE,
                    'activated_at' => now(),
                ]);

            $definitions = [
                [
                    'key' => 'extension',
                    'room_code' => 'EOC-01',
                    'contract_code' => 'EOC-01-SAP-HET-HAN-GIA-HAN',
                    'email' => 'test.giahan@example.test',
                    'name' => 'Test Khách Muốn Gia Hạn',
                    'phone' => '0938000001',
                    'cccd' => '079900000001',
                    'end_date' => today()->addDays(7),
                    'requested_end_date' => today()->addDays(7)->addYear(),
                ],
                [
                    'key' => 'end_of_term',
                    'room_code' => 'EOC-02',
                    'contract_code' => 'EOC-02-DUNG-NGAY-HET-HAN',
                    'email' => 'test.hethan@example.test',
                    'name' => 'Test Khách Hết Hạn Hôm Nay',
                    'phone' => '0938000002',
                    'cccd' => '079900000002',
                    'end_date' => today(),
                    'requested_end_date' => today(),
                ],
                [
                    'key' => 'early_termination',
                    'room_code' => 'EOC-03',
                    'contract_code' => 'EOC-03-CHAM-DUT-TRUOC-HAN',
                    'email' => 'test.truochan@example.test',
                    'name' => 'Test Khách Chấm Dứt Trước Hạn',
                    'phone' => '0938000003',
                    'cccd' => '079900000003',
                    'end_date' => today()->addMonthsNoOverflow(6),
                    'requested_end_date' => today(),
                ],
            ];

            foreach ($definitions as $index => $definition) {
                $user = User::query()->updateOrCreate(
                    ['email' => $definition['email']],
                    [
                        'name' => $definition['name'],
                        'phone' => $definition['phone'],
                        'role_id' => $clientRole->id,
                        'password' => Hash::make(self::PASSWORD),
                        'status' => User::STATUS_ACTIVE,
                        'activated_at' => now(),
                        'must_change_password' => false,
                    ]
                );
                $tenant = Tenant::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'full_name' => $definition['name'],
                        'date_of_birth' => '1995-01-01',
                        'gender' => $index % 2 === 0 ? 'male' : 'female',
                        'cccd' => $definition['cccd'],
                        'phone' => $definition['phone'],
                        'email' => $definition['email'],
                        'address' => 'Địa chỉ kiểm thử chu kỳ cuối hợp đồng',
                    ]
                );
                $room = Room::query()->updateOrCreate(
                    ['room_code' => $definition['room_code']],
                    [
                        'floor' => 9,
                        'price' => 3000000 + ($index * 500000),
                        'area' => 25,
                        'max_people' => 2,
                        'current_people' => 1,
                        'status' => Room::STATUS_OCCUPIED,
                        'description' => 'Phòng dành cho kiểm thử '.$definition['contract_code'],
                    ]
                );

                $contract = Contract::query()->firstOrNew(['contract_code' => $definition['contract_code']]);
                if ($contract->exists) {
                    $contract->forceFill(['approved_termination_request_id' => null])->save();
                    $this->clearGeneratedEndCycleData($contract);
                }
                $contract->forceFill([
                    'room_id' => $room->id,
                    'tenant_id' => $tenant->id,
                    'representative_tenant_id' => $tenant->id,
                    'monthly_rent' => $room->price,
                    'deposit_amount' => $room->price,
                    'deposit_status' => Contract::DEPOSIT_PAID,
                    'deposit_paid_at' => now()->subMonths(5),
                    'number_of_people' => 1,
                    'internet_enabled' => true,
                    'service_enabled' => true,
                    'parking_quantity' => 0,
                    'start_date' => today()->subMonthsNoOverflow(6),
                    'end_date' => $definition['end_date'],
                    'scheduled_move_in_date' => today()->subMonthsNoOverflow(6),
                    'actual_move_in_at' => now()->subMonthsNoOverflow(6),
                    'signed_at' => now()->subMonthsNoOverflow(7),
                    'signed_confirmed_by' => $admin->id,
                    'checked_in_by' => $admin->id,
                    'move_in_terms_confirmed_at' => now()->subMonthsNoOverflow(7),
                    'move_in_terms_confirmed_by' => $admin->id,
                    'move_in_details_confirmed_at' => now()->subMonthsNoOverflow(6),
                    'move_in_details_confirmed_by' => $user->id,
                    'scheduled_move_out_at' => null,
                    'approved_termination_request_id' => null,
                    'actual_move_out_at' => null,
                    'actual_end_date' => null,
                    'checked_out_by' => null,
                    'checkout_reason' => null,
                    'terminated_at' => null,
                    'termination_note' => null,
                    'completed_at' => null,
                    'completed_by' => null,
                    'deposit_resolution' => null,
                    'deposit_resolved_at' => null,
                    'deposit_resolved_by' => null,
                    'deposit_refund_amount' => 0,
                    'deposit_deduction_amount' => 0,
                    'deposit_refund_requested_at' => null,
                    'deposit_refund_approved_at' => null,
                    'deposit_transferred_at' => null,
                    'status' => Contract::STATUS_ACTIVE,
                    'note' => 'Seeder kiểm thử: '.$definition['key'],
                ])->save();

                $this->seedRepresentative($contract, $tenant, $admin);
                $this->seedDeposit($contract, $user, $admin);
                $this->seedLatestReading($contract, $index);
                $this->resetAndSeedRequest($contract, $tenant, $definition);
            }
        });

    }

    private function clearGeneratedEndCycleData(Contract $contract): void
    {
        $statement = $contract->settlementStatement()->first();
        if ($statement) {
            $invoiceId = $statement->invoice_id;
            $statement->delete();
            if ($invoiceId) {
                Invoice::query()->whereKey($invoiceId)->delete();
            }
        }
        UtilityReading::query()
            ->where('contract_id', $contract->id)
            ->where('reading_type', 'checkout')
            ->delete();
    }

    private function seedRepresentative(Contract $contract, Tenant $tenant, User $admin): void
    {
        ContractTenant::query()->updateOrCreate(
            ['contract_id' => $contract->id, 'tenant_id' => $tenant->id],
            [
                'role' => ContractTenant::ROLE_REPRESENTATIVE,
                'full_name' => $tenant->full_name,
                'date_of_birth' => $tenant->date_of_birth,
                'identity_number' => $tenant->cccd,
                'phone' => $tenant->phone,
                'relationship' => 'Người đại diện hợp đồng',
                'address' => $tenant->address,
                'status' => ContractTenant::STATUS_CHECKED_IN,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subMonths(6),
                'actual_move_in_at' => $contract->actual_move_in_at,
                'actual_move_out_at' => null,
            ]
        );
    }

    private function seedDeposit(Contract $contract, User $client, User $admin): void
    {
        $invoice = Invoice::query()->firstOrNew(['lifecycle_event_key' => "contract:{$contract->id}:deposit"]);
        $invoice->forceFill([
            'contract_id' => $contract->id,
            'invoice_code' => 'DEP-'.$contract->contract_code,
            'invoice_type' => Invoice::TYPE_DEPOSIT,
            'room_id' => $contract->room_id,
            'month' => $contract->start_date->month,
            'year' => $contract->start_date->year,
            'invoice_date' => $contract->start_date->copy()->subDays(7),
            'due_date' => $contract->start_date->copy()->subDays(3),
            'room_fee' => 0,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => $contract->deposit_amount,
            'status' => Invoice::STATUS_PAID,
        ])->save();
        $invoice->details()->updateOrCreate(
            ['type' => Invoice::TYPE_DEPOSIT],
            [
                'name' => 'Tiền cọc hợp đồng '.$contract->contract_code,
                'quantity' => 1,
                'unit' => 'lần',
                'unit_price' => $contract->deposit_amount,
                'amount' => $contract->deposit_amount,
                'sort_order' => 1,
            ]
        );
        Payment::query()->updateOrCreate(
            ['transaction_code' => 'EOC-DEPOSIT-'.$contract->id],
            [
                'invoice_id' => $invoice->id,
                'amount_paid' => $contract->deposit_amount,
                'payment_date' => $contract->start_date->copy()->subDays(3),
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
                'status' => Payment::STATUS_SUCCESS,
                'submitted_by' => $client->id,
                'confirmed_by' => $admin->id,
                'reviewed_at' => now()->subMonths(6),
                'note' => 'Tiền cọc đã thu cho kịch bản kiểm thử cuối hợp đồng.',
            ]
        );
    }

    private function seedLatestReading(Contract $contract, int $index): void
    {
        $electricity = 1200 + ($index * 100);
        UtilityReading::query()->updateOrCreate(
            ['lifecycle_event_key' => "contract:{$contract->id}:eoc-latest"],
            [
                'room_id' => $contract->room_id,
                'contract_id' => $contract->id,
                'month' => today()->month,
                'year' => today()->year,
                'reading_type' => 'periodic',
                'record_date' => today(),
                'electricity_old' => $electricity - 100,
                'electricity_new' => $electricity,
                'water_old' => 100 + ($index * 10),
                'water_new' => 108 + ($index * 10),
                'status' => UtilityReading::STATUS_CONFIRMED,
                'note' => 'Chỉ số gần nhất để thử thao tác checkout.',
            ]
        );
    }

    private function resetAndSeedRequest(Contract $contract, Tenant $tenant, array $definition): void
    {
        $contract->extensionRequests()->delete();
        $contract->terminationRequests()->delete();
        ContractLifecycleAlert::query()->where('contract_id', $contract->id)->delete();

        if ($definition['key'] === 'extension') {
            $request = ContractExtensionRequest::query()->create([
                'contract_id' => $contract->id,
                'current_end_date' => $contract->end_date,
                'requested_end_date' => $definition['requested_end_date'],
                'reason' => 'Khách muốn tiếp tục thuê thêm 12 tháng.',
                'status' => ContractExtensionRequest::STATUS_PENDING,
            ]);
            $this->seedAlert($contract, 'extension_request', "extension-request:{$request->id}", 'Yêu cầu gia hạn chờ xử lý', $request);
            $this->seedClientExpiryNotification($contract);

            return;
        }

        $type = $definition['key'] === 'end_of_term'
            ? ContractTerminationRequest::TYPE_END_OF_TERM
            : ContractTerminationRequest::TYPE_EARLY_TERMINATION;
        $request = ContractTerminationRequest::query()->create([
            'contract_id' => $contract->id,
            'tenant_id' => $tenant->id,
            'requested_end_date' => $definition['requested_end_date'],
            'reason' => $type === ContractTerminationRequest::TYPE_END_OF_TERM
                ? 'Hợp đồng hết hạn hôm nay, khách đăng ký bàn giao và trả phòng.'
                : 'Khách cần chuyển nơi ở và xin chấm dứt hợp đồng trước hạn.',
            'request_type' => $type,
            'status' => ContractTerminationRequest::STATUS_PENDING,
        ]);
        $this->seedAlert(
            $contract,
            'termination_request',
            "termination-request:{$request->id}",
            $type === ContractTerminationRequest::TYPE_END_OF_TERM
                ? 'Yêu cầu trả phòng đúng hạn chờ xử lý'
                : 'Yêu cầu chấm dứt trước hạn chờ xử lý',
            $request,
        );
    }

    private function seedAlert(Contract $contract, string $type, string $dedupeKey, string $title, object $reference): void
    {
        ContractLifecycleAlert::query()->create([
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'type' => $type,
            'dedupe_key' => $dedupeKey,
            'title' => $title,
            'message' => 'Dữ liệu kiểm thử được tạo bởi EndOfContractTestSeeder.',
            'metadata' => [
                'scenario' => $contract->note,
                'reference_type' => class_basename($reference),
                'reference_id' => $reference->getKey(),
            ],
            'detected_at' => now(),
        ]);
    }

    private function seedClientExpiryNotification(Contract $contract): void
    {
        $user = $contract->tenant()->with('user')->first()?->user;
        if (! $user) {
            return;
        }

        $user->notifications()->get()
            ->filter(fn ($notification) => ($notification->data['type'] ?? null) === 'contract_expiring'
                && (int) ($notification->data['contract_id'] ?? 0) === $contract->id)
            ->each->delete();

        $user->notify(new ClientPortalNotification(
            'contract_expiring',
            'Hợp đồng còn 7 ngày',
            'Hợp đồng '.$contract->contract_code.' sẽ hết hạn ngày '.$contract->end_date->format('d/m/Y').'. Yêu cầu gia hạn của bạn đang chờ ban quản lý xử lý.',
            'contract',
            [
                'contract_id' => $contract->id,
                'contract_code' => $contract->contract_code,
            ],
        ));
    }
}

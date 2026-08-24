<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractStatusHistory;
use App\Models\ContractTenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\Vehicle;
use App\Services\ContractLifecycleService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $clientRole;

    private ContractLifecycleService $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-11 10:00:00');
        $this->withoutVite();
        Storage::fake('local');
        $adminRole = Role::create(['role_name' => 'Admin']);
        $this->clientRole = Role::create(['role_name' => 'User']);
        $this->admin = $this->user($adminRole, 'contract-admin@example.test');
        $this->lifecycle = app(ContractLifecycleService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_contract_list_exposes_extend_and_end_actions_instead_of_sidebar_entries(): void
    {
        $index = $this->actingAs($this->admin)->get(route('admin.contracts.index'));

        $index->assertOk()
            ->assertSee('href="'.route('admin.contracts.extend.list').'"', false)
            ->assertSee('href="'.route('admin.contracts.end.list').'"', false)
            ->assertSee('href="'.route('admin.contracts.create').'"', false);

        $sidebarOutsideContractList = $this->get(route('admin.contracts.create'));
        $sidebarOutsideContractList->assertOk()
            ->assertDontSee('href="'.route('admin.contracts.extend.list').'"', false)
            ->assertDontSee('href="'.route('admin.contracts.end.list').'"', false);
    }

    public function test_contract_list_filters_immediately_by_keyword_and_status_without_filter_buttons(): void
    {
        $draft = $this->draft(0, [], 'live-draft');
        $active = $this->draft(0, [], 'live-active');
        $active->forceFill(['status' => Contract::STATUS_ACTIVE])->save();

        $page = $this->actingAs($this->admin)->get(route('admin.contracts.index'));
        $page->assertOk()
            ->assertSee('data-contract-filter', false)
            ->assertSee('data-contract-search', false)
            ->assertSee('data-contract-status', false)
            ->assertSee('data-contract-results', false)
            ->assertDontSee('>Lọc</button>', false)
            ->assertDontSee('>Làm mới</a>', false);

        $this->get(route('admin.contracts.index', [
            'keyword' => $draft->contract_code,
            'status' => Contract::STATUS_DRAFT,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee($draft->contract_code)
            ->assertDontSee($active->contract_code)
            ->assertDontSee('data-contract-filter', false);

        $this->get(route('admin.contracts.index', ['status' => Contract::STATUS_ACTIVE]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()
            ->assertSee($active->contract_code)
            ->assertDontSee($draft->contract_code);
    }

    public function test_draft_detail_uses_consistent_review_layout_and_keeps_lifecycle_actions(): void
    {
        $contract = $this->draft();

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertDontSee('Rà soát thông tin trước khi phát hành cho khách ký.')
            ->assertSee('Thông tin hợp đồng')
            ->assertSee('Người đại diện và người thuê')
            ->assertSee('Tài chính dự kiến')
            ->assertSee('Dịch vụ chung bắt buộc')
            ->assertSee('Phát hành bản nháp')
            ->assertSee('href="'.route('admin.contracts.edit', $contract).'"', false)
            ->assertSee('action="'.route('admin.contracts.submit-for-signature', $contract).'"', false)
            ->assertSee('action="'.route('admin.contracts.cancel', $contract).'"', false)
            ->assertDontSee('Backend kiểm tra lại toàn bộ điều kiện');
    }

    public function test_contract_form_hides_mandatory_service_and_vehicle_registration_fields(): void
    {
        $room = $this->room('SERVICE-FORM');
        $desk = Amenity::create([
            'name' => 'Bàn làm việc', 'description' => 'Bàn được bàn giao cùng phòng',
            'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true, 'is_active' => true,
        ]);
        $room->amenities()->attach($desk->id, [
            'quantity' => 2, 'condition' => 'normal', 'note' => null,
        ]);

        $this->actingAs($this->admin)->get(route('admin.contracts.create', ['room_id' => $room->id]))
            ->assertOk()
            ->assertDontSee('Tiện nghi mặc định đã bao gồm')
            ->assertDontSee('Dịch vụ đăng ký tính phí')
            ->assertDontSee('name="service_enabled"', false)
            ->assertDontSee('name="parking_enabled"', false)
            ->assertDontSee('name="parking_vehicle_type"', false)
            ->assertDontSee('name="parking_quantity"', false)
            ->assertSee('data-identity-preview-input', false)
            ->assertSee('representative-identity-front-preview')
            ->assertSee('representative-identity-back-preview')
            ->assertDontSee('Người đại diện luôn là một người thuê trực tiếp và được tính vào sức chứa của phòng.')
            ->assertDontSee('Mọi người được thêm vào hợp đồng phải đủ 18 tuổi và có đầy đủ thông tin CCCD.')
            ->assertDontSee('Trước khi nhận phòng: đóng cọc một tháng.')
            ->assertDontSee('Trẻ dưới 14 tuổi')
            ->assertDontSee('name="internet_enabled"', false)
            ->assertDontSee('Internet: 100.000đ/phòng/tháng')
            ->assertDontSee('Dịch vụ chung: 50.000đ/tháng')
            ->assertSee('data-contract-services', false)
            ->assertSee('data-room-inventory="'.$room->id.'"', false)
            ->assertSee('Tài sản bàn giao của phòng')
            ->assertSee('Bàn làm việc × 2')
            ->assertSee('Sử dụng bình thường');
    }

    public function test_person_under_eighteen_cannot_be_added_to_contract(): void
    {
        $room = $this->room('UNDERAGE-TENANT');
        $tenant = $this->tenant('child-member');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'members' => [[
                'full_name' => 'Người chưa đủ tuổi',
                'date_of_birth' => now()->subYears(18)->addDay()->toDateString(),
                'identity_number' => '012345678901',
                'phone' => null,
                ...$this->memberIdentityImages('under-eighteen', now()->subYears(18)->addDay()->toDateString()),
            ]],
        ]))->assertSessionHasErrors('members.0.date_of_birth');

        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_person_is_eligible_on_their_eighteenth_birthday(): void
    {
        $room = $this->room('BOUNDARY-TENANT');
        $tenant = $this->tenant('teen-member');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'members' => [[
                'full_name' => 'Người vừa đủ tuổi',
                'date_of_birth' => now()->subYears(18)->toDateString(),
                'identity_number' => '012345678902',
                ...$this->memberIdentityImages('exactly-eighteen', now()->subYears(18)->toDateString()),
            ]],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contract_tenants', [
            'full_name' => 'Người vừa đủ tuổi',
        ]);
    }

    public function test_representative_under_eighteen_is_not_eligible_for_a_contract(): void
    {
        $room = $this->room('UNDERAGE-REPRESENTATIVE');
        $tenant = $this->tenant('underage-representative');
        $tenant->update(['date_of_birth' => now()->subYears(18)->addDay()->toDateString()]);

        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertDontSee($tenant->cccd);

        $this->post(route('admin.contracts.store'), $this->payload($room, $tenant))
            ->assertSessionHasErrors('representative.date_of_birth');

        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_existing_offline_tenant_is_reused_for_contract_member_by_cccd(): void
    {
        $room = $this->room('REUSE-MEMBER-TENANT');
        $representative = $this->tenant('reuse-member-representative');
        $member = Tenant::create([
            'user_id' => null,
            'full_name' => 'Hồ sơ thành viên có sẵn',
            'date_of_birth' => '1992-03-04',
            'cccd' => '012345678955',
            'phone' => '0988123456',
        ]);

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $representative, [
            'members' => [[
                ...$this->memberIdentityImages('reuse-existing-member', '1992-03-04'),
                'full_name' => 'Tên thành viên cập nhật',
                'date_of_birth' => '1992-03-04',
                'identity_number' => $member->cccd,
                'phone' => $member->phone,
            ]],
        ]))->assertSessionHasNoErrors();

        $member = Contract::sole()->members()->where('role', ContractTenant::ROLE_TENANT)->sole();
        $this->assertSame($member->id, $member->tenant_id);
        $this->assertNull($member->fresh()->user_id);
        $this->assertSame('Tên thành viên cập nhật', $member->fresh()->full_name);
    }

    public function test_contract_member_cannot_be_persisted_without_tenant_profile(): void
    {
        $contract = $this->draft(0, [], 'required-member-tenant');

        try {
            ContractTenant::query()->create([
                'contract_id' => $contract->id,
                'tenant_id' => null,
                'role' => ContractTenant::ROLE_TENANT,
                'full_name' => 'Bản ghi không có hồ sơ',
                'date_of_birth' => '1990-01-01',
                'identity_number' => '012345678977',
                'phone' => '0988777666',
                'status' => ContractTenant::STATUS_PENDING,
            ]);
            $this->fail('Cơ sở dữ liệu phải từ chối thành viên không có tenant_id.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('contract_tenants', [
                'identity_number' => '012345678977',
            ]);
        }
    }

    public function test_parking_data_is_ignored_when_parking_checkbox_is_not_selected(): void
    {
        $room = $this->room('NO-PARKING');
        $tenant = $this->tenant('no-parking');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'parking_vehicle_type' => Contract::PARKING_CAR,
            'parking_quantity' => 5,
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertNull($contract->parking_vehicle_type);
        $this->assertSame(0, $contract->parking_quantity);
    }

    public function test_non_draft_detail_uses_consistent_layout_and_localizes_lifecycle_history(): void
    {
        $contract = $this->draft();
        $this->lifecycle->submitForSignature($contract, $this->admin, 'Gửi khách xác nhận');

        $this->assertSame(
            now()->addDays(Contract::SIGNATURE_DEADLINE_DAYS)->toDateTimeString(),
            $contract->fresh()->signature_due_at->toDateTimeString()
        );

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Chi tiết hợp đồng')
            ->assertSee('Xác nhận ký')
            ->assertSee('Thời gian ký')
            ->assertSee('id="edit-contract-dialog"', false)
            ->assertSee('id="cancel-contract-dialog"', false)
            ->assertSee('action="'.route('admin.contracts.return-to-draft', $contract).'"', false)
            ->assertDontSee('Trả lại bản nháp')
            ->assertSee('In hợp đồng')
            ->assertSeeInOrder(['Bản nháp', 'Chờ ký'])
            ->assertSee('Tạo bản nháp')
            ->assertSee('Gửi chờ ký')
            ->assertDontSee('create_draft')
            ->assertDontSee('submit_for_signature')
            ->assertDontSee('pending_signature');

        $this->post(route('admin.contracts.return-to-draft', $contract), [
            'reason' => 'Điều chỉnh thông tin người thuê.',
            'edit_after_return' => 1,
        ])->assertRedirect(route('admin.contracts.edit', $contract))->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame(Contract::STATUS_DRAFT, $contract->status);
        $this->assertNull($contract->signature_due_at);
        $this->assertSame(0, $contract->handoverItems()->count());
    }

    public function test_pending_deposit_detail_keeps_invoice_and_collection_actions_on_contract_page(): void
    {
        $contract = $this->draft(3000000, [], 'pending-deposit-layout');
        $this->sign($contract);

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Thu tiền cọc')
            ->assertSee('Phát hành hóa đơn cọc')
            ->assertSee('action="'.route('admin.contracts.deposit-invoice.issue', $contract).'"', false)
            ->assertDontSee('Bước tiếp theo')
            ->assertDontSee('Ghi nhận thanh toán');

        $this->post(route('admin.contracts.deposit-invoice.issue', $contract))->assertRedirect();
        $invoice = $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->sole();

        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Ghi nhận thanh toán')
            ->assertSee('Xác nhận đã thu')
            ->assertSee('action="'.route('admin.invoices.payments.store', $invoice).'"', false)
            ->assertSee('name="return_to_contract" value="1"', false)
            ->assertSee('<option value="cash"', false)
            ->assertSee('<option value="bank_transfer"', false)
            ->assertDontSee('<option value="qr"', false)
            ->assertSee('id="cancel-contract-dialog"', false)
            ->assertDontSee('Phát hành hóa đơn cọc');

        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1000000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_QR,
            'return_to_contract' => 1,
        ])->assertSessionHasErrors('payment_method');

        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1000000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'return_to_contract' => 1,
        ])->assertSessionHasErrors('transaction_code');

        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1000000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_CASH,
            'return_to_contract' => 1,
        ])->assertRedirect(route('admin.contracts.show', $contract))->assertSessionHasNoErrors();

        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('1.000.000đ')
            ->assertSee('2.000.000đ');
    }

    public function test_awaiting_move_in_detail_prioritizes_check_in_and_moves_secondary_actions_to_dialogs(): void
    {
        $contract = $this->awaiting([], 'awaiting-move-in-layout');

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Xác nhận nhận phòng')
            ->assertSee('Khách đã xác nhận thông tin')
            ->assertSee('name="actual_move_in_at"', false)
            ->assertSee('name="handover_electricity"', false)
            ->assertSee('name="handover_water"', false)
            ->assertSee('Đã đối chiếu chỉ số và tài sản bàn giao')
            ->assertSee('id="extend-move-in-dialog"', false)
            ->assertSee('id="cancel-contract-dialog"', false)
            ->assertSee('action="'.route('admin.contracts.check-in', $contract).'"', false)
            ->assertSee('action="'.route('admin.contracts.extend-move-in-deadline', $contract).'"', false)
            ->assertSee('action="'.route('admin.contracts.cancel', $contract).'"', false)
            ->assertSee('Tài sản bàn giao')
            ->assertSee('Người nhận phòng')
            ->assertDontSee('Bước tiếp theo');
    }

    public function test_create_only_writes_draft_without_signing_occupying_reading_or_invoice(): void
    {
        $room = $this->room('DRAFT');
        $tenant = $this->tenant('draft');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'internet_enabled' => 1,
            'service_enabled' => 1,
            'parking_enabled' => 1,
            'parking_vehicle_type' => Contract::PARKING_MOTORCYCLE,
            'parking_quantity' => 1,
            'representative' => [
                'full_name' => 'Người đại diện đã bổ sung',
                'phone' => '0911222333',
                'cccd' => '079123456789',
                'date_of_birth' => '1995-05-20',
                'gender' => 'female',
                'cccd_issue_date' => '2020-05-20',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'address' => '123 Đường kiểm thử',
            ],
        ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame(Contract::STATUS_DRAFT, $contract->status);
        $this->assertNull($contract->signed_at);
        $this->assertNull($contract->actual_move_in_at);
        $this->assertSame('2027-09-01', $contract->end_date->toDateString());
        $this->assertSame('2026-10-01 23:59:59', $contract->reservation_expires_at->toDateTimeString());
        $this->assertNull($contract->move_in_terms_confirmed_by);
        $this->assertNull($contract->move_in_terms_confirmed_at);
        $this->assertNull($contract->signature_due_at);
        $this->assertTrue($contract->internet_enabled);
        $this->assertTrue($contract->service_enabled);
        $this->assertNull($contract->parking_vehicle_type);
        $this->assertSame(0, $contract->parking_quantity);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
        $this->assertSame(0, $room->fresh()->current_people);
        $this->assertDatabaseCount('utility_readings', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id, 'full_name' => 'Người đại diện đã bổ sung',
            'phone' => '0911222333', 'cccd' => '079123456789', 'address' => '123 Đường kiểm thử',
        ]);
        $this->assertDatabaseHas('contract_tenants', [
            'contract_id' => $contract->id, 'role' => ContractTenant::ROLE_REPRESENTATIVE,
            'full_name' => 'Người đại diện đã bổ sung', 'identity_number' => '079123456789',
            'address' => '123 Đường kiểm thử',
        ]);
        $this->assertDatabaseHas('contract_status_histories', [
            'contract_id' => $contract->id, 'from_status' => null, 'to_status' => Contract::STATUS_DRAFT,
            'action' => 'create_draft', 'performed_by' => $this->admin->id,
        ]);
    }

    public function test_submitting_for_signature_snapshots_room_inventory_and_later_room_edits_do_not_change_it(): void
    {
        $room = $this->room('SNAPSHOT');
        $tenant = $this->tenant('snapshot');
        $bed = Amenity::create([
            'name' => 'Giường snapshot', 'description' => 'Giường gỗ',
            'is_quantifiable' => true, 'is_active' => true,
        ]);
        $room->amenities()->attach($bed->id, [
            'quantity' => 2, 'condition' => 'normal', 'note' => 'Không trầy xước',
        ]);
        $contract = $this->lifecycle->createDraft([
            'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'start_date' => '2026-08-11', 'end_date' => '2027-08-11',
            'scheduled_move_in_date' => '2026-08-11', 'reservation_expires_at' => '2026-08-12 18:00:00',
            'parking_quantity' => 1,
            'internet_enabled' => true, 'service_enabled' => false,
        ], $this->admin);
        $this->assertTrue($contract->service_enabled);
        $this->assertSame(0, $contract->parking_quantity);

        $this->lifecycle->submitForSignature($contract, $this->admin);
        $this->assertDatabaseHas('contract_handover_items', [
            'contract_id' => $contract->id, 'amenity_id' => $bed->id, 'name' => 'Giường snapshot',
            'quantity' => 2, 'condition' => 'normal', 'note' => 'Không trầy xước',
        ]);

        $room->amenities()->updateExistingPivot($bed->id, [
            'quantity' => 1, 'condition' => 'damaged', 'note' => 'Đã thay đổi trên phòng',
        ]);
        $snapshot = $contract->handoverItems()->sole();
        $this->assertSame(2, $snapshot->quantity);
        $this->assertSame('normal', $snapshot->condition);
        $this->assertSame('Không trầy xước', $snapshot->note);
    }

    public function test_client_can_view_and_confirm_own_services_and_handover_inventory_only(): void
    {
        Setting::currentOrCreate(['internet_fee' => 100000]);
        $room = $this->room('CLIENT-HANDOVER');
        $tenant = $this->tenant('client-handover');
        $otherTenant = $this->tenant('client-handover-other');
        $desk = Amenity::create([
            'name' => 'Bàn học bàn giao', 'description' => 'Bàn có ngăn kéo',
            'is_quantifiable' => true, 'is_active' => true,
        ]);
        $room->amenities()->attach($desk->id, [
            'quantity' => 1, 'condition' => 'damaged', 'note' => 'Xước nhẹ cạnh bàn',
        ]);
        $contract = $this->lifecycle->createDraft([
            'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'start_date' => '2026-08-11', 'end_date' => '2027-08-11',
            'scheduled_move_in_date' => '2026-08-11', 'reservation_expires_at' => '2026-08-12 18:00:00',
            'parking_quantity' => 2,
            'internet_enabled' => true, 'service_enabled' => false,
        ], $this->admin);
        $tenant->vehicles()->create([
            'vehicle_type' => 'motorcycle',
            'vehicle_name' => 'Honda Vision',
            'license_plate' => '59A1-12345',
            'status' => Vehicle::STATUS_APPROVED,
        ]);
        $this->lifecycle->submitForSignature($contract, $this->admin);

        $this->actingAs($tenant->user)->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Xác nhận thông tin nhận phòng')
            ->assertSee('Tôi đã kiểm tra thông tin dịch vụ và tài sản trong phòng.')
            ->assertSee('Xác nhận thông tin')
            ->assertSee('action="'.route('client.contracts.move-in-details.confirm', $contract).'"', false)
            ->assertDontSee('Xác nhận biên bản nhận phòng')
            ->assertSee('Thông tin nhận phòng')
            ->assertSee('Internet bắt buộc')
            ->assertSee('100.000đ/phòng/tháng')
            ->assertDontSee('Đã bao gồm, không tính phí riêng')
            ->assertSee('Dịch vụ chung bắt buộc')
            ->assertSee('59A1-12345')
            ->assertSee('Quản lý phương tiện')
            ->assertSee('Bàn học bàn giao')
            ->assertSee('Có hư hỏng')
            ->assertSee('Xước nhẹ cạnh bàn');

        $this->actingAs($otherTenant->user)->post(route('client.contracts.move-in-details.confirm', $contract), [
            'confirmation' => 1,
        ])->assertNotFound();
        $this->assertNull($contract->fresh()->move_in_details_confirmed_at);

        $this->actingAs($tenant->user)->post(route('client.contracts.move-in-details.confirm', $contract), [
            'confirmation' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($tenant->user_id, $contract->fresh()->move_in_details_confirmed_by);
        $this->assertDatabaseHas('contract_status_histories', [
            'contract_id' => $contract->id, 'action' => 'confirm_move_in_details',
            'performed_by' => $tenant->user_id,
        ]);
    }

    public function test_check_in_is_blocked_until_client_confirms_move_in_details(): void
    {
        $contract = $this->draft(0, [], 'missing-client-confirmation');
        $this->lifecycle->submitForSignature($contract, $this->admin);
        $this->lifecycle->markAsSigned($contract, $this->admin, now());
        $this->payDeposit($contract);

        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('move_in_details_confirmed');
        $this->assertNull($contract->fresh()->actual_move_in_at);

        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
    }

    public function test_draft_records_representative_and_named_members_without_requiring_accounts(): void
    {
        $room = $this->room('MEMBERS');
        $representative = $this->tenant('representative');
        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee('Người đại diện thuê')
            ->assertDontSee('Thành viên không bắt buộc có tài khoản đăng nhập.')
            ->assertSee('Ngày bắt đầu thời hạn thuê')
            ->assertSee('Hạn cuối phải nhận phòng')
            ->assertSee('name="contract_duration"', false)
            ->assertSee('tối thiểu 12 tháng')
            ->assertDontSee('Thông tin tài khoản được điền sẵn')
            ->assertDontSee('Không chọn nếu người đứng tên thuê phòng cho người khác')
            ->assertDontSee('name="signature_due_at"', false)
            ->assertSee('name="reservation_expires_at"', false)
            ->assertDontSee('name="move_in_terms_confirmed"', false)
            ->assertDontSee('name="deposit_due_at"', false);

        $this->post(route('admin.contracts.store'), $this->payload($room, $representative, [
            'number_of_people' => 20,
            'members' => [[
                'full_name' => 'Người thuê thành viên A', 'identity_number' => '012345678901',
                'phone' => '0901234567', ...$this->memberIdentityImages('member-a'),
            ]],
        ]))->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame($representative->id, $contract->representative_tenant_id);
        $this->assertSame(2, $contract->number_of_people);
        $this->assertDatabaseHas('contract_tenants', [
            'contract_id' => $contract->id, 'tenant_id' => $representative->id,
            'role' => ContractTenant::ROLE_REPRESENTATIVE, 'status' => ContractTenant::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('contract_tenants', [
            'contract_id' => $contract->id, 'full_name' => 'Người thuê thành viên A',
            'role' => ContractTenant::ROLE_TENANT, 'status' => ContractTenant::STATUS_APPROVED,
        ]);
        $memberTenant = Tenant::query()->where('cccd', '012345678901')->sole();
        $this->assertNull($memberTenant->user_id);
        $this->assertSame('other', $memberTenant->gender);
        $this->assertSame('2020-01-01', $memberTenant->cccd_issue_date?->toDateString());
        $this->assertSame('Cục Cảnh sát QLHC về TTXH', $memberTenant->cccd_issue_place);
        $this->assertSame('Địa chỉ thường trú của người thuê', $memberTenant->address);
        $this->assertSame($memberTenant->id, $contract->members()->where('full_name', 'Người thuê thành viên A')->value('tenant_id'));

        $member = $contract->members()->current()->where('role', ContractTenant::ROLE_TENANT)->sole();
        $representativeMember = $contract->members()->current()->where('role', ContractTenant::ROLE_REPRESENTATIVE)->sole();
        $representativeFront = $representativeMember->identity_front_path;
        $representativeBack = $representativeMember->identity_back_path;
        $unchangedPayload = $this->payload($room, $representative, [
            'members' => [[
                'id' => $member->id,
                'full_name' => $member->full_name,
                'date_of_birth' => $member->date_of_birth->toDateString(),
                'gender' => $memberTenant->gender,
                'identity_number' => $member->identity_number,
                'cccd_issue_date' => $memberTenant->cccd_issue_date->toDateString(),
                'cccd_issue_place' => $memberTenant->cccd_issue_place,
                'phone' => $member->phone,
                'email' => $memberTenant->email,
                'address' => $member->address,
            ]],
        ]);
        unset($unchangedPayload['representative']['identity_front'], $unchangedPayload['representative']['identity_back']);

        $this->put(route('admin.contracts.update', $contract), $unchangedPayload)
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $contract->members()->count());
        $this->assertSame(2, $contract->currentMembers()->count());
        $this->assertSame($representativeFront, $representativeMember->fresh()->identity_front_path);
        $this->assertSame($representativeBack, $representativeMember->fresh()->identity_back_path);

        $this->get(route('admin.contracts.edit', $contract))
            ->assertOk()
            ->assertSee('Đã lưu')
            ->assertSee('Thay ảnh');

        $this->put(route('admin.contracts.update', $contract), $this->payload($room, $representative, [
            'members' => [[
                'full_name' => 'Người thuê thành viên B', 'identity_number' => '012345678902',
                'phone' => '0901234568', ...$this->memberIdentityImages('member-b'),
            ]],
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contract_tenants', [
            'contract_id' => $contract->id, 'full_name' => 'Người thuê thành viên A',
            'status' => ContractTenant::STATUS_WITHDRAWN,
        ]);
        $this->assertDatabaseHas('contract_tenants', [
            'contract_id' => $contract->id, 'full_name' => 'Người thuê thành viên B',
            'role' => ContractTenant::ROLE_TENANT, 'status' => ContractTenant::STATUS_APPROVED,
        ]);
        $this->assertDatabaseCount('contract_tenant_histories', 8);
        $this->assertSame(2, $contract->fresh()->number_of_people);

        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertDontSee('Người thuê thành viên A')
            ->assertSee('Người thuê thành viên B');
    }

    public function test_contract_dates_are_derived_from_start_and_selected_duration(): void
    {
        $room = $this->room('DERIVED-DATES');
        $tenant = $this->tenant('derived-dates');
        $payload = $this->payload($room, $tenant, [
            'contract_duration' => '24',
            'end_date' => '2099-12-31',
            'reservation_expires_at' => '2099-12-30',
            'scheduled_move_in_date' => '2026-09-11',
        ]);
        unset($payload['move_in_terms_confirmed']);
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame('2028-09-01', $contract->end_date->toDateString());
        $this->assertSame('24', $contract->rental_duration_option);
        $this->assertSame('2026-10-01 23:59:59', $contract->reservation_expires_at->toDateTimeString());
        $this->assertNull($contract->signature_due_at);

        $otherRoom = $this->room('MOVE-IN-OUTSIDE-WINDOW');
        $otherTenant = $this->tenant('move-in-outside-window');
        $this->post(route('admin.contracts.store'), $this->payload($otherRoom, $otherTenant, [
            'contract_duration' => '12',
            'scheduled_move_in_date' => '2026-10-02',
        ]))->assertSessionHasErrors('scheduled_move_in_date');
        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_occupied_room_only_accepts_a_contract_starting_after_the_current_lease_ends(): void
    {
        $current = $this->active([
            'start_date' => '2026-08-11',
            'scheduled_move_in_date' => '2026-08-11',
            'reservation_expires_at' => '2026-08-12 18:00:00',
            'end_date' => '2027-08-11',
        ], 'current-room-guest');
        $current->forceFill(['end_date' => '2026-08-31'])->save();
        $newTenant = $this->tenant('future-room-guest');

        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee('data-available-from="2026-09-01"', false)
            ->assertSee('có thể thuê từ 01/09/2026');

        $this->post(route('admin.contracts.store'), $this->payload($current->room, $newTenant, [
            'contract_duration' => '12',
            'start_date' => '2026-08-31',
            'scheduled_move_in_date' => '2026-08-31',
        ]))->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('contracts', 1);

        $this->post(route('admin.contracts.store'), $this->payload($current->room, $newTenant, [
            'contract_duration' => '12',
            'start_date' => '2026-09-01',
            'scheduled_move_in_date' => '2026-09-01',
        ]))->assertSessionHasNoErrors();

        $futureDraft = Contract::query()->whereKeyNot($current->id)->sole();
        $this->assertSame(Contract::STATUS_DRAFT, $futureDraft->status);
        $this->assertSame('2026-09-01', $futureDraft->start_date->toDateString());
    }

    public function test_move_in_dates_are_limited_to_one_month_from_contract_start(): void
    {
        $room = $this->room('MOVE-IN-TERMS');
        $tenant = $this->tenant('move-in-terms');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'scheduled_move_in_date' => '2026-10-02',
        ]))->assertSessionHasErrors('scheduled_move_in_date');
        $this->assertDatabaseCount('contracts', 0);

        $this->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'scheduled_move_in_date' => '2026-10-01',
            'reservation_expires_at' => '2026-10-01',
        ]))->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame('2026-10-01', $contract->scheduled_move_in_date->toDateString());
        $this->assertSame('2026-10-01 23:59:59', $contract->reservation_expires_at->toDateTimeString());
    }

    public function test_contract_accepts_duration_longer_than_one_year(): void
    {
        $room = $this->room('LONG-TERM');
        $tenant = $this->tenant('long-term');
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'contract_duration' => 18,
        ]))->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame('2028-03-01', $contract->end_date->toDateString());
        $this->assertSame('18', $contract->rental_duration_option);
        $this->get(route('admin.contracts.edit', $contract))->assertOk()->assertSee('value="18"', false);
    }

    public function test_representative_always_occupies_a_slot_and_two_identity_sides_are_private(): void
    {
        $room = $this->room('RENT-FOR-OTHER');
        $tenant = $this->tenant('non-resident-lessee');
        $payload = $this->payload($room, $tenant, [
            'deposit_amount' => 0,
            'members' => [[
                'full_name' => 'Người thuê thực tế', 'identity_number' => '012345678955',
                ...$this->memberIdentityImages('actual-resident'),
            ]],
        ]);
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $representative = $contract->members()->where('role', ContractTenant::ROLE_REPRESENTATIVE)->sole();
        $resident = $contract->members()->where('role', ContractTenant::ROLE_TENANT)->sole();
        $this->assertSame(2, $contract->number_of_people);
        $this->assertSame(ContractTenant::STATUS_APPROVED, $representative->status);
        $this->assertSame(ContractTenant::STATUS_APPROVED, $resident->status);
        Storage::disk('local')->assertExists([$representative->identity_front_path, $representative->identity_back_path]);

        $this->actingAs($tenant->user)->get(route('admin.contract-tenants.identity-document', [$representative, 'front']))
            ->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.contract-tenants.identity-document', [$representative, 'front']))
            ->assertOk();

        $this->sign($contract);
        $this->payDeposit($contract);
        $this->lifecycle->checkIn($contract, $this->admin, $this->checkInPayload([
            'schedule_variance_reason' => 'Người thuê nhận phòng sớm theo thỏa thuận.',
        ]));
        $this->assertSame(2, $room->fresh()->current_people);
    }

    public function test_contract_requires_both_identity_card_images_without_partial_write(): void
    {
        $room = $this->room('IDENTITY-PAIR');
        $tenant = $this->tenant('identity-pair');
        $payload = $this->payload($room, $tenant);
        unset($payload['representative']['identity_back']);

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasErrors('representative.identity_back');
        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('contract_tenants', 0);
    }

    public function test_non_contiguous_member_index_keeps_text_and_identity_files_on_the_same_member(): void
    {
        $room = $this->room('MEMBER-INDEX-GAP');
        $tenant = $this->tenant('member-index-gap');
        $payload = $this->payload($room, $tenant, [
            'members' => [3 => [
                'full_name' => 'Người thuê sau khi thêm lại',
                'identity_number' => '012345678966',
                'phone' => '0901234569',
                ...$this->memberIdentityImages('member-index-gap'),
            ]],
        ]);

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $member = Contract::sole()->members()
            ->where('role', ContractTenant::ROLE_TENANT)
            ->sole();
        $this->assertSame('Người thuê sau khi thêm lại', $member->full_name);
        $this->assertSame('012345678966', $member->identity_number);
        Storage::disk('local')->assertExists([
            $member->identity_front_path,
            $member->identity_back_path,
        ]);
    }

    public function test_room_capacity_rejects_the_fifth_resident_for_a_four_person_room(): void
    {
        $room = $this->room('CAPACITY-FOUR', ['max_people' => 4]);
        $tenant = $this->tenant('capacity-four');
        $members = collect(range(1, 4))->map(fn (int $number): array => [
            'full_name' => 'Người thuê '.$number,
            'identity_number' => sprintf('012345678%03d', $number),
            ...$this->memberIdentityImages('capacity-'.$number),
        ])->all();
        $payload = $this->payload($room, $tenant, ['members' => $members]);
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasErrors('number_of_people');
        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('contract_tenants', 0);
    }

    public function test_representative_is_always_counted_when_room_capacity_is_checked(): void
    {
        $room = $this->room('CAPACITY-REPRESENTATIVE', ['max_people' => 2]);
        $tenant = $this->tenant('capacity-representative');
        $members = collect(range(1, 2))->map(fn (int $number): array => [
            'full_name' => 'Người thuê '.$number,
            'identity_number' => sprintf('012345679%03d', $number),
            ...$this->memberIdentityImages('capacity-representative-'.$number),
        ])->all();

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'members' => $members,
        ]))->assertSessionHasErrors('number_of_people');

        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('contract_tenants', 0);
    }

    public function test_ajax_draft_validation_returns_field_specific_vietnamese_errors_without_redirecting(): void
    {
        $room = $this->room('AJAX-VALIDATION');
        $tenant = $this->tenant('ajax-validation');
        $payload = $this->payload($room, $tenant, [
            'members' => [[
                'full_name' => 'Người kiểm thử',
                'date_of_birth' => '1990-01-01',
                'gender' => 'other',
                'identity_number' => '012345678901',
                'cccd_issue_date' => '2020-01-01',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'phone' => '0901234567',
                'address' => 'Địa chỉ kiểm thử',
            ]],
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.contracts.store'), $payload);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'members.0.identity_front',
                'members.0.identity_back',
            ]);
        $this->assertSame('Vui lòng chọn ảnh mặt trước CCCD của người thuê.', $response->json('errors')['members.0.identity_front'][0]);
        $this->assertSame('Vui lòng chọn ảnh mặt sau CCCD của người thuê.', $response->json('errors')['members.0.identity_back'][0]);

        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_create_validates_dates_capacity_maintenance_and_required_schedule_without_partial_writes(): void
    {
        $room = $this->room('INVALID', ['max_people' => 2]);
        $tenant = $this->tenant('invalid');
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'contract_duration' => 'invalid', 'number_of_people' => 3, 'scheduled_move_in_date' => null,
        ]))->assertSessionHasErrors(['contract_duration', 'scheduled_move_in_date']);
        $this->assertDatabaseCount('contracts', 0);

        $room->update(['status' => Room::STATUS_MAINTENANCE]);
        $this->post(route('admin.contracts.store'), $this->payload($room, $tenant))
            ->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_client_declares_member_and_admin_review_is_required_before_check_in(): void
    {
        $contract = $this->awaiting();
        $client = $contract->tenant->user;

        $this->actingAs($client)->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Người chờ duyệt', 'identity_number' => '012345678911',
            'phone' => '0901111111', ...$this->memberIdentityImages('pending-resident'),
        ])->assertSessionHasNoErrors();

        $member = ContractTenant::query()->where('full_name', 'Người chờ duyệt')->sole();
        $this->assertNotNull($member->tenant_id);
        $this->assertNull($member->tenant->user_id);
        $this->assertSame(ContractTenant::STATUS_PENDING, $member->status);
        $this->assertSame(Room::STATUS_AVAILABLE, $contract->room->fresh()->status);
        $this->assertSame(0, $contract->room->fresh()->current_people);

        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('members');
        $this->post(route('admin.contract-tenants.approve', $member))->assertSessionHasNoErrors();
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())->assertSessionHasNoErrors();

        $this->assertSame(ContractTenant::STATUS_CHECKED_IN, $member->fresh()->status);
        $this->assertSame(2, $contract->room->fresh()->current_people);
    }

    public function test_member_account_cannot_manage_the_representatives_contract(): void
    {
        $contract = $this->draft(0, [], 'primary-representative');
        $memberTenant = $this->tenant('contract-member-with-account');
        $member = ContractTenant::query()->create([
            'contract_id' => $contract->id,
            'tenant_id' => $memberTenant->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => $memberTenant->full_name,
            'date_of_birth' => $memberTenant->date_of_birth,
            'identity_number' => $memberTenant->cccd,
            'phone' => $memberTenant->phone,
            'status' => ContractTenant::STATUS_APPROVED,
        ]);

        $this->actingAs($memberTenant->user)
            ->get(route('client.contracts.index'))
            ->assertOk()
            ->assertDontSee($contract->contract_code);
        $this->get(route('client.contracts.show', $contract))->assertNotFound();
        $this->post(route('client.contracts.members.withdraw', [$contract, $member]))->assertNotFound();

        $this->actingAs($contract->tenant->user)
            ->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee($contract->contract_code);
    }

    public function test_client_cannot_declare_person_under_eighteen(): void
    {
        $contract = $this->awaiting();

        $this->actingAs($contract->tenant->user)->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Bé do khách khai báo',
            'date_of_birth' => '2021-08-01',
        ])->assertRedirect()->assertSessionHasErrors([
            'date_of_birth', 'identity_number', 'identity_front', 'identity_back',
        ]);

        $this->assertDatabaseMissing('contract_tenants', ['full_name' => 'Bé do khách khai báo']);
    }

    public function test_approved_member_can_join_and_leave_active_room_without_losing_history(): void
    {
        $contract = $this->active();
        $client = $contract->tenant->user;
        $this->assertSame(1, $contract->room->fresh()->current_people);

        $this->actingAs($client)->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Người vào sau', 'identity_number' => '012345678912',
            ...$this->memberIdentityImages('late-resident'),
        ])->assertSessionHasNoErrors();
        $member = ContractTenant::query()->where('full_name', 'Người vào sau')->sole();
        $this->assertSame(1, $contract->room->fresh()->current_people);

        $this->actingAs($this->admin)->post(route('admin.contract-tenants.approve', $member))
            ->assertSessionHasNoErrors();
        $this->assertSame(ContractTenant::STATUS_CHECKED_IN, $member->fresh()->status);
        $this->assertSame(2, $contract->room->fresh()->current_people);

        $this->post(route('admin.contract-tenants.move-out', $member), [
            'actual_move_out_at' => now()->format('Y-m-d H:i:s'), 'reason' => 'Chuyển nơi ở.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(ContractTenant::STATUS_MOVED_OUT, $member->fresh()->status);
        $this->assertSame(1, $contract->room->fresh()->current_people);
        $this->assertDatabaseHas('contract_tenant_histories', [
            'contract_tenant_id' => $member->id, 'action' => 'tenant_move_out',
        ]);
    }

    public function test_admin_policy_routes_and_mass_assignment_prevent_forged_status_or_deletion(): void
    {
        $contract = $this->draft();
        $client = $contract->tenant->user;
        foreach (['submit-for-signature', 'mark-signed', 'check-in', 'cancel', 'check-out', 'complete-settlement'] as $action) {
            $this->actingAs($client)->post("/admin/contracts/{$contract->id}/{$action}", [])->assertForbidden();
        }
        $contract->update(['status' => Contract::STATUS_ACTIVE, 'signed_at' => now(), 'checked_in_by' => $client->id]);
        $this->assertSame(Contract::STATUS_DRAFT, $contract->fresh()->status);
        $this->assertNull($contract->fresh()->signed_at);
        $this->assertNull($contract->fresh()->checked_in_by);
        $this->actingAs($this->admin)->delete("/admin/contracts/{$contract->id}")->assertStatus(405);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    public function test_signature_state_machine_rejects_skips_future_date_and_is_idempotent(): void
    {
        $contract = $this->draft(2000000);
        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), [])->assertSessionHasErrors();
        $this->post(route('admin.contracts.mark-signed', $contract), ['signed_at' => now()])
            ->assertSessionHasErrors('contract');

        $this->post(route('admin.contracts.submit-for-signature', $contract))->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $contract->fresh()->status);
        $historyCount = ContractStatusHistory::count();
        $this->post(route('admin.contracts.submit-for-signature', $contract))->assertSessionHasNoErrors();
        $this->assertSame($historyCount, ContractStatusHistory::count());

        $this->post(route('admin.contracts.mark-signed', $contract), ['signed_at' => now()->addMinute()])
            ->assertSessionHasErrors('signed_at');
        $this->assertNull($contract->fresh()->signed_at);

        $this->post(route('admin.contracts.mark-signed', $contract), ['signed_at' => now()->subHour()])
            ->assertSessionHasNoErrors();
        $contract->refresh();
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->status);
        $this->assertSame($this->admin->id, $contract->signed_confirmed_by);
        $historyCount = ContractStatusHistory::count();
        $this->post(route('admin.contracts.mark-signed', $contract), ['signed_at' => now()->subHour()]);
        $this->assertSame($historyCount, ContractStatusHistory::count());
    }

    public function test_signed_contract_requires_only_deposit_before_move_in(): void
    {
        $contract = $this->draft(0, [
            'start_date' => '2026-09-01', 'end_date' => '2027-09-01',
            'scheduled_move_in_date' => '2026-09-01', 'reservation_expires_at' => '2026-09-02 18:00:00',
        ]);
        $this->sign($contract);

        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->assertSame('3000000.00', $contract->fresh()->deposit_amount);
        $this->assertDatabaseCount('invoices', 0);
        $this->payDeposit($contract);
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
        $this->assertNull($contract->fresh()->deposit_resolution);
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $contract->id,
            'invoice_type' => Invoice::TYPE_DEPOSIT,
            'room_fee' => 0,
            'total_amount' => 3000000,
        ]);
        $this->assertDatabaseMissing('invoices', [
            'contract_id' => $contract->id,
            'invoice_type' => Invoice::TYPE_FIRST_MONTH_RENT,
        ]);
        $this->assertSame(3000000.0, (float) $contract->invoices()->sum('total_amount'));
        $this->assertSame(Room::STATUS_AVAILABLE, $contract->room->fresh()->status);
    }

    public function test_first_month_rent_is_prorated_by_calendar_days_without_half_month_tiers(): void
    {
        $sevenDays = $this->draft(0, [
            'start_date' => '2027-05-25', 'end_date' => '2028-05-25',
            'scheduled_move_in_date' => '2027-05-25', 'reservation_expires_at' => '2027-05-26 18:00:00',
        ], 'prorated-seven-days');
        $this->sign($sevenDays);

        $this->assertSame(7, $sevenDays->fresh()->first_month_rent_days);
        $this->assertSame(677419.0, $sevenDays->fresh()->calculated_first_month_rent_amount);

        $sixDays = $this->draft(0, [
            'start_date' => '2027-05-26', 'end_date' => '2028-05-26',
            'scheduled_move_in_date' => '2027-05-26', 'reservation_expires_at' => '2027-05-27 18:00:00',
        ], 'prorated-six-days');
        $this->sign($sixDays);
        $this->assertSame(580645.0, $sixDays->fresh()->calculated_first_month_rent_amount);
        $this->assertDatabaseMissing('invoices', ['invoice_type' => Invoice::TYPE_FIRST_MONTH_RENT]);
    }

    public function test_first_month_rent_is_free_when_five_or_fewer_days_remain(): void
    {
        $contract = $this->draft(0, [
            'start_date' => '2027-05-27', 'end_date' => '2028-05-27',
            'scheduled_move_in_date' => '2027-05-27', 'reservation_expires_at' => '2027-05-28 18:00:00',
        ], 'free-five-days');
        $this->sign($contract);
        $depositInvoice = $this->lifecycle->issueDepositInvoice($contract, $this->admin);

        $this->assertSame(5, $contract->fresh()->first_month_rent_days);
        $this->assertSame(0.0, $contract->fresh()->calculated_first_month_rent_amount);
        $this->assertDatabaseMissing('invoices', ['invoice_type' => Invoice::TYPE_FIRST_MONTH_RENT]);

        Payment::query()->forceCreate([
            'invoice_id' => $depositInvoice->id, 'amount_paid' => $depositInvoice->total_amount,
            'payment_date' => today(), 'payment_method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_SUCCESS,
        ]);
        $this->lifecycle->syncDepositState($contract, $this->admin);

        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
    }

    public function test_deposit_deadline_is_calculated_after_signing_and_capped_by_move_in_deadline(): void
    {
        $normal = $this->draft(1000000, [
            'deposit_due_at' => null,
            'reservation_expires_at' => now()->addMonth(),
        ], 'automatic-deposit-deadline');
        $this->lifecycle->submitForSignature($normal, $this->admin);
        $this->lifecycle->markAsSigned($normal, $this->admin, now());
        $this->assertSame(
            now()->addDays(10)->toDateTimeString(),
            $normal->fresh()->deposit_due_at->toDateTimeString()
        );

        $capped = $this->draft(1000000, [
            'deposit_due_at' => null,
            'reservation_expires_at' => now()->addDay(),
        ], 'capped-deposit-deadline');
        $this->lifecycle->submitForSignature($capped, $this->admin);
        $this->lifecycle->markAsSigned($capped, $this->admin, now());
        $this->assertSame(
            now()->addDay()->toDateTimeString(),
            $capped->fresh()->deposit_due_at->toDateTimeString()
        );
    }

    public function test_signature_transition_rolls_back_signed_fields_when_history_write_fails(): void
    {
        $contract = $this->draft(1000000, [], 'signature-rollback');
        $this->lifecycle->submitForSignature($contract, $this->admin);
        ContractStatusHistory::creating(function (ContractStatusHistory $history): void {
            if ($history->action === 'mark_as_signed') {
                throw new RuntimeException('simulated history failure');
            }
        });

        try {
            $this->lifecycle->markAsSigned($contract, $this->admin, now());
            $this->fail('Expected simulated history failure.');
        } catch (RuntimeException) {
            $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $contract->fresh()->status);
            $this->assertNull($contract->fresh()->signed_at);
            $this->assertNull($contract->fresh()->signed_confirmed_by);
            $this->assertDatabaseMissing('contract_status_histories', [
                'contract_id' => $contract->id, 'action' => 'mark_as_signed',
            ]);
        }
    }

    public function test_deposit_invoice_is_unique_and_must_be_fully_paid(): void
    {
        $contract = $this->draft(2000000);
        $this->sign($contract);
        $this->actingAs($this->admin)->post(route('admin.contracts.deposit-invoice.issue', $contract))->assertRedirect();
        $depositInvoice = $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->sole();
        $this->post(route('admin.contracts.deposit-invoice.issue', $contract));
        $this->assertSame(0, $contract->invoices()->where('invoice_type', Invoice::TYPE_FIRST_MONTH_RENT)->count());
        $this->assertSame(1, $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->count());

        Payment::query()->forceCreate(['invoice_id' => $depositInvoice->id, 'amount_paid' => 500000, 'payment_date' => today(), 'payment_method' => 'cash', 'status' => Payment::STATUS_PENDING]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        Payment::query()->forceCreate(['invoice_id' => $depositInvoice->id, 'amount_paid' => 500000, 'payment_date' => today(), 'payment_method' => 'cash', 'status' => Payment::STATUS_FAILED]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $depositInvoice), [
            'amount_paid' => 1250000, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->post(route('admin.invoices.payments.store', $depositInvoice), [
            'amount_paid' => 1750000, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
        $this->post(route('admin.invoices.payments.store', $depositInvoice), [
            'amount_paid' => 1, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasErrors('amount_paid');
    }

    public function test_overlapping_reservations_are_rejected_but_non_overlapping_future_contract_is_allowed(): void
    {
        $room = $this->room('RESERVE');
        $first = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2026-09-01', 'scheduled_move_in_date' => '2026-09-01', 'reservation_expires_at' => '2026-09-02 18:00:00', 'end_date' => '2027-09-01']);
        $this->sign($first);

        $second = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2027-08-01', 'scheduled_move_in_date' => '2027-08-01', 'reservation_expires_at' => '2027-09-01 18:00:00', 'end_date' => '2028-08-01'], 'reserve-second');
        $this->lifecycle->submitForSignature($second, $this->admin);
        $this->expectException(ValidationException::class);
        try {
            $this->lifecycle->markAsSigned($second, $this->admin, now());
        } finally {
            $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $second->fresh()->status);
            $third = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2027-09-02', 'scheduled_move_in_date' => '2027-09-02', 'reservation_expires_at' => '2027-10-02 18:00:00', 'end_date' => '2028-09-02'], 'reserve-third');
            $this->sign($third);
            $this->payDeposit($third);
            $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $third->fresh()->status);
        }
    }

    public function test_check_in_validates_all_guards_and_rolls_back_when_any_write_fails(): void
    {
        $contract = $this->awaiting();
        $room = $contract->room;
        $room->update(['status' => Room::STATUS_MAINTENANCE]);
        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('room_id');
        $this->assertNull($contract->fresh()->actual_move_in_at);
        $this->assertDatabaseCount('utility_readings', 0);

        $room->update(['status' => Room::STATUS_AVAILABLE]);
        UtilityReading::creating(fn () => throw new RuntimeException('simulated write failure'));
        try {
            $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload());
            $this->fail('Expected simulated transaction failure.');
        } catch (RuntimeException) {
            $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
            $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
            $this->assertSame(0, $room->fresh()->current_people);
            $this->assertDatabaseCount('utility_readings', 0);
        }
    }

    public function test_check_in_early_or_late_requires_reason_and_success_is_idempotent(): void
    {
        $contract = $this->awaiting(['start_date' => '2026-08-10', 'scheduled_move_in_date' => '2026-08-10']);
        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('schedule_variance_reason');
        $payload = $this->checkInPayload(['schedule_variance_reason' => 'Khách đến muộn một ngày.']);
        $this->post(route('admin.contracts.check-in', $contract), $payload)->assertSessionHasNoErrors();
        $contract->refresh();
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);
        $this->assertSame($this->admin->id, $contract->checked_in_by);
        $this->assertSame(Room::STATUS_OCCUPIED, $contract->room->fresh()->status);
        $this->assertSame($contract->number_of_people, $contract->room->fresh()->current_people);
        $this->assertSame(1, $contract->utilityReadings()->where('reading_type', 'handover')->count());
        $historyCount = $contract->statusHistories()->count();
        $secondAdmin = $this->user(Role::where('role_name', 'Admin')->sole(), 'second-contract-admin@example.test');
        $this->actingAs($secondAdmin)->post(route('admin.contracts.check-in', $contract), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $contract->utilityReadings()->where('reading_type', 'handover')->count());
        $this->assertSame($historyCount, $contract->statusHistories()->count());
    }

    public function test_room_baseline_is_shown_and_prefills_first_contract_handover(): void
    {
        $contract = $this->awaiting();
        UtilityReading::query()->forceCreate([
            'room_id' => $contract->room_id,
            'contract_id' => null,
            'month' => now()->month,
            'year' => now()->year,
            'record_date' => today(),
            'reading_type' => 'baseline',
            'lifecycle_event_key' => "room:{$contract->room_id}:baseline",
            'electricity_old' => 321,
            'electricity_new' => 321,
            'water_old' => 45,
            'water_new' => 45,
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('name="handover_electricity" value="321"', false)
            ->assertSee('name="handover_water" value="45"', false)
            ->assertSee('Điện 321 · Nước 45');

        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload([
            'handover_electricity' => 321,
            'handover_water' => 45,
            'schedule_variance_reason' => 'Ngày nhận thực tế khác lịch.',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id,
            'reading_type' => 'handover',
            'electricity_new' => 321,
            'water_new' => 45,
        ]);
    }

    public function test_overdue_move_in_scheduler_is_idempotent_and_admin_can_extend_or_cancel(): void
    {
        $contract = $this->awaiting(['start_date' => '2026-08-10', 'scheduled_move_in_date' => '2026-08-10', 'reservation_expires_at' => '2026-08-10 18:00:00']);
        $this->artisan('contracts:process-lifecycle')->assertSuccessful();
        $this->artisan('contracts:process-lifecycle')->assertSuccessful();
        $this->assertSame(1, ContractLifecycleAlert::where('contract_id', $contract->id)->where('type', 'move_in_overdue')->count());
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
        $this->assertDatabaseCount('payments', 1);

        $this->actingAs($this->admin)->post(route('admin.contracts.extend-move-in-deadline', $contract), [
            'reservation_expires_at' => now()->addDays(3), 'reason' => 'Khách xin lùi lịch.',
        ])->assertSessionHasNoErrors();
        $this->assertNotNull(ContractLifecycleAlert::first()->fresh()->resolved_at);

        $this->post(route('admin.contracts.cancel', $contract), ['cancel_reason' => 'Khách không còn nhu cầu.'])
            ->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_CANCELLED, $contract->fresh()->status);
        $this->assertSame('Khách không còn nhu cầu.', $contract->fresh()->cancel_reason);
    }

    public function test_expiry_keeps_room_and_account_active_extension_restores_active_and_old_guest_blocks_new_check_in(): void
    {
        $old = $this->active([
            'start_date' => '2026-08-01',
            'scheduled_move_in_date' => '2026-08-01',
            'reservation_expires_at' => '2026-08-10 18:00:00',
            'end_date' => '2027-08-01',
        ]);
        $old->forceFill(['end_date' => '2026-08-10'])->save();
        $room = $old->room;
        $this->artisan('contracts:process-lifecycle')->assertSuccessful();
        $this->assertSame(Contract::STATUS_EXPIRED, $old->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $room->fresh()->status);
        $this->assertSame($old->number_of_people, $room->fresh()->current_people);
        $this->assertSame(User::STATUS_ACTIVE, $old->tenant->user->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.contracts.extend', $old), [
            'new_end_date' => '2027-08-10', 'reason' => 'Hai bên đồng ý gia hạn.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_ACTIVE, $old->fresh()->status);

        $future = $this->awaiting(['room_id' => $room->id, 'start_date' => '2027-08-11', 'scheduled_move_in_date' => '2027-08-11', 'reservation_expires_at' => '2027-08-12 18:00:00', 'end_date' => '2028-08-11'], 'future-tenant');
        $this->post(route('admin.contracts.check-in', $future), $this->checkInPayload(['schedule_variance_reason' => 'Kiểm tra phòng còn khách cũ.']))
            ->assertSessionHasErrors('room_id');
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $future->fresh()->status);
    }

    public function test_checkout_creates_settling_final_reading_optional_invoice_and_is_idempotent(): void
    {
        $contract = $this->active();
        $payload = [
            'actual_move_out_at' => now(), 'checkout_electricity' => 130, 'checkout_water' => 20,
            'checkout_reason' => 'Khách chuyển nơi làm việc.', 'settlement_amount' => 500000,
            'settlement_description' => 'Bồi thường cửa hỏng',
        ];
        $this->actingAs($this->admin)->post(route('admin.contracts.check-out', $contract), $payload)->assertSessionHasNoErrors();
        $contract->refresh();
        $this->assertSame(Contract::STATUS_SETTLING, $contract->status);
        $this->assertNotNull($contract->actual_move_out_at);
        $this->assertSame(Room::STATUS_AVAILABLE, $contract->room->fresh()->status);
        $this->assertSame(0, $contract->room->fresh()->current_people);
        $this->assertSame(1, $contract->utilityReadings()->where('reading_type', 'checkout')->count());
        $this->assertSame(1, $contract->invoices()->where('invoice_type', Invoice::TYPE_SETTLEMENT)->count());
        $history = $contract->statusHistories()->count();
        $this->post(route('admin.contracts.check-out', $contract), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $contract->utilityReadings()->where('reading_type', 'checkout')->count());
        $this->assertSame($history, $contract->statusHistories()->count());
    }

    public function test_completion_requires_no_debt_and_explicit_deposit_resolution(): void
    {
        $contract = $this->active();
        $contract->forceFill(['deposit_amount' => 1000000, 'deposit_status' => Contract::DEPOSIT_PAID])->save();
        $this->lifecycle->checkOut($contract, $this->admin, [
            'actual_move_out_at' => now(), 'checkout_electricity' => 110, 'checkout_water' => 12,
            'checkout_reason' => 'Kết thúc đúng thỏa thuận.',
        ]);
        $invoice = Invoice::query()->forceCreate([
            'contract_id' => $contract->id, 'room_id' => $contract->room_id, 'invoice_code' => 'DEBT-1',
            'month' => 8, 'year' => 2026, 'invoice_date' => today(), 'due_date' => today(),
            'room_fee' => 100000, 'total_amount' => 100000, 'status' => Invoice::STATUS_UNPAID,
        ]);
        $this->actingAs($this->admin)->post(route('admin.contracts.complete-settlement', $contract), [
            'confirm_complete' => 1,
        ])->assertSessionHasErrors('invoices');
        $this->assertSame(Contract::STATUS_SETTLING, $contract->fresh()->status);

        $this->post(route('admin.contracts.complete-settlement', $contract), [
            'deposit_resolution' => Contract::DEPOSIT_RETAINED,
            'settlement_note' => 'Biên bản BT-01',
            'write_off_outstanding' => 1, 'write_off_reason' => 'Quản lý phê duyệt miễn khoản nhỏ.',
            'confirm_complete' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_COMPLETED, $contract->fresh()->status);
        $this->assertSame(Invoice::STATUS_WRITTEN_OFF, $invoice->fresh()->status);
        $this->assertSame($this->admin->id, $contract->fresh()->completed_by);
        $this->assertSame(Contract::DEPOSIT_RETAINED, $contract->fresh()->deposit_resolution);
        $this->assertNotNull($contract->fresh()->deposit_resolved_at);
    }

    public function test_cancellation_requires_reason_active_cannot_cancel_and_terminal_states_cannot_reopen(): void
    {
        $draft = $this->draft();
        $this->actingAs($this->admin)->post(route('admin.contracts.cancel', $draft), [])->assertSessionHasErrors('cancel_reason');
        $active = $this->active([], 'active-cancel');
        $this->post(route('admin.contracts.cancel', $active), ['cancel_reason' => 'Giả mạo'])->assertSessionHasErrors('contract');
        $this->assertSame(Contract::STATUS_ACTIVE, $active->fresh()->status);

        $this->post(route('admin.contracts.cancel', $draft), ['cancel_reason' => 'Hai bên không tiếp tục.'])->assertSessionHasNoErrors();
        $this->post(route('admin.contracts.submit-for-signature', $draft))->assertSessionHasErrors('contract');
        $this->assertSame(Contract::STATUS_CANCELLED, $draft->fresh()->status);
        $this->assertSame(1, $draft->statusHistories()->where('action', 'cancel')->count());
    }

    public function test_check_in_rejects_missing_signature_future_time_capacity_occupied_room_and_regressive_meters(): void
    {
        $missingSignature = $this->awaiting([], 'guard-signature');
        $missingSignature->forceFill(['signed_at' => null])->save();
        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $missingSignature), $this->checkInPayload())
            ->assertSessionHasErrors('contract');

        $futureTime = $this->awaiting([], 'guard-future');
        $this->post(route('admin.contracts.check-in', $futureTime), $this->checkInPayload([
            'actual_move_in_at' => now()->addMinute(),
        ]))->assertSessionHasErrors('actual_move_in_at');

        $capacity = $this->awaiting([], 'guard-capacity');
        $capacity->forceFill(['number_of_people' => $capacity->room->max_people + 1])->save();
        $this->post(route('admin.contracts.check-in', $capacity), $this->checkInPayload())
            ->assertSessionHasErrors('number_of_people');

        $occupied = $this->awaiting([], 'guard-occupied');
        $occupied->room->update(['status' => Room::STATUS_OCCUPIED, 'current_people' => 1]);
        $this->post(route('admin.contracts.check-in', $occupied), $this->checkInPayload())
            ->assertSessionHasErrors('room_id');

        $meters = $this->awaiting([], 'guard-meter');
        UtilityReading::query()->forceCreate([
            'room_id' => $meters->room_id, 'contract_id' => null, 'month' => 8, 'year' => 2026,
            'record_date' => today(), 'reading_type' => 'monthly', 'electricity_old' => 180,
            'electricity_new' => 200, 'water_old' => 15, 'water_new' => 20, 'status' => 'confirmed',
        ]);
        $this->post(route('admin.contracts.check-in', $meters), $this->checkInPayload())
            ->assertSessionHasErrors('handover_electricity');

        foreach ([$missingSignature, $futureTime, $capacity, $occupied, $meters] as $contract) {
            $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
            $this->assertNull($contract->fresh()->actual_move_in_at);
        }
    }

    public function test_reversing_deposit_after_check_in_keeps_occupancy_and_creates_exception_alert(): void
    {
        $contract = $this->draft(1000000, [], 'deposit-reversal');
        $this->sign($contract);
        $depositInvoice = $this->lifecycle->issueDepositInvoice($contract, $this->admin);
        $depositPayment = Payment::query()->forceCreate([
            'invoice_id' => $depositInvoice->id, 'amount_paid' => $depositInvoice->total_amount, 'payment_date' => today(),
            'payment_method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_SUCCESS,
        ]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->lifecycle->checkIn($contract, $this->admin, $this->checkInPayload());

        $depositPayment->update(['status' => Payment::STATUS_FAILED]);
        $this->lifecycle->syncDepositState($contract, $this->admin, 'Ngân hàng đảo giao dịch.');

        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $contract->room->fresh()->status);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id, 'type' => 'deposit_exception',
        ]);
    }

    public function test_cancelling_after_collecting_initial_payment_requires_deposit_resolution(): void
    {
        $contract = $this->draft(1000000, [], 'cancel-paid-deposit');
        $this->sign($contract);
        $invoice = $this->lifecycle->issueDepositInvoice($contract, $this->admin);
        Payment::query()->forceCreate([
            'invoice_id' => $invoice->id, 'amount_paid' => 500000, 'payment_date' => today(),
            'payment_method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_SUCCESS,
        ]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->lifecycle->cancel($contract, $this->admin, 'Khách hủy sau khi đã đóng một phần tiền cọc.');

        $this->assertSame(Contract::STATUS_CANCELLED, $contract->fresh()->status);
        $this->assertSame(Contract::DEPOSIT_NEEDS_RESOLUTION, $contract->fresh()->deposit_resolution);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id, 'type' => 'cancelled_deposit_resolution',
        ]);
        $this->assertSame(500000.0, $contract->fresh()->deposit_paid_amount);
    }

    public function test_scheduler_deduplicates_signature_and_deposit_overdue_alerts(): void
    {
        $signature = $this->draft(0, [], 'signature-overdue');
        $this->lifecycle->submitForSignature($signature, $this->admin);
        $signature->forceFill(['signature_due_at' => now()->subDay()])->save();
        $deposit = $this->draft(1000000, ['deposit_due_at' => now()->subDay()], 'deposit-overdue');
        $this->sign($deposit);

        $this->artisan('contracts:process-lifecycle')->assertSuccessful();
        $this->artisan('contracts:process-lifecycle')->assertSuccessful();

        $this->assertSame(1, ContractLifecycleAlert::where('contract_id', $signature->id)->where('type', 'signature_overdue')->count());
        $this->assertSame(1, ContractLifecycleAlert::where('contract_id', $deposit->id)->where('type', 'deposit_overdue')->count());
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $signature->fresh()->status);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $deposit->fresh()->status);
    }

    public function test_checkout_rejects_wrong_state_future_time_and_regressive_final_reading(): void
    {
        $awaiting = $this->awaiting([], 'checkout-state');
        $this->actingAs($this->admin)->post(route('admin.contracts.check-out', $awaiting), [
            'actual_move_out_at' => now(), 'checkout_electricity' => 110, 'checkout_water' => 11,
            'checkout_reason' => 'Request giả.',
        ])->assertSessionHasErrors('contract');

        $active = $this->active([], 'checkout-guards');
        $this->post(route('admin.contracts.check-out', $active), [
            'actual_move_out_at' => now()->addMinute(), 'checkout_electricity' => 110,
            'checkout_water' => 11, 'checkout_reason' => 'Tương lai.',
        ])->assertSessionHasErrors('actual_move_out_at');
        $this->post(route('admin.contracts.check-out', $active), [
            'actual_move_out_at' => now(), 'checkout_electricity' => 99,
            'checkout_water' => 9, 'checkout_reason' => 'Chỉ số sai.',
        ])->assertSessionHasErrors('checkout_electricity');

        $this->assertSame(Contract::STATUS_ACTIVE, $active->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $active->room->fresh()->status);
        $this->assertDatabaseMissing('utility_readings', [
            'contract_id' => $active->id, 'reading_type' => 'checkout',
        ]);
    }

    public function test_print_uses_signed_date_snapshot_and_labels_unsigned_document_as_draft(): void
    {
        Setting::currentOrCreate()->update([
            'electric_price' => 3500,
            'water_price' => 15000,
            'internet_fee' => 100000,
            'service_fee' => 50000,
        ]);
        $this->actingAs($this->admin)->get(route('admin.contracts.template'))
            ->assertOk()
            ->assertSee('HỢP ĐỒNG THUÊ PHÒNG TRỌ')
            ->assertSee('100.000đ/phòng/tháng')
            ->assertSee('VI. Cam kết, hiệu lực và giải quyết tranh chấp');

        $contract = $this->draft();
        $contract->forceFill([
            'landlord_name_snapshot' => 'Chủ nhà snapshot',
            'property_address_snapshot' => 'Địa chỉ snapshot',
        ])->save();
        $this->actingAs($this->admin)->get(route('admin.contracts.print', $contract))
            ->assertOk()
            ->assertSee('BẢN DỰ THẢO – CHƯA CÓ HIỆU LỰC')
            ->assertSee('Chủ nhà snapshot')
            ->assertSee('Địa chỉ snapshot')
            ->assertSee('III. Danh sách người thuê')
            ->assertSee('IV. Chỉ số điện nước và tài sản bàn giao')
            ->assertSee('3.500đ/kWh')
            ->assertSee('15.000đ/m³')
            ->assertSee('100.000đ/phòng/tháng')
            ->assertDontSee('CCCD mặt trước')
            ->assertDontSee('CCCD mặt sau');
        $this->sign($contract);
        $this->get(route('admin.contracts.print', $contract))->assertOk()
            ->assertDontSee('BẢN DỰ THẢO – CHƯA CÓ HIỆU LỰC')->assertSee('11/08/2026');
    }

    private function draft(float $deposit = 0, array $overrides = [], ?string $tenantKey = null): Contract
    {
        $room = isset($overrides['room_id']) ? Room::findOrFail($overrides['room_id']) : $this->room(uniqid('ROOM-'));
        $tenant = $this->tenant($tenantKey ?? uniqid('tenant-'));
        $data = array_merge([
            'room_id' => $room->id, 'tenant_id' => $tenant->id, 'start_date' => '2026-08-11',
            'end_date' => '2027-08-11', 'scheduled_move_in_date' => '2026-08-11',
            'reservation_expires_at' => '2026-08-12 18:00:00', 'signature_due_at' => '2026-08-11 18:00:00',
            'deposit_due_at' => '2026-08-12 12:00:00', 'deposit_amount' => $deposit,
            'number_of_people' => 1, 'parking_quantity' => 0,
        ], $overrides);

        return $this->lifecycle->createDraft($data, $this->admin);
    }

    private function sign(Contract $contract): Contract
    {
        $this->lifecycle->submitForSignature($contract, $this->admin);
        $contract = $this->lifecycle->markAsSigned($contract, $this->admin, now());
        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);

        return $contract->fresh();
    }

    private function awaiting(array $overrides = [], ?string $tenantKey = null): Contract
    {
        $contract = $this->draft(0, $overrides, $tenantKey);
        $contract = $this->sign($contract);
        $this->payDeposit($contract);

        return $contract->fresh();
    }

    private function payDeposit(Contract $contract): void
    {
        $this->lifecycle->issueDepositInvoice($contract, $this->admin);
        $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)
            ->get()->each(function (Invoice $invoice): void {
                if ((float) $invoice->total_amount <= 0) {
                    return;
                }
                Payment::query()->forceCreate([
                    'invoice_id' => $invoice->id, 'amount_paid' => $invoice->total_amount,
                    'payment_date' => today(), 'payment_method' => Payment::METHOD_CASH,
                    'status' => Payment::STATUS_SUCCESS,
                ]);
            });
        $this->lifecycle->syncDepositState($contract, $this->admin);
    }

    private function active(array $overrides = [], ?string $tenantKey = null): Contract
    {
        $contract = $this->awaiting($overrides, $tenantKey);
        $this->lifecycle->checkIn($contract, $this->admin, $this->checkInPayload([
            'schedule_variance_reason' => $contract->scheduled_move_in_date?->isSameDay(now()) ? null : 'Ngày nhận thực tế khác lịch.',
        ]));

        return $contract->fresh();
    }

    public function test_new_contract_enforces_minimum_year_move_in_limit_and_ignores_legacy_parking_fields(): void
    {
        $room = $this->room('NEW-RULES');
        $tenant = $this->tenant('new-rules');
        $this->actingAs($this->admin);

        $this->post(route('admin.contracts.store'), $this->payload($room, $tenant, ['contract_duration' => 11]))
            ->assertSessionHasErrors('contract_duration');
        $this->post(route('admin.contracts.store'), $this->payload($room, $tenant, ['scheduled_move_in_date' => '2026-10-02']))
            ->assertSessionHasErrors('scheduled_move_in_date');
        $this->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'parking_enabled' => 1, 'parking_vehicle_type' => Contract::PARKING_CAR, 'parking_quantity' => 1,
        ]))->assertSessionHasNoErrors();
        $contract = Contract::sole();
        $this->assertTrue($contract->service_enabled);
        $this->assertNull($contract->parking_vehicle_type);
        $this->assertSame(0, $contract->parking_quantity);
    }

    public function test_offline_tenant_cannot_be_used_to_create_a_contract(): void
    {
        $tenant = Tenant::create(['user_id' => null, 'full_name' => 'Khách offline', 'cccd' => '079000009999', 'phone' => '0900009999']);
        $room = $this->room('OFFLINE');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant))
            ->assertSessionHasErrors('tenant_id');

        $this->assertFalse(Tenant::query()->eligibleForContract()->whereKey($tenant)->exists());
        $this->assertDatabaseCount('contracts', 0);
    }

    private function checkInPayload(array $overrides = []): array
    {
        return array_merge([
            'actual_move_in_at' => now(), 'handover_electricity' => 100, 'handover_water' => 10,
            'handover_confirmed' => 1, 'schedule_variance_reason' => null,
        ], $overrides);
    }

    private function payload(Room $room, Tenant $tenant, array $overrides = []): array
    {
        $payload = [
            'room_id' => $room->id, 'tenant_id' => $tenant->id, 'start_date' => '2026-09-01',
            'contract_duration' => '12', 'end_date' => '2027-09-01', 'scheduled_move_in_date' => '2026-09-01',
            'reservation_expires_at' => '2026-09-11', 'move_in_terms_confirmed' => 1, 'deposit_amount' => 1000000,
            'number_of_people' => 1, 'parking_quantity' => 0,
            'representative' => [
                'identity_front' => UploadedFile::fake()->image('cccd-front.jpg'),
                'identity_back' => UploadedFile::fake()->image('cccd-back.jpg'),
            ],
        ];
        if (isset($overrides['representative'])) {
            $overrides['representative'] = array_merge($payload['representative'], $overrides['representative']);
        }

        return array_merge($payload, $overrides);
    }

    private function memberIdentityImages(string $prefix, string $dateOfBirth = '1990-01-01'): array
    {
        return [
            'date_of_birth' => $dateOfBirth,
            'gender' => 'other',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '09'.substr(str_pad((string) abs(crc32('phone-'.$prefix)), 8, '0'), 0, 8),
            'email' => substr(hash('sha256', $prefix), 0, 16).'@member.example.test',
            'address' => 'Địa chỉ thường trú của người thuê',
            'identity_front' => UploadedFile::fake()->image($prefix.'-front.jpg'),
            'identity_back' => UploadedFile::fake()->image($prefix.'-back.jpg'),
        ];
    }

    private function room(string $code, array $overrides = []): Room
    {
        return Room::create(array_merge([
            'room_code' => $code, 'floor' => 1, 'price' => 3000000, 'area' => 25,
            'max_people' => 4, 'current_people' => 0, 'status' => Room::STATUS_AVAILABLE,
        ], $overrides));
    }

    private function tenant(string $key): Tenant
    {
        $user = $this->user($this->clientRole, $key.'@example.test');

        return Tenant::create([
            'user_id' => $user->id, 'full_name' => 'Tenant '.$key,
            'date_of_birth' => '1995-05-20',
            'gender' => 'other',
            'cccd' => substr(str_pad((string) abs(crc32($key)), 12, '0'), 0, 12),
            'cccd_issue_date' => '2021-06-01',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '09'.substr(str_pad((string) abs(crc32('p'.$key)), 8, '0'), 0, 8),
            'email' => $key.'@tenant.test',
            'address' => 'Địa chỉ kiểm thử',
        ]);
    }

    private function user(Role $role, string $email): User
    {
        return User::create([
            'name' => 'User', 'email' => $email, 'role_id' => $role->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
    }
}

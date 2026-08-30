<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractStatusHistory;
use App\Models\ContractTemplate;
use App\Models\ContractTenant;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\Vehicle;
use App\Services\ContractLifecycleService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
            ->assertSee('Danh sách người thuê')
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

    public function test_admin_can_create_contract_with_only_name_and_gender_without_notifying_user_while_still_draft(): void
    {
        $room = $this->room('INCOMPLETE-CO-TENANT');
        $representative = $this->tenant('incomplete-co-tenant');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $representative, [
            'members' => [[
                'full_name' => 'Người ở cùng bổ sung sau',
                'gender' => 'female',
            ]],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $member = Contract::sole()->members()
            ->where('role', ContractTenant::ROLE_TENANT)
            ->with('tenant')
            ->sole();

        $this->assertNull($member->date_of_birth);
        $this->assertNull($member->identity_number);
        $this->assertNull($member->phone);
        $this->assertNull($member->address);
        $this->assertSame('female', $member->tenant->gender);
        $this->assertNull($member->tenant->cccd);
        $this->assertNull($member->tenant->phone);

        $this->assertFalse($representative->user->notifications()
            ->where('data->type', 'contract_members_profile_incomplete')
            ->exists());
    }

    public function test_incomplete_member_is_notified_and_must_be_completed_before_move_in_confirmation(): void
    {
        $contract = $this->draft(0, [
            'members' => [[
                'full_name' => 'Người bổ sung trước nhận phòng',
                'gender' => 'female',
            ]],
        ], 'complete-before-move-in');
        $client = $contract->tenant->user;
        $member = $contract->members()->where('role', ContractTenant::ROLE_TENANT)->sole();

        $this->assertFalse($client->notifications()
            ->where('data->type', 'contract_members_profile_incomplete')->exists());

        $contract = $this->sign($contract);
        $this->payDeposit($contract);
        $contract = $contract->fresh();
        $notification = $client->notifications()
            ->where('data->type', 'contract_members_profile_incomplete')->sole();
        $this->assertNull($notification->read_at);

        try {
            $this->lifecycle->confirmMoveInDetails($contract, $client);
            $this->fail('Hồ sơ thiếu phải chặn xác nhận nhận phòng.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('members', $exception->errors());
        }

        $this->actingAs($client)
            ->get(route('client.contracts.members.edit', [$contract, $member]))
            ->assertOk()
            ->assertSee('Hoàn thiện hồ sơ');

        $this->put(route('client.contracts.members.update', [$contract, $member]), [
            'full_name' => 'Người bổ sung trước nhận phòng',
            'identity_number' => '012345678944',
            ...$this->memberIdentityImages('complete-before-move-in-member'),
        ])->assertRedirect(route('client.contracts.show', $contract))->assertSessionHasNoErrors();

        $this->assertTrue($member->fresh('tenant')->hasCompleteMoveInProfile());
        $this->assertNotNull($notification->fresh()->read_at);

        $this->lifecycle->saveHandoverDraft($contract, $this->admin, 100, 10);
        $this->lifecycle->confirmMoveInDetails($contract, $client);
        $this->assertNotNull($contract->fresh()->move_in_details_confirmed_at);
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
            ->assertSee('data-contract-print', false)
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

    public function test_active_contract_detail_links_to_the_guided_checkout_workflow(): void
    {
        $contract = $this->active();

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Người thuê trong phòng')
            ->assertSee('Điện nước gần nhất')
            ->assertSee('Thu tiền hợp đồng')
            ->assertDontSee('Thao tác cuối hợp đồng')
            ->assertSee('Dịch vụ, phương tiện và tài sản bàn giao')
            ->assertSee('Lịch sử hợp đồng')
            ->assertSee('href="'.route('admin.contracts.check-out.form', $contract).'"', false)
            ->assertDontSee("document.getElementById('checkout-contract-dialog').showModal()", false)
            ->assertSee('href="'.route('admin.contracts.extend.form', $contract).'"', false)
            ->assertSee('<section id="contract-assets"', false)
            ->assertSee('<section id="contract-history"', false)
            ->assertDontSee('aria-label="Điều hướng nhanh"', false)
            ->assertDontSee('Bước tiếp theo');

        $this->get(route('admin.contracts.check-out.form', $contract))
            ->assertOk()
            ->assertSee('Quy trình kết thúc hợp đồng')
            ->assertSee('Bước 1/4')
            ->assertSee('Lý do &amp; lịch bàn giao', false)
            ->assertSee('action="'.route('admin.contracts.departure-schedule', $contract).'"', false)
            ->assertDontSee('action="'.route('admin.contracts.check-out', $contract).'"', false);

        $this->post(route('admin.contracts.departure-schedule', $contract), [
            'approved_end_date' => today()->toDateString(),
            'departure_reason' => 'Hai bên thống nhất kết thúc và bàn giao phòng.',
        ])->assertRedirect(route('admin.contracts.check-out.form', $contract));

        $this->get(route('admin.contracts.check-out.form', $contract))
            ->assertOk()
            ->assertSee('Bước 2/4')
            ->assertSee('Bàn giao phòng')
            ->assertSee('Quyết toán &amp; tiền cọc', false)
            ->assertSee('Hoàn tất hợp đồng')
            ->assertSee('Phòng hoặc tài sản có hư hỏng/thất lạc không?')
            ->assertSee('Tiền người thuê cần bồi thường')
            ->assertSee('Ảnh đồ vật hư hỏng/thất lạc')
            ->assertSee('data-damage-fields', false)
            ->assertDontSee('name="checkout_key_count"', false)
            ->assertDontSee('Số chìa khóa đã trả')
            ->assertSee('Tạm tính sau đối trừ')
            ->assertSee('action="'.route('admin.contracts.check-out', $contract).'"', false);
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
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="proof_image"', false)
            ->assertDontSee('name="transaction_code"', false)
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
        ])->assertSessionHasErrors('proof_image');

        $proof = UploadedFile::fake()->image('deposit-transfer.png');
        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1000000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'proof_image' => $proof,
            'return_to_contract' => 1,
        ])->assertRedirect(route('admin.contracts.show', $contract))->assertSessionHasNoErrors();

        $bankPayment = $invoice->payments()->where('payment_method', Payment::METHOD_BANK_TRANSFER)->sole();
        $this->assertNull($bankPayment->transaction_code);
        $this->assertTrue($bankPayment->proofImageExists());

        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 500000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_CASH,
            'return_to_contract' => 1,
        ])->assertRedirect(route('admin.contracts.show', $contract))->assertSessionHasNoErrors();

        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('1.500.000đ')
            ->assertSee('1.500.000đ')
            ->assertSee('Xem ảnh');
    }

    public function test_awaiting_move_in_detail_prioritizes_check_in_and_moves_secondary_actions_to_dialogs(): void
    {
        $contract = $this->awaiting([], 'awaiting-move-in-layout');

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Xác nhận nhận phòng')
            ->assertSee('Đã khóa theo xác nhận của khách')
            ->assertSee('name="actual_move_in_at"', false)
            ->assertDontSee('name="handover_electricity"', false)
            ->assertDontSee('name="handover_water"', false)
            ->assertSee('Điện đã khóa')
            ->assertSee('Đã đối chiếu đúng chỉ số điện')
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
        Setting::currentOrCreate(['internet_fee' => 100000, 'service_fee' => 50000]);
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
        $this->lifecycle->markAsSigned($contract, $this->admin, now());
        $this->payDeposit($contract);
        $this->actingAs($this->admin)->post(route('admin.contracts.handover-reading.store', $contract), [
            'handover_electricity' => 135,
            'handover_water' => 24,
        ])->assertSessionHasErrors(['handover_electricity_image', 'handover_water_image']);
        $this->assertDatabaseMissing('utility_readings', [
            'contract_id' => $contract->id,
            'reading_type' => 'handover',
        ]);
        $this->actingAs($this->admin)->post(route('admin.contracts.handover-reading.store', $contract), [
            'handover_electricity' => 135,
            'handover_water' => 24,
            'handover_electricity_image' => UploadedFile::fake()->image('electricity-meter.jpg'),
            'handover_water_image' => UploadedFile::fake()->image('water-meter.jpg'),
        ])->assertSessionHasNoErrors();
        $handoverReading = $contract->utilityReadings()->where('reading_type', 'handover')->sole();
        Storage::disk('local')->assertExists([
            $handoverReading->electricity_image,
            $handoverReading->water_image,
        ]);

        $this->actingAs($tenant->user)->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Xác nhận thông tin nhận phòng')
            ->assertSee('Tôi đã đối chiếu ảnh đồng hồ với chỉ số điện')
            ->assertSee('135 kWh')
            ->assertSee(route('client.contracts.handover-meter-image', [$contract, 'electricity']), false)
            ->assertSee(route('client.contracts.handover-meter-image', [$contract, 'water']), false)
            ->assertSee('24 m³')
            ->assertSee('Xác nhận thông tin')
            ->assertSee('action="'.route('client.contracts.move-in-details.confirm', $contract).'"', false)
            ->assertDontSee('Xác nhận biên bản nhận phòng')
            ->assertSee('Thông tin nhận phòng')
            ->assertSee('Internet')
            ->assertDontSee('Internet bắt buộc')
            ->assertSee('100.000đ/phòng/tháng')
            ->assertDontSee('Đã bao gồm, không tính phí riêng')
            ->assertSee('Dịch vụ chung')
            ->assertDontSee('Dịch vụ chung bắt buộc')
            ->assertSee('50.000đ/tháng')
            ->assertDontSee('Máy lạnh')
            ->assertSee('59A1-12345')
            ->assertSee('Quản lý phương tiện')
            ->assertSee('Bàn học bàn giao')
            ->assertSee('Có hư hỏng')
            ->assertSee('Xước nhẹ cạnh bàn');

        $this->actingAs($otherTenant->user)->post(route('client.contracts.move-in-details.confirm', $contract), [
            'confirmation' => 1,
        ])->assertNotFound();
        $this->get(route('client.contracts.handover-meter-image', [$contract, 'electricity']))
            ->assertNotFound();
        $this->assertNull($contract->fresh()->move_in_details_confirmed_at);

        $this->actingAs($tenant->user)->post(route('client.contracts.move-in-details.confirm', $contract), [
            'confirmation' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('client.contracts.handover-meter-image', [$contract, 'electricity']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
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
        $this->lifecycle->saveHandoverDraft($contract, $this->admin, 100, 10);

        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('move_in_details_confirmed');
        $this->assertNull($contract->fresh()->actual_move_in_at);

        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
    }

    public function test_confirmed_handover_reading_cannot_be_changed_or_tampered_during_check_in(): void
    {
        $contract = $this->awaiting([], 'locked-handover');

        $this->actingAs($this->admin)->post(route('admin.contracts.handover-reading.store', $contract), [
            'handover_electricity' => 999,
            'handover_water' => 99,
            'handover_electricity_image' => UploadedFile::fake()->image('tampered-electricity.jpg'),
            'handover_water_image' => UploadedFile::fake()->image('tampered-water.jpg'),
        ])->assertSessionHasErrors('handover_reading');

        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload([
            'handover_electricity' => 999,
            'handover_water' => 99,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id,
            'reading_type' => 'handover',
            'electricity_new' => 100,
            'water_new' => 10,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);
        $this->assertDatabaseMissing('utility_readings', [
            'contract_id' => $contract->id,
            'electricity_new' => 999,
        ]);
    }

    public function test_admin_must_record_reason_and_client_must_reconfirm_after_handover_change(): void
    {
        $contract = $this->awaiting([], 'reconfirm-handover');

        $this->actingAs($this->admin)->post(route('admin.contracts.move-in-details.reopen', $contract), [
            'reason' => 'ngắn',
        ])->assertSessionHasErrors('reason');
        $this->assertNotNull($contract->fresh()->move_in_details_confirmed_at);

        $reason = 'Đồng hồ vừa được đối chiếu lại tại phòng.';
        $this->post(route('admin.contracts.move-in-details.reopen', $contract), [
            'reason' => $reason,
        ])->assertSessionHasNoErrors();
        $this->assertNull($contract->fresh()->move_in_details_confirmed_at);
        $this->assertDatabaseHas('contract_status_histories', [
            'contract_id' => $contract->id,
            'action' => 'reopen_move_in_details',
            'reason' => $reason,
            'performed_by' => $this->admin->id,
        ]);

        $this->post(route('admin.contracts.handover-reading.store', $contract), [
            'handover_electricity' => 120,
            'handover_water' => 12,
            'handover_electricity_image' => UploadedFile::fake()->image('electricity-updated.jpg'),
            'handover_water_image' => UploadedFile::fake()->image('water-updated.jpg'),
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('move_in_details_confirmed');

        $this->actingAs($contract->tenant->user)->post(route('client.contracts.move-in-details.confirm', $contract), [
            'confirmation' => 1,
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id,
            'electricity_new' => 120,
            'water_new' => 12,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);
    }

    public function test_legacy_confirmation_without_handover_reading_can_be_safely_reopened(): void
    {
        $contract = $this->awaiting([], 'legacy-confirmation');
        $contract->utilityReadings()->where('reading_type', 'handover')->delete();

        $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Xác nhận cũ thiếu chỉ số — cần mở lại')
            ->assertSee('Mở lại xác nhận cũ')
            ->assertDontSee('name="handover_electricity"', false);

        $this->post(route('admin.contracts.move-in-details.reopen', $contract), [
            'reason' => 'Cập nhật phiếu bàn giao để bổ sung chỉ số điện nước.',
        ])->assertSessionHasNoErrors();
        $this->assertNull($contract->fresh()->move_in_details_confirmed_at);

        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('name="handover_electricity"', false)
            ->assertSee('name="handover_water"', false);
    }

    public function test_draft_records_representative_and_named_members_without_requiring_accounts(): void
    {
        $room = $this->room('MEMBERS');
        $representative = $this->tenant('representative');
        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee('Người thuê đại diện (được cấp tài khoản)')
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

    public function test_reserved_room_waiting_for_move_in_is_not_selectable_for_a_new_contract(): void
    {
        $reserved = $this->awaiting([], 'reserved-room-guest');
        $newTenant = $this->tenant('blocked-reserved-room-guest');

        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertDontSee($reserved->room->room_code);

        $this->post(route('admin.contracts.store'), $this->payload($reserved->room, $newTenant))
            ->assertSessionHasErrors('room_id');

        $this->assertSame(1, Contract::query()->count());
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $reserved->fresh()->status);
    }

    public function test_room_is_reserved_as_soon_as_contract_is_waiting_for_signature(): void
    {
        $reserved = $this->draft(0, [], 'pending-signature-room-guest');
        $this->lifecycle->submitForSignature($reserved, $this->admin);
        $newTenant = $this->tenant('blocked-pending-signature-room-guest');

        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertDontSee($reserved->room->room_code);

        $this->post(route('admin.contracts.store'), $this->payload($reserved->room, $newTenant))
            ->assertSessionHasErrors('room_id');

        $this->assertSame(1, Contract::query()->count());
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $reserved->fresh()->status);
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

    public function test_contract_automatically_uses_identity_images_from_tenant_profile(): void
    {
        $room = $this->room('PROFILE-IDENTITY');
        $tenant = $this->tenant('profile-identity');
        Storage::disk('local')->put('tenant-identities/front.jpg', 'front-image');
        Storage::disk('local')->put('tenant-identities/back.jpg', 'back-image');
        $tenant->document()->create([
            'cccd' => $tenant->cccd,
            'cccd_issue_date' => $tenant->cccd_issue_date,
            'cccd_issue_place' => $tenant->cccd_issue_place,
            'cccd_front_image' => 'tenant-identities/front.jpg',
            'cccd_back_image' => 'tenant-identities/back.jpg',
        ]);

        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee(route('admin.tenants.identity-document', [$tenant, 'front']));

        $payload = $this->payload($room, $tenant);
        unset($payload['representative']['identity_front'], $payload['representative']['identity_back']);
        $this->post(route('admin.contracts.store'), $payload)->assertSessionHasNoErrors();

        $representative = Contract::sole()->representativeMember;
        $this->assertSame('tenant-identities/front.jpg', $representative->identity_front_path);
        $this->assertSame('tenant-identities/back.jpg', $representative->identity_back_path);
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
        $profileDocument = $tenant->fresh()->document;
        $this->assertSame($representative->identity_front_path, $profileDocument->cccd_front_image);
        $this->assertSame($representative->identity_back_path, $profileDocument->cccd_back_image);
        $this->actingAs($tenant->user)->get(route('client.account.identity-document', 'front'))->assertOk();

        $this->actingAs($tenant->user)->get(route('admin.contract-tenants.identity-document', [$representative, 'front']))
            ->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.contract-tenants.identity-document', [$representative, 'front']))
            ->assertOk();

        $this->sign($contract);
        $this->payDeposit($contract);
        $this->lifecycle->saveHandoverDraft($contract, $this->admin, 100, 10);
        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);
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
                'identity_front' => UploadedFile::fake()->image('partial-front.jpg'),
            ]],
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.contracts.store'), $payload);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'members.0.identity_back',
            ]);
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

        $this->actingAs($client)->get(route('client.contracts.tenants.create', $contract))
            ->assertOk()
            ->assertSee('người thuê đại diện tiếp tục là đầu mối duy nhất')
            ->assertSee('không được cấp tài khoản riêng');

        $this->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Người chờ duyệt', 'identity_number' => '012345678911',
            'phone' => '0901111111', ...$this->memberIdentityImages('pending-resident'),
        ])->assertRedirect(route('client.contracts.show', $contract))->assertSessionHasNoErrors();

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

    public function test_client_cannot_request_another_member_when_room_is_already_full(): void
    {
        $contract = $this->active([], 'full-room-member-request');
        $contract->forceFill(['number_of_people' => 2])->save();
        $contract->room->forceFill(['max_people' => 2, 'current_people' => 1])->save();
        $client = $contract->tenant->user;
        $memberCount = ContractTenant::query()->count();
        $tenantCount = Tenant::query()->count();

        $this->actingAs($client)
            ->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('2/2')
            ->assertDontSee('+ Thêm người thuê');

        $this->get(route('client.contracts.tenants.create', $contract))
            ->assertStatus(409);

        $this->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Người không thể thêm',
            'identity_number' => '012345678912',
            ...$this->memberIdentityImages('blocked-full-room'),
        ])->assertSessionHasErrors('members');

        $this->assertDatabaseCount('contract_tenants', $memberCount);
        $this->assertDatabaseCount('tenants', $tenantCount);
    }

    public function test_pending_member_request_reserves_the_last_room_slot(): void
    {
        $contract = $this->active([], 'pending-reserves-last-slot');
        $contract->room->forceFill(['max_people' => 2, 'current_people' => 1])->save();
        $client = $contract->tenant->user;

        $this->actingAs($client)->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Người giữ slot cuối',
            'identity_number' => '012345678913',
            ...$this->memberIdentityImages('pending-last-slot'),
        ])->assertSessionHasNoErrors();

        $this->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('2/2')
            ->assertDontSee('+ Thêm người thuê');
        $this->get(route('client.contracts.tenants.create', $contract))->assertStatus(409);

        $this->post(route('client.contracts.members.store', $contract), [
            'full_name' => 'Người vượt slot',
            'identity_number' => '012345678914',
            ...$this->memberIdentityImages('over-pending-slot'),
        ])->assertSessionHasErrors('members');

        $this->assertSame(1, ContractTenant::query()->where('status', ContractTenant::STATUS_PENDING)->count());
    }

    public function test_approved_member_can_withdraw_before_receiving_the_room(): void
    {
        $contract = $this->awaiting([], 'approved-withdrawal');
        $memberTenant = $this->tenant('approved-member-withdrawal');
        $member = ContractTenant::query()->create([
            'contract_id' => $contract->id,
            'tenant_id' => $memberTenant->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => $memberTenant->full_name,
            'date_of_birth' => $memberTenant->date_of_birth,
            'identity_number' => $memberTenant->cccd,
            'phone' => $memberTenant->phone,
            'status' => ContractTenant::STATUS_APPROVED,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
        $contract->forceFill(['number_of_people' => 2])->save();

        $this->actingAs($contract->tenant->user)
            ->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Không nhận phòng');

        $this->post(route('client.contracts.members.withdraw', [$contract, $member]))
            ->assertSessionHasNoErrors();

        $this->assertSame(ContractTenant::STATUS_WITHDRAWN, $member->fresh()->status);
        $this->assertSame(1, $contract->fresh()->number_of_people);
        $this->assertNull($member->actual_move_in_at);
        $this->assertDatabaseHas('contract_tenant_histories', [
            'contract_tenant_id' => $member->id,
            'from_status' => ContractTenant::STATUS_APPROVED,
            'to_status' => ContractTenant::STATUS_WITHDRAWN,
            'action' => 'tenant_withdraw_approved',
        ]);
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
        $this->get(route('client.contracts.tenants.create', $contract))->assertNotFound();
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

    public function test_admin_can_restore_an_individual_move_out_entered_by_mistake(): void
    {
        $contract = $this->active([], 'restore-move-out');
        $memberTenant = $this->tenant('restored-member');
        $member = ContractTenant::query()->create([
            'contract_id' => $contract->id,
            'tenant_id' => $memberTenant->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => $memberTenant->full_name,
            'date_of_birth' => $memberTenant->date_of_birth,
            'identity_number' => $memberTenant->cccd,
            'phone' => $memberTenant->phone,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => now()->subDay(),
        ]);
        $contract->forceFill(['number_of_people' => 2])->save();
        $contract->room->forceFill(['current_people' => 2])->save();
        $residence = TemporaryResidence::query()->create([
            'tenant_id' => $memberTenant->id,
            'contract_id' => $contract->id,
            'contract_tenant_id' => $member->id,
            'start_date' => today()->subDay(),
            'end_date' => today()->addMonths(6),
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post(route('admin.contract-tenants.move-out', $member), [
            'actual_move_out_at' => now()->format('Y-m-d H:i:s'),
            'reason' => 'Nhập nhầm người rời phòng.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(ContractTenant::STATUS_MOVED_OUT, $member->fresh()->status);
        $this->assertSame('expired', $residence->fresh()->status);
        $this->assertSame(1, $contract->room->fresh()->current_people);
        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Lịch sử rời phòng')
            ->assertSee('departure-history-dialog')
            ->assertSee('Khôi phục do nhập nhầm')
            ->assertSee('action="'.route('admin.contract-tenants.restore-move-out', $member).'"', false);

        $this->post(route('admin.contract-tenants.restore-move-out', $member), [
            'reason' => 'Đối chiếu lại và xác nhận admin đã chọn nhầm thành viên.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(ContractTenant::STATUS_CHECKED_IN, $member->fresh()->status);
        $this->assertNull($member->actual_move_out_at);
        $this->assertSame('active', $residence->fresh()->status);
        $this->assertSame(2, $contract->fresh()->number_of_people);
        $this->assertSame(2, $contract->room->fresh()->current_people);
        $this->assertDatabaseHas('contract_tenant_histories', [
            'contract_tenant_id' => $member->id,
            'from_status' => ContractTenant::STATUS_MOVED_OUT,
            'to_status' => ContractTenant::STATUS_CHECKED_IN,
            'action' => 'tenant_move_out_reverted',
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

        $this->post(route('admin.contracts.mark-signed', $contract), [
            'signed_at' => now()->subHour(),
            'signed_contract_file' => UploadedFile::fake()->image('signed-contract.jpg'),
        ])
            ->assertSessionHasNoErrors();
        $contract->refresh();
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->status);
        $this->assertSame($this->admin->id, $contract->signed_confirmed_by);
        Storage::disk('local')->assertExists($contract->contract_file);
        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Bản hợp đồng đã ký')
            ->assertSee('data-image-modal', false)
            ->assertSee('data-media-type="image"', false)
            ->assertSee('href="'.route('admin.contracts.file', $contract).'"', false);
        $historyCount = ContractStatusHistory::count();
        $this->post(route('admin.contracts.mark-signed', $contract), ['signed_at' => now()->subHour()]);
        $this->assertSame($historyCount, ContractStatusHistory::count());

        $this->actingAs($contract->tenant->user)->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Bản hợp đồng đã ký')
            ->assertSee('data-image-modal', false)
            ->assertSee('data-media-type="image"', false)
            ->assertSee('href="'.route('client.contracts.file', $contract).'"', false)
            ->assertDontSee('target="_blank"', false);
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
            now()->addDays(5)->toDateTimeString(),
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

        $pendingPayment = Payment::query()->forceCreate(['invoice_id' => $depositInvoice->id, 'amount_paid' => 500000, 'payment_date' => today(), 'payment_method' => 'cash', 'status' => Payment::STATUS_PENDING]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->actingAs($this->admin)
            ->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertDontSee('Thanh toán chờ xác nhận');
        Payment::query()->forceCreate(['invoice_id' => $depositInvoice->id, 'amount_paid' => 500000, 'payment_date' => today(), 'payment_method' => 'cash', 'status' => Payment::STATUS_FAILED]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $depositInvoice), [
            'amount_paid' => 1250000, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->post(route('admin.invoices.payments.store', $depositInvoice), [
            'amount_paid' => 1750000, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasErrors('amount_paid');
        $this->post(route('admin.invoices.payments.reject', $pendingPayment), [
            'review_note' => 'Khoản chờ được thay thế bằng giao dịch do admin ghi nhận.',
        ])->assertSessionHasNoErrors();
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

        try {
            $this->draft(0, ['room_id' => $room->id, 'start_date' => '2027-08-01', 'scheduled_move_in_date' => '2027-08-01', 'reservation_expires_at' => '2027-09-01 18:00:00', 'end_date' => '2028-08-01'], 'reserve-second');
            $this->fail('Lịch thuê trùng với phòng đã giữ chỗ phải bị từ chối ngay khi tạo bản nháp.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('room_id', $exception->errors());
        }

        $this->assertDatabaseCount('contracts', 1);
        $third = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2027-09-02', 'scheduled_move_in_date' => '2027-09-02', 'reservation_expires_at' => '2027-10-02 18:00:00', 'end_date' => '2028-09-02'], 'reserve-third');
        $this->sign($third);
        $this->payDeposit($third);
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $third->fresh()->status);
    }

    public function test_same_representative_can_hold_overlapping_contracts_for_different_rooms(): void
    {
        $first = $this->draft(0, [], 'multi-room-representative');
        $secondRoom = $this->room('MULTI-ROOM-02');
        $second = $this->lifecycle->createDraft([
            'room_id' => $secondRoom->id,
            'tenant_id' => $first->tenant_id,
            'start_date' => $first->start_date->toDateString(),
            'end_date' => $first->end_date->toDateString(),
            'scheduled_move_in_date' => $first->scheduled_move_in_date->toDateString(),
            'reservation_expires_at' => '2026-08-12 18:00:00',
            'number_of_people' => 1,
            'members' => [],
        ], $this->admin);

        $this->lifecycle->submitForSignature($first, $this->admin);
        $this->lifecycle->submitForSignature($second, $this->admin);

        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $first->fresh()->status);
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $second->fresh()->status);
        $this->assertNotSame($first->room_id, $second->room_id);
        $this->assertSame(2, Contract::query()
            ->where('tenant_id', $first->tenant_id)
            ->where('start_date', $first->start_date)
            ->count());
    }

    public function test_check_in_validates_all_guards_and_rolls_back_when_any_write_fails(): void
    {
        $contract = $this->awaiting();
        $room = $contract->room;
        $room->update(['status' => Room::STATUS_MAINTENANCE]);
        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('room_id');
        $this->assertNull($contract->fresh()->actual_move_in_at);
        $this->assertDatabaseCount('utility_readings', 1);

        $room->update(['status' => Room::STATUS_AVAILABLE]);
        UtilityReading::updating(fn () => throw new RuntimeException('simulated write failure'));
        try {
            $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload());
            $this->fail('Expected simulated transaction failure.');
        } catch (RuntimeException) {
            $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
            $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
            $this->assertSame(0, $room->fresh()->current_people);
            $this->assertDatabaseCount('utility_readings', 1);
            $this->assertDatabaseHas('utility_readings', [
                'contract_id' => $contract->id, 'reading_type' => 'handover', 'status' => UtilityReading::STATUS_DRAFT,
            ]);
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
        $contract = $this->draft();
        $this->sign($contract);
        $this->payDeposit($contract);
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

        $this->post(route('admin.contracts.handover-reading.store', $contract), [
            'handover_electricity' => 321,
            'handover_water' => 45,
            'handover_electricity_image' => UploadedFile::fake()->image('electricity-baseline.jpg'),
            'handover_water_image' => UploadedFile::fake()->image('water-baseline.jpg'),
        ])->assertSessionHasNoErrors();
        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload([
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
            'new_end_date' => '2027-08-10', 'reason' => 'Hai bên đồng ý gia hạn.', 'confirm_extend' => '1',
        ])->assertSessionHasNoErrors();
        $extensionRequest = $old->extensionRequests()->latest('id')->firstOrFail();
        $this->assertSame(ContractExtensionRequest::STATUS_APPROVED, $extensionRequest->status);
        $this->assertSame(Contract::STATUS_ACTIVE, $old->fresh()->status);

        $future = $this->awaiting(['room_id' => $room->id, 'start_date' => '2027-08-11', 'scheduled_move_in_date' => '2027-08-11', 'reservation_expires_at' => '2027-08-12 18:00:00', 'end_date' => '2028-08-11'], 'future-tenant');
        $this->actingAs($this->admin);
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
            'has_damage' => 1, 'checkout_damage_note' => 'Cửa phòng bị hỏng bản lề.',
            'settlement_description' => 'Bồi thường cửa hỏng', 'checkout_photos' => [UploadedFile::fake()->image('damage.jpg')],
            'checkout_key_count' => 1, 'handover_confirmed' => '1',
        ];
        $this->actingAs($this->admin)->post(route('admin.contracts.check-out', $contract), $payload)->assertSessionHasNoErrors();
        $contract->refresh();
        $this->assertSame(Contract::STATUS_SETTLING, $contract->status);
        $this->assertNotNull($contract->actual_move_out_at);
        $this->assertSame(Room::STATUS_AVAILABLE, $contract->room->fresh()->status);
        $this->assertSame(0, $contract->room->fresh()->current_people);
        $this->assertNotNull($contract->approved_termination_request_id);
        $this->assertSame(
            ContractTerminationRequest::STATUS_COMPLETED,
            $contract->approvedTerminationRequest->status,
        );
        $this->assertSame(1, $contract->statusHistories()->where('action', 'schedule_departure')->count());
        $this->assertTrue($contract->checkout_has_damage);
        $this->assertSame(500000.0, (float) $contract->settlementStatement->items()->where('type', 'adjustment')->sole()->amount);
        $this->assertSame(1, $contract->utilityReadings()->where('reading_type', 'checkout')->count());
        $this->assertSame(1, $contract->invoices()->where('invoice_type', Invoice::TYPE_SETTLEMENT)->count());
        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Tiến độ kết thúc hợp đồng')
            ->assertSee('Quyết toán &amp; tiền cọc', false)
            ->assertSee('href="'.route('admin.contracts.check-out.form', $contract).'"', false);
        $history = $contract->statusHistories()->count();
        $payload['checkout_photos'] = [UploadedFile::fake()->image('damage-again.jpg')];
        $this->post(route('admin.contracts.check-out', $contract), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $contract->utilityReadings()->where('reading_type', 'checkout')->count());
        $this->assertSame($history, $contract->statusHistories()->count());
    }

    public function test_checkout_damage_requires_compensation_description_and_photo(): void
    {
        $contract = $this->active([], 'checkout-damage-validation');
        $base = [
            'actual_move_out_at' => now(), 'checkout_electricity' => 110, 'checkout_water' => 11,
            'checkout_reason' => 'Bàn giao phòng.', 'checkout_key_count' => 1,
            'handover_confirmed' => '1', 'has_damage' => 1,
        ];

        $this->actingAs($this->admin)->post(route('admin.contracts.check-out', $contract), $base)
            ->assertSessionHasErrors(['settlement_amount', 'settlement_description', 'checkout_damage_note', 'checkout_photos']);
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);

        $this->post(route('admin.contracts.check-out', $contract), $base + [
            'settlement_amount' => 300000,
            'settlement_description' => 'Bồi thường kính cửa sổ',
            'checkout_damage_note' => 'Kính cửa sổ bị vỡ.',
            'checkout_photos' => [UploadedFile::fake()->image('window-damage.jpg')],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($contract->fresh()->checkout_has_damage);
        $this->assertDatabaseHas('settlement_statement_items', [
            'settlement_statement_id' => $contract->settlementStatement->id,
            'type' => 'adjustment',
            'amount' => 300000,
        ]);
        $this->get(route('admin.contracts.check-out.form', $contract))
            ->assertOk()
            ->assertSee('Ban quản lý cần hoàn người thuê')
            ->assertSee('Có hư hỏng/thất lạc:')
            ->assertSee('Bồi thường kính cửa sổ')
            ->assertSee('Xem ảnh 1');
    }

    public function test_completion_requires_no_debt_and_explicit_deposit_resolution(): void
    {
        $contract = $this->active();
        $contract->forceFill(['deposit_amount' => 1000000, 'deposit_status' => Contract::DEPOSIT_PAID])->save();
        $this->lifecycle->checkOut($contract, $this->admin, [
            'actual_move_out_at' => now(), 'checkout_electricity' => 110, 'checkout_water' => 12,
            'checkout_reason' => 'Kết thúc đúng thỏa thuận.',
            'checkout_key_count' => 1, 'handover_confirmed' => true,
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

        $contract->forceFill([
            'deposit_status' => Contract::DEPOSIT_RETAINED,
            'deposit_resolution' => Contract::DEPOSIT_RETAINED,
            'deposit_damage_proof' => 'contracts/testing/damage-proof.jpg',
            'deposit_resolved_at' => now(),
        ])->save();
        $this->post(route('admin.contracts.complete-settlement', $contract), [
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

    public function test_cancelling_a_draft_releases_new_tenant_for_another_contract(): void
    {
        $draft = $this->draft(0, [], 'cancelled-draft-reusable-tenant');
        $tenant = $draft->tenant;
        $user = $tenant->user;

        $this->actingAs($this->admin)->post(route('admin.contracts.cancel', $draft), [
            'cancel_reason' => 'Không tiếp tục ký hợp đồng này.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Contract::STATUS_CANCELLED, $draft->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $user->fresh()->status);
        $this->assertTrue(Tenant::query()->eligibleForContract()->whereKey($tenant)->exists());

        $this->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee('value="'.$tenant->id.'"', false)
            ->assertSee($tenant->full_name);
    }

    public function test_admin_can_move_out_representative_transfer_role_and_print_immutable_appendix(): void
    {
        $contract = $this->active([], 'old-representative');
        $oldRepresentative = $contract->members()
            ->where('role', ContractTenant::ROLE_REPRESENTATIVE)
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->sole();
        $oldUser = $contract->tenant->user;
        $successorTenant = Tenant::create([
            'user_id' => null,
            'full_name' => 'Người đại diện mới',
            'date_of_birth' => '1992-04-05',
            'gender' => 'other',
            'cccd' => '079092000321',
            'cccd_issue_date' => '2021-01-01',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '0901234321',
            'email' => 'member-before-transfer@example.test',
            'address' => 'Địa chỉ người đại diện mới',
        ]);
        $successor = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => $successorTenant->id,
            'full_name' => $successorTenant->full_name,
            'date_of_birth' => $successorTenant->date_of_birth,
            'identity_number' => $successorTenant->cccd,
            'phone' => $successorTenant->phone,
            'address' => $successorTenant->address,
            'role' => ContractTenant::ROLE_TENANT,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => now()->subDay(),
            'declared_by' => $oldUser->id,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now()->subDay(),
        ]);
        $contract->forceFill(['number_of_people' => 2])->save();
        $contract->room->forceFill(['current_people' => 2])->save();

        $page = $this->actingAs($this->admin)->get(route('admin.contracts.show', $contract));
        $page->assertOk()
            ->assertDontSee('Lập biên bản trả phòng')
            ->assertSee('action="'.route('admin.contract-tenants.transfer-representative', $oldRepresentative).'"', false)
            ->assertSee('Chuyển đại diện và ghi nhận rời phòng');

        $this->post(route('admin.contract-tenants.transfer-representative', $oldRepresentative), [
            'successor_member_id' => $successor->id,
            'effective_at' => now(),
            'reason' => 'Đại diện cũ chuyển nơi ở và hai bên thống nhất chuyển giao.',
            'email' => 'new-representative@example.test',
            'temporary_password' => 'Temporary123!',
            'temporary_password_confirmation' => 'Temporary123!',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $contract->refresh();
        $successor->refresh();
        $newUser = $successorTenant->fresh()->user;
        $transfer = $contract->representativeTransfers()->sole();

        $this->assertSame(ContractTenant::STATUS_MOVED_OUT, $oldRepresentative->fresh()->status);
        $this->assertNotNull($oldRepresentative->fresh()->actual_move_out_at);
        $this->assertSame(ContractTenant::ROLE_REPRESENTATIVE, $successor->role);
        $this->assertSame(ContractTenant::STATUS_CHECKED_IN, $successor->status);
        $this->assertSame($successorTenant->id, $contract->tenant_id);
        $this->assertSame($successorTenant->id, $contract->representative_tenant_id);
        $this->assertSame(User::STATUS_LOCKED, $oldUser->fresh()->status);
        $this->assertSame(User::STATUS_PENDING, $newUser->status);
        $this->assertTrue($newUser->must_change_password);
        $this->assertTrue(Hash::check('Temporary123!', $newUser->password));
        $this->assertSame(1, $contract->number_of_people);
        $this->assertSame(1, $contract->room->fresh()->current_people);
        $this->assertSame('Người đại diện mới', $transfer->new_representative_snapshot['full_name']);
        $this->assertDatabaseHas('contract_histories', [
            'contract_id' => $contract->id,
            'action' => 'representative_transferred',
        ]);

        $this->get(route('admin.contracts.show', $contract))
            ->assertOk()
            ->assertSee('In phụ lục chuyển giao')
            ->assertSee('href="'.route('admin.representative-transfers.appendix', $transfer).'"', false);
        $this->get(route('admin.tenants.show', $oldRepresentative->tenant_id))
            ->assertOk()
            ->assertSee('Đã rời phòng')
            ->assertSee('Đã khóa');
        $this->get(route('admin.tenants.index', ['status' => 'renting']))
            ->assertOk()
            ->assertDontSee($oldRepresentative->full_name)
            ->assertSee($successor->full_name);
        $this->get(route('admin.tenants.index', ['status' => 'moved_out']))
            ->assertOk()
            ->assertSee($oldRepresentative->full_name)
            ->assertDontSee($successor->full_name)
            ->assertSee('Đã rời phòng');
        $this->get(route('admin.representative-transfers.appendix', $transfer))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
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
            ->assertSessionHasErrors('handover_reading');

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
        $this->lifecycle->saveHandoverDraft($contract, $this->admin, 100, 10);
        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);
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
            'has_damage' => 0,
            'checkout_key_count' => 0, 'handover_confirmed' => '1',
        ])->assertSessionHasErrors('contract');

        $active = $this->active([], 'checkout-guards');
        $this->post(route('admin.contracts.check-out', $active), [
            'actual_move_out_at' => now()->addMinute(), 'checkout_electricity' => 110,
            'checkout_water' => 11, 'checkout_reason' => 'Tương lai.',
            'has_damage' => 0,
            'checkout_key_count' => 0, 'handover_confirmed' => '1',
        ])->assertSessionHasErrors('actual_move_out_at');
        $this->post(route('admin.contracts.check-out', $active), [
            'actual_move_out_at' => now(), 'checkout_electricity' => 99,
            'checkout_water' => 9, 'checkout_reason' => 'Chỉ số sai.',
            'has_damage' => 0,
            'checkout_key_count' => 0, 'handover_confirmed' => '1',
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
        $this->actingAs($this->admin)->get(route('admin.contracts.template.print'))
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

    public function test_publishing_contract_template_creates_a_new_version_without_overwriting_the_old_one(): void
    {
        $current = ContractTemplate::activeOrCreate();
        $clauses = ContractTemplate::DEFAULT_CLAUSES;
        $clauses['early_termination'] = 'Điều khoản chính sách mới chỉ áp dụng cho hợp đồng phát hành sau thời điểm này.';

        $this->actingAs($this->admin)->post(route('admin.contracts.template.store'), [
            'name' => 'Mẫu chính sách mới',
            'clauses' => $clauses,
        ])->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse($current->fresh()->is_active);
        $this->assertDatabaseHas('contract_templates', [
            'version' => $current->version + 1,
            'name' => 'Mẫu chính sách mới',
            'is_active' => true,
        ]);

        $newTemplate = ContractTemplate::query()->where('is_active', true)->sole();
        $this->get(route('admin.contracts.template'))
            ->assertOk()
            ->assertSee('Lịch sử phiên bản')
            ->assertSee('href="'.route('admin.contracts.template.show', $newTemplate).'"', false)
            ->assertDontSee('name="clauses[early_termination]"', false)
            ->assertDontSee('Xem trước mẫu hiện hành')
            ->assertDontSee('Nguyên tắc an toàn');
        $this->get(route('admin.contracts.template.show', $current))
            ->assertOk()
            ->assertSee('Phiên bản '.$current->version)
            ->assertSee('id="template-editor" class="hidden', false)
            ->assertSee('Sửa phiên bản này')
            ->assertSee('template-mini-preview', false)
            ->assertSee('name="clauses[early_termination]"', false)
            ->assertSee('Lưu thành phiên bản mới');
    }

    public function test_signed_contract_document_is_snapshotted_and_immutable_across_template_versions(): void
    {
        $contract = $this->draft();
        $this->lifecycle->submitForSignature($contract, $this->admin);
        $lockedTemplateId = $contract->fresh()->contract_template_id;

        $this->lifecycle->markAsSigned($contract, $this->admin, now());
        $signed = $contract->fresh();
        $originalContent = $signed->contract_content;
        $originalHash = $signed->contract_content_sha256;

        $this->assertNotNull($signed->contract_content_snapshotted_at);
        $this->assertSame(hash('sha256', $originalContent), $originalHash);
        $this->assertSame($lockedTemplateId, $signed->contract_template_id);

        ContractTemplate::query()->where('is_active', true)->update(['is_active' => false]);
        ContractTemplate::query()->create([
            'name' => 'Mẫu tương lai',
            'version' => ContractTemplate::query()->max('version') + 1,
            'clauses' => array_replace(ContractTemplate::DEFAULT_CLAUSES, [
                'early_termination' => 'Nội dung hoàn toàn mới không được xuất hiện trong hợp đồng đã ký.',
            ]),
            'is_active' => true,
            'effective_from' => now(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.contracts.print', $contract))->assertOk()
            ->assertDontSee('Nội dung hoàn toàn mới không được xuất hiện trong hợp đồng đã ký.');
        $this->assertSame($originalContent, $contract->fresh()->contract_content);
        $this->assertSame($originalHash, $contract->fresh()->contract_content_sha256);

        $this->expectException(\LogicException::class);
        $signed->forceFill(['contract_content' => $originalContent.' bị sửa'])->save();
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

        return $contract->fresh();
    }

    private function awaiting(array $overrides = [], ?string $tenantKey = null): Contract
    {
        $contract = $this->draft(0, $overrides, $tenantKey);
        $contract = $this->sign($contract);
        $this->payDeposit($contract);
        $this->lifecycle->saveHandoverDraft($contract, $this->admin, 100, 10);
        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);

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
            'actual_move_in_at' => now(),
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

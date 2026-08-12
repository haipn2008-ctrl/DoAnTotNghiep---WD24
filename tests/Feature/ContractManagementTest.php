<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractOccupant;
use App\Models\ContractStatusHistory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\ContractLifecycleService;
use Carbon\Carbon;
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
            ->assertSee('Rà soát thông tin trước khi phát hành cho khách ký.')
            ->assertSee('Thông tin hợp đồng')
            ->assertSee('Người đại diện và người ở')
            ->assertSee('Tài chính dự kiến')
            ->assertSee('Dịch vụ đăng ký')
            ->assertSee('Phát hành bản nháp')
            ->assertSee('href="'.route('admin.contracts.edit', $contract).'"', false)
            ->assertSee('action="'.route('admin.contracts.submit-for-signature', $contract).'"', false)
            ->assertSee('action="'.route('admin.contracts.cancel', $contract).'"', false)
            ->assertDontSee('Backend kiểm tra lại toàn bộ điều kiện');
    }

    public function test_create_only_writes_draft_without_signing_occupying_reading_or_invoice(): void
    {
        $room = $this->room('DRAFT');
        $tenant = $this->tenant('draft');

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
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
        $this->assertSame('2026-09-11 23:59:59', $contract->reservation_expires_at->toDateTimeString());
        $this->assertNull($contract->move_in_terms_confirmed_by);
        $this->assertNull($contract->move_in_terms_confirmed_at);
        $this->assertNull($contract->signature_due_at);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
        $this->assertSame(0, $room->fresh()->current_people);
        $this->assertDatabaseCount('utility_readings', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id, 'full_name' => 'Người đại diện đã bổ sung',
            'phone' => '0911222333', 'cccd' => '079123456789', 'address' => '123 Đường kiểm thử',
        ]);
        $this->assertDatabaseHas('contract_occupants', [
            'contract_id' => $contract->id, 'role' => ContractOccupant::ROLE_REPRESENTATIVE,
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
            'representative_is_occupant' => true, 'parking_quantity' => 1,
            'internet_enabled' => true, 'service_enabled' => false,
        ], $this->admin);

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
            'representative_is_occupant' => true, 'parking_quantity' => 2,
            'internet_enabled' => true, 'service_enabled' => false,
        ], $this->admin);
        $this->lifecycle->submitForSignature($contract, $this->admin);

        $this->actingAs($tenant->user)->get(route('client.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Thông tin nhận phòng')
            ->assertSee('Internet')
            ->assertSee('Đã đăng ký')
            ->assertSee('2 xe đã đăng ký')
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

        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('move_in_details_confirmed');
        $this->assertNull($contract->fresh()->actual_move_in_at);

        $this->lifecycle->confirmMoveInDetails($contract, $contract->tenant->user);
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
    }

    public function test_draft_records_representative_and_named_occupants_without_requiring_accounts(): void
    {
        $room = $this->room('MEMBERS');
        $representative = $this->tenant('representative');
        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee('Người đại diện thuê')
            ->assertSee('Không cần tạo tài khoản')
            ->assertSee('Ngày bắt đầu thời hạn thuê')
            ->assertSee('Hạn cuối phải nhận phòng')
            ->assertSee('name="contract_duration"', false)
            ->assertSee('Thuê ít ngày')
            ->assertDontSee('Thông tin tài khoản được điền sẵn')
            ->assertDontSee('Không chọn nếu người đứng tên thuê phòng cho người khác')
            ->assertDontSee('name="signature_due_at"', false)
            ->assertSee('name="reservation_expires_at"', false)
            ->assertSee('name="move_in_terms_confirmed"', false)
            ->assertDontSee('name="deposit_due_at"', false);

        $this->post(route('admin.contracts.store'), $this->payload($room, $representative, [
            'number_of_people' => 20,
            'occupants' => [[
                'full_name' => 'Người ở cùng A', 'identity_number' => '012345678901',
                'phone' => '0901234567', ...$this->occupantIdentityImages('member-a'),
            ]],
        ]))->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame($representative->id, $contract->representative_tenant_id);
        $this->assertSame(2, $contract->number_of_people);
        $this->assertDatabaseHas('contract_occupants', [
            'contract_id' => $contract->id, 'tenant_id' => $representative->id,
            'role' => ContractOccupant::ROLE_REPRESENTATIVE, 'status' => ContractOccupant::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('contract_occupants', [
            'contract_id' => $contract->id, 'tenant_id' => null, 'full_name' => 'Người ở cùng A',
            'role' => ContractOccupant::ROLE_OCCUPANT, 'status' => ContractOccupant::STATUS_APPROVED,
        ]);

        $this->put(route('admin.contracts.update', $contract), $this->payload($room, $representative, [
            'occupants' => [[
                'full_name' => 'Người ở cùng B', 'identity_number' => '012345678902',
                'phone' => '0901234568', ...$this->occupantIdentityImages('member-b'),
            ]],
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contract_occupants', [
            'contract_id' => $contract->id, 'full_name' => 'Người ở cùng A',
            'status' => ContractOccupant::STATUS_WITHDRAWN,
        ]);
        $this->assertDatabaseHas('contract_occupants', [
            'contract_id' => $contract->id, 'full_name' => 'Người ở cùng B',
            'role' => ContractOccupant::ROLE_OCCUPANT, 'status' => ContractOccupant::STATUS_APPROVED,
        ]);
        $this->assertDatabaseCount('contract_occupant_histories', 8);
        $this->assertSame(2, $contract->fresh()->number_of_people);
    }

    public function test_contract_dates_are_derived_from_start_and_selected_duration(): void
    {
        $room = $this->room('DERIVED-DATES');
        $tenant = $this->tenant('derived-dates');
        $payload = $this->payload($room, $tenant, [
            'contract_duration' => '3',
            'end_date' => '2099-12-31',
            'reservation_expires_at' => '2099-12-30',
            'scheduled_move_in_date' => '2026-09-11',
        ]);
        unset($payload['move_in_terms_confirmed']);
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame('2026-12-01', $contract->end_date->toDateString());
        $this->assertSame('3', $contract->rental_duration_option);
        $this->assertSame('2026-09-11 23:59:59', $contract->reservation_expires_at->toDateTimeString());
        $this->assertNull($contract->signature_due_at);

        $otherRoom = $this->room('MOVE-IN-OUTSIDE-WINDOW');
        $otherTenant = $this->tenant('move-in-outside-window');
        $this->post(route('admin.contracts.store'), $this->payload($otherRoom, $otherTenant, [
            'contract_duration' => '6',
            'scheduled_move_in_date' => '2026-09-12',
        ]))->assertSessionHasErrors('scheduled_move_in_date');
        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_occupied_room_only_accepts_a_contract_starting_after_the_current_lease_ends(): void
    {
        $current = $this->active([
            'start_date' => '2026-08-11',
            'scheduled_move_in_date' => '2026-08-11',
            'reservation_expires_at' => '2026-08-12 18:00:00',
            'end_date' => '2026-08-31',
        ], 'current-room-guest');
        $newTenant = $this->tenant('future-room-guest');

        $this->actingAs($this->admin)->get(route('admin.contracts.create'))
            ->assertOk()
            ->assertSee('data-available-from="2026-09-01"', false)
            ->assertSee('có thể thuê từ 01/09/2026');

        $this->post(route('admin.contracts.store'), $this->payload($current->room, $newTenant, [
            'contract_duration' => '3',
            'start_date' => '2026-08-31',
            'scheduled_move_in_date' => '2026-08-31',
        ]))->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('contracts', 1);

        $this->post(route('admin.contracts.store'), $this->payload($current->room, $newTenant, [
            'contract_duration' => '3',
            'start_date' => '2026-09-01',
            'scheduled_move_in_date' => '2026-09-01',
        ]))->assertSessionHasNoErrors();

        $futureDraft = Contract::query()->whereKeyNot($current->id)->sole();
        $this->assertSame(Contract::STATUS_DRAFT, $futureDraft->status);
        $this->assertSame('2026-09-01', $futureDraft->start_date->toDateString());
    }

    public function test_move_in_dates_require_admin_confirmation_and_stay_inside_contract(): void
    {
        $room = $this->room('MOVE-IN-TERMS');
        $tenant = $this->tenant('move-in-terms');
        $payload = $this->payload($room, $tenant, [
            'contract_duration' => 'short_term',
            'end_date' => '2026-09-07',
            'reservation_expires_at' => '2026-09-05',
        ]);
        unset($payload['move_in_terms_confirmed']);
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasErrors('move_in_terms_confirmed');
        $this->assertDatabaseCount('contracts', 0);

        $payload = $this->payload($room, $tenant, [
            'contract_duration' => 'short_term',
            'end_date' => '2026-09-07',
            'scheduled_move_in_date' => '2026-09-05',
            'reservation_expires_at' => '2026-09-04',
        ]);
        $this->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasErrors('reservation_expires_at');
        $this->assertDatabaseCount('contracts', 0);

        $payload = $this->payload($room, $tenant, [
            'contract_duration' => 'short_term',
            'end_date' => '2026-09-07',
            'reservation_expires_at' => '2026-09-08',
        ]);
        $this->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasErrors('reservation_expires_at');
        $this->assertDatabaseCount('contracts', 0);

        $payload = $this->payload($room, $tenant, [
            'contract_duration' => 'short_term',
            'end_date' => '2026-09-07',
            'reservation_expires_at' => '2026-09-05',
        ]);
        $this->post(route('admin.contracts.store'), $payload)->assertSessionHasNoErrors();
        $contract = Contract::sole();
        $this->assertSame('2026-09-05 23:59:59', $contract->reservation_expires_at->toDateTimeString());
        $this->assertSame(0.6667, (float) ContractStatusHistory::sole()->metadata['move_in_window_ratio']);
    }

    public function test_short_term_contract_accepts_manual_end_date_up_to_three_months(): void
    {
        $room = $this->room('SHORT-TERM');
        $tenant = $this->tenant('short-term');
        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'contract_duration' => 'short_term',
            'end_date' => '2026-10-15',
        ]))->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame('2026-10-15', $contract->end_date->toDateString());
        $this->assertSame('short_term', $contract->rental_duration_option);
        $this->get(route('admin.contracts.edit', $contract))
            ->assertOk()
            ->assertSee('value="short_term" selected', false);

        $otherRoom = $this->room('SHORT-TERM-TOO-LONG');
        $otherTenant = $this->tenant('short-term-too-long');
        $this->post(route('admin.contracts.store'), $this->payload($otherRoom, $otherTenant, [
            'contract_duration' => 'short_term',
            'end_date' => '2026-12-02',
        ]))->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_lessee_does_not_occupy_a_slot_by_default_and_two_identity_sides_are_private(): void
    {
        $room = $this->room('RENT-FOR-OTHER');
        $tenant = $this->tenant('non-resident-lessee');
        $payload = $this->payload($room, $tenant, [
            'deposit_amount' => 0,
            'occupants' => [[
                'full_name' => 'Người ở thực tế', 'identity_number' => '012345678955',
                ...$this->occupantIdentityImages('actual-resident'),
            ]],
        ]);
        unset($payload['representative_is_occupant']);

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $representative = $contract->occupants()->where('role', ContractOccupant::ROLE_REPRESENTATIVE)->sole();
        $resident = $contract->occupants()->where('role', ContractOccupant::ROLE_OCCUPANT)->sole();
        $this->assertFalse($contract->representative_is_occupant);
        $this->assertSame(1, $contract->number_of_people);
        $this->assertSame(ContractOccupant::STATUS_NON_RESIDENT, $representative->status);
        $this->assertSame(ContractOccupant::STATUS_APPROVED, $resident->status);
        Storage::disk('local')->assertExists([$representative->identity_front_path, $representative->identity_back_path]);

        $this->actingAs($tenant->user)->get(route('admin.contract-occupants.identity-document', [$representative, 'front']))
            ->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.contract-occupants.identity-document', [$representative, 'front']))
            ->assertOk();

        $this->sign($contract);
        $this->lifecycle->checkIn($contract, $this->admin, $this->checkInPayload([
            'schedule_variance_reason' => 'Người ở nhận phòng sớm theo thỏa thuận.',
        ]));
        $this->assertSame(1, $room->fresh()->current_people);
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
        $this->assertDatabaseCount('contract_occupants', 0);
    }

    public function test_non_contiguous_occupant_index_keeps_text_and_identity_files_on_the_same_member(): void
    {
        $room = $this->room('MEMBER-INDEX-GAP');
        $tenant = $this->tenant('member-index-gap');
        $payload = $this->payload($room, $tenant, [
            'occupants' => [3 => [
                'full_name' => 'Người ở sau khi thêm lại',
                'identity_number' => '012345678966',
                'phone' => '0901234569',
                ...$this->occupantIdentityImages('member-index-gap'),
            ]],
        ]);

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $occupant = Contract::sole()->occupants()
            ->where('role', ContractOccupant::ROLE_OCCUPANT)
            ->sole();
        $this->assertSame('Người ở sau khi thêm lại', $occupant->full_name);
        $this->assertSame('012345678966', $occupant->identity_number);
        Storage::disk('local')->assertExists([
            $occupant->identity_front_path,
            $occupant->identity_back_path,
        ]);
    }

    public function test_room_capacity_rejects_the_fifth_resident_for_a_four_person_room(): void
    {
        $room = $this->room('CAPACITY-FOUR', ['max_people' => 4]);
        $tenant = $this->tenant('capacity-four');
        $occupants = collect(range(1, 5))->map(fn (int $number): array => [
            'full_name' => 'Người ở '.$number,
            'identity_number' => sprintf('012345678%03d', $number),
            ...$this->occupantIdentityImages('capacity-'.$number),
        ])->all();
        $payload = $this->payload($room, $tenant, ['occupants' => $occupants]);
        unset($payload['representative_is_occupant']);

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $payload)
            ->assertSessionHasErrors('number_of_people');
        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('contract_occupants', 0);
    }

    public function test_representative_cannot_be_added_as_resident_when_room_is_already_full(): void
    {
        $room = $this->room('CAPACITY-REPRESENTATIVE', ['max_people' => 2]);
        $tenant = $this->tenant('capacity-representative');
        $occupants = collect(range(1, 2))->map(fn (int $number): array => [
            'full_name' => 'Người ở '.$number,
            'identity_number' => sprintf('012345679%03d', $number),
            ...$this->occupantIdentityImages('capacity-representative-'.$number),
        ])->all();

        $this->actingAs($this->admin)->post(route('admin.contracts.store'), $this->payload($room, $tenant, [
            'representative_is_occupant' => 1,
            'occupants' => $occupants,
        ]))->assertSessionHasErrors('number_of_people');

        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('contract_occupants', 0);
    }

    public function test_ajax_draft_validation_returns_field_specific_vietnamese_errors_without_redirecting(): void
    {
        $room = $this->room('AJAX-VALIDATION');
        $tenant = $this->tenant('ajax-validation');
        $payload = $this->payload($room, $tenant, [
            'occupants' => [[
                'full_name' => 'Người kiểm thử',
                'identity_number' => '012345678901',
            ]],
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.contracts.store'), $payload);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'occupants.0.identity_front',
                'occupants.0.identity_back',
            ]);
        $this->assertSame('Vui lòng chọn ảnh mặt trước CCCD của người ở.', $response->json('errors')['occupants.0.identity_front'][0]);
        $this->assertSame('Vui lòng chọn ảnh mặt sau CCCD của người ở.', $response->json('errors')['occupants.0.identity_back'][0]);

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

    public function test_client_declares_occupant_and_admin_review_is_required_before_check_in(): void
    {
        $contract = $this->awaiting();
        $client = $contract->tenant->user;

        $this->actingAs($client)->post(route('client.contracts.occupants.store', $contract), [
            'full_name' => 'Người chờ duyệt', 'identity_number' => '012345678911',
            'phone' => '0901111111', ...$this->occupantIdentityImages('pending-resident'),
        ])->assertSessionHasNoErrors();

        $occupant = ContractOccupant::query()->where('full_name', 'Người chờ duyệt')->sole();
        $this->assertNull($occupant->tenant_id);
        $this->assertSame(ContractOccupant::STATUS_PENDING, $occupant->status);
        $this->assertSame(Room::STATUS_AVAILABLE, $contract->room->fresh()->status);
        $this->assertSame(0, $contract->room->fresh()->current_people);

        $this->actingAs($this->admin)->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())
            ->assertSessionHasErrors('occupants');
        $this->post(route('admin.contract-occupants.approve', $occupant))->assertSessionHasNoErrors();
        $this->post(route('admin.contracts.check-in', $contract), $this->checkInPayload())->assertSessionHasNoErrors();

        $this->assertSame(ContractOccupant::STATUS_CHECKED_IN, $occupant->fresh()->status);
        $this->assertSame(2, $contract->room->fresh()->current_people);
    }

    public function test_approved_occupant_can_join_and_leave_active_room_without_losing_history(): void
    {
        $contract = $this->active();
        $client = $contract->tenant->user;
        $this->assertSame(1, $contract->room->fresh()->current_people);

        $this->actingAs($client)->post(route('client.contracts.occupants.store', $contract), [
            'full_name' => 'Người vào sau', 'identity_number' => '012345678912',
            ...$this->occupantIdentityImages('late-resident'),
        ])->assertSessionHasNoErrors();
        $occupant = ContractOccupant::query()->where('full_name', 'Người vào sau')->sole();
        $this->assertSame(1, $contract->room->fresh()->current_people);

        $this->actingAs($this->admin)->post(route('admin.contract-occupants.approve', $occupant))
            ->assertSessionHasNoErrors();
        $this->assertSame(ContractOccupant::STATUS_CHECKED_IN, $occupant->fresh()->status);
        $this->assertSame(2, $contract->room->fresh()->current_people);

        $this->post(route('admin.contract-occupants.move-out', $occupant), [
            'actual_move_out_at' => now()->format('Y-m-d H:i:s'), 'reason' => 'Chuyển nơi ở.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(ContractOccupant::STATUS_MOVED_OUT, $occupant->fresh()->status);
        $this->assertSame(1, $contract->room->fresh()->current_people);
        $this->assertDatabaseHas('contract_occupant_histories', [
            'contract_occupant_id' => $occupant->id, 'action' => 'occupant_move_out',
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

    public function test_signed_contract_without_deposit_goes_directly_to_awaiting_move_in(): void
    {
        $contract = $this->draft(0);
        $this->sign($contract);

        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
        $this->assertSame(Contract::DEPOSIT_NOT_REQUIRED, $contract->fresh()->deposit_resolution);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(Room::STATUS_AVAILABLE, $contract->room->fresh()->status);
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

    public function test_deposit_invoice_is_unique_and_only_successful_full_payment_advances_state(): void
    {
        $contract = $this->draft(2000000);
        $this->sign($contract);
        $this->actingAs($this->admin)->post(route('admin.contracts.deposit-invoice.issue', $contract))->assertRedirect();
        $invoice = $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->sole();
        $this->post(route('admin.contracts.deposit-invoice.issue', $contract));
        $this->assertSame(1, $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->count());

        Payment::query()->forceCreate(['invoice_id' => $invoice->id, 'amount_paid' => 500000, 'payment_date' => today(), 'payment_method' => 'cash', 'status' => Payment::STATUS_PENDING]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        Payment::query()->forceCreate(['invoice_id' => $invoice->id, 'amount_paid' => 500000, 'payment_date' => today(), 'payment_method' => 'cash', 'status' => Payment::STATUS_FAILED]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1000000, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_PENDING_DEPOSIT, $contract->fresh()->status);
        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1000000, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1, 'payment_date' => today()->toDateString(), 'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasErrors('amount_paid');
    }

    public function test_overlapping_reservations_are_rejected_but_non_overlapping_future_contract_is_allowed(): void
    {
        $room = $this->room('RESERVE');
        $first = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2026-09-01', 'scheduled_move_in_date' => '2026-09-01', 'reservation_expires_at' => '2026-09-02 18:00:00', 'end_date' => '2026-12-31']);
        $this->sign($first);

        $second = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2026-12-01', 'scheduled_move_in_date' => '2026-12-01', 'reservation_expires_at' => '2026-12-02 18:00:00', 'end_date' => '2027-02-01'], 'reserve-second');
        $this->lifecycle->submitForSignature($second, $this->admin);
        $this->expectException(ValidationException::class);
        try {
            $this->lifecycle->markAsSigned($second, $this->admin, now());
        } finally {
            $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $second->fresh()->status);
            $third = $this->draft(0, ['room_id' => $room->id, 'start_date' => '2027-01-01', 'scheduled_move_in_date' => '2027-01-01', 'reservation_expires_at' => '2027-01-02 18:00:00', 'end_date' => '2027-12-31'], 'reserve-third');
            $this->sign($third);
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

    public function test_overdue_move_in_scheduler_is_idempotent_and_admin_can_extend_or_cancel(): void
    {
        $contract = $this->awaiting(['start_date' => '2026-08-10', 'scheduled_move_in_date' => '2026-08-10', 'reservation_expires_at' => '2026-08-10 18:00:00']);
        $this->artisan('contracts:process-lifecycle')->assertSuccessful();
        $this->artisan('contracts:process-lifecycle')->assertSuccessful();
        $this->assertSame(1, ContractLifecycleAlert::where('contract_id', $contract->id)->where('type', 'move_in_overdue')->count());
        $this->assertSame(Contract::STATUS_AWAITING_MOVE_IN, $contract->fresh()->status);
        $this->assertDatabaseCount('payments', 0);

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
            'end_date' => '2026-08-10',
        ]);
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

    public function test_completion_requires_no_debt_and_explicit_deposit_resolution_or_authorized_write_off(): void
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
            'deposit_resolution' => Contract::DEPOSIT_REFUNDED, 'confirm_complete' => 1,
        ])->assertSessionHasErrors('invoices');
        $this->assertSame(Contract::STATUS_SETTLING, $contract->fresh()->status);

        $this->post(route('admin.contracts.complete-settlement', $contract), [
            'deposit_resolution' => Contract::DEPOSIT_RETAINED, 'settlement_note' => 'Biên bản BT-01',
            'write_off_outstanding' => 1, 'write_off_reason' => 'Quản lý phê duyệt miễn khoản nhỏ.',
            'confirm_complete' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Contract::STATUS_COMPLETED, $contract->fresh()->status);
        $this->assertSame(Invoice::STATUS_WRITTEN_OFF, $invoice->fresh()->status);
        $this->assertSame($this->admin->id, $contract->fresh()->completed_by);
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
        $invoice = $this->lifecycle->issueDepositInvoice($contract, $this->admin);
        $payment = Payment::query()->forceCreate([
            'invoice_id' => $invoice->id, 'amount_paid' => 1000000, 'payment_date' => today(),
            'payment_method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_SUCCESS,
        ]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->lifecycle->checkIn($contract, $this->admin, $this->checkInPayload());

        $payment->update(['status' => Payment::STATUS_FAILED]);
        $this->lifecycle->syncDepositState($contract, $this->admin, 'Ngân hàng đảo giao dịch.');

        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $contract->room->fresh()->status);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id, 'type' => 'deposit_exception',
        ]);
    }

    public function test_cancelling_after_collecting_deposit_requires_explicit_financial_resolution(): void
    {
        $contract = $this->draft(1000000, [], 'cancel-paid-deposit');
        $this->sign($contract);
        $invoice = $this->lifecycle->issueDepositInvoice($contract, $this->admin);
        Payment::query()->forceCreate([
            'invoice_id' => $invoice->id, 'amount_paid' => 500000, 'payment_date' => today(),
            'payment_method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_SUCCESS,
        ]);
        $this->lifecycle->syncDepositState($contract, $this->admin);
        $this->lifecycle->cancel($contract, $this->admin, 'Khách hủy sau khi đã cọc một phần.');

        $this->assertSame(Contract::STATUS_CANCELLED, $contract->fresh()->status);
        $this->assertSame('pending_resolution', $contract->fresh()->deposit_resolution);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id, 'type' => 'cancelled_deposit_resolution',
        ]);
        $this->assertSame(500000.0, $contract->fresh()->deposit_paid_amount);
    }

    public function test_scheduler_deduplicates_signature_and_deposit_overdue_alerts(): void
    {
        $signature = $this->draft(0, ['signature_due_at' => now()->subDay()], 'signature-overdue');
        $this->lifecycle->submitForSignature($signature, $this->admin);
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
        $contract = $this->draft();
        $contract->forceFill([
            'landlord_name_snapshot' => 'Chủ nhà snapshot',
            'property_address_snapshot' => 'Địa chỉ snapshot',
        ])->save();
        $this->actingAs($this->admin)->get(route('admin.contracts.print', $contract))
            ->assertOk()->assertSee('BẢN DỰ THẢO / CHƯA KÝ')->assertSee('Chủ nhà snapshot')->assertSee('Địa chỉ snapshot');
        $this->sign($contract);
        $this->get(route('admin.contracts.print', $contract))->assertOk()
            ->assertDontSee('BẢN DỰ THẢO / CHƯA KÝ')->assertSee('11/08/2026');
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
            'representative_is_occupant' => true, 'number_of_people' => 1, 'parking_quantity' => 0,
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

        return $this->sign($contract);
    }

    private function active(array $overrides = [], ?string $tenantKey = null): Contract
    {
        $contract = $this->awaiting($overrides, $tenantKey);
        $this->lifecycle->checkIn($contract, $this->admin, $this->checkInPayload([
            'schedule_variance_reason' => $contract->scheduled_move_in_date?->isSameDay(now()) ? null : 'Ngày nhận thực tế khác lịch.',
        ]));

        return $contract->fresh();
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
            'representative_is_occupant' => 1, 'number_of_people' => 1, 'parking_quantity' => 0,
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

    private function occupantIdentityImages(string $prefix): array
    {
        return [
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
            'cccd' => substr(str_pad((string) abs(crc32($key)), 12, '0'), 0, 12),
            'phone' => '09'.substr(str_pad((string) abs(crc32('p'.$key)), 8, '0'), 0, 8),
            'email' => $key.'@tenant.test',
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
